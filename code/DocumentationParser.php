<?php

/**
 * Parser wrapping the Markdown Extra parser.
 * 
 * @see http://michelf.com/projects/php-markdown/extra/
 *
 * @package docsviewer
 */
class DocumentationParser {

	const CODE_BLOCK_BACKTICK = 1;
	const CODE_BLOCK_COLON = 2;

	/**
	 * @var string Rewriting of api links in the format "[api:MyClass]" or "[api:MyClass::$my_property]".
	 */
	public static $api_link_base = 'http://api.silverstripe.org/search/lookup/?q=%s&version=%s&module=%s';
	
	/**
	 * @var array
	 */
	public static $heading_counts = array();
	
	/**
	 * Parse a given path to the documentation for a file. Performs a case 
	 * insensitive lookup on the file system. Automatically appends the file 
	 * extension to one of the markdown extensions as well so /install/ in a
	 * web browser will match /install.md or /INSTALL.md.
	 * 
	 * Filepath: /var/www/myproject/src/cms/en/folder/subfolder/page.md
	 * URL: http://myhost/mywebroot/dev/docs/2.4/cms/en/folder/subfolder/page
	 * Webroot: http://myhost/mywebroot/
	 * Baselink: dev/docs/2.4/cms/en/
	 * Pathparts: folder/subfolder/page
	 * 
	 * @param DocumentationPage $page
	 * @param String $baselink Link relative to webroot, up until the "root" 
	 *							of the module. Necessary to rewrite relative 
	 *							links
	 *
	 * @return String
	 */
	public static function parse(DocumentationPage $page, $baselink = null) {
		if(!$page || (!$page instanceof DocumentationPage)) return false;

		$md = $page->getMarkdown();
		
		// Pre-processing
		$md = self::rewrite_image_links($md, $page);
		$md = self::rewrite_relative_links($md, $page, $baselink);

		$md = self::rewrite_api_links($md, $page);
		$md = self::rewrite_heading_anchors($md, $page);
		$md = self::rewrite_code_blocks($md);
	
		require_once(DOCSVIEWER_PATH .'/thirdparty/markdown/markdown.php');
		
		$parser = new MarkdownExtra_Parser();
		$parser->no_markup = true;
		
		return $parser->transform($md);
	}
	
	public static function rewrite_code_blocks($md) {
		$started = false;
		$inner = false;
		$mode = false;
		$end = false;

		$lines = explode("\n", $md);
		$output = array();

		foreach($lines as $i => $line) {
			if(!$started && preg_match('/^\t*:::\s*(.*)/', $line, $matches)) {
				// first line with custom formatting
				$started = true;
				$mode = self::CODE_BLOCK_COLON;
				$output[$i] = sprintf('<pre class="brush: %s">', (isset($matches[1])) ? $matches[1] : "");
			} 
			elseif(!$started && preg_match('/^\t*```\s*(.*)/', $line, $matches)) {
				$started = true;
				$mode = self::CODE_BLOCK_BACKTICK;
				$output[$i] = sprintf('<pre class="brush: %s">', (isset($matches[1])) ? $matches[1] : "");
			} 
			elseif($started && $mode == self::CODE_BLOCK_BACKTICK) {
				// inside a backtick fenced box
				if(preg_match('/^\t*```\s*/', $line, $matches)) {
					// end of the backtick fenced box. Unset the line that contains the backticks
					$end = true;
				}
				else {
					// still inside the line.
					$output[$i] = ($started) ? '' : '<pre>' . "\n";
					$output[$i] .= htmlentities($line, ENT_COMPAT, 'UTF-8');
					$inner = true;
				}
			} 
			elseif(preg_match('/^\t(.*)/', $line, $matches)) {
				// inner line of block, or first line of standard markdown code block
				// regex removes first tab (any following tabs are part of the code).
				$output[$i] = ($started) ? '' : '<pre>' . "\n";
				$output[$i] .= htmlentities($matches[1], ENT_COMPAT, 'UTF-8');
				$inner = true;
				$started = true;
			}
			elseif($started && $inner && $mode == self::CODE_BLOCK_COLON && trim($line) === "") {
				// still inside a colon based block, if the line is only whitespace 
				// then continue with  with it. We can continue with it for now as 
				// it'll be tidied up later in the $end section.
				$inner = true;
				$output[$i] = $line;
			}
			elseif($started && $inner) {
				// line contains something other than whitespace, or tabbed. E.g
				// 	> code
				//	> \n
				//	> some message
				//
				// So actually want to reset $i to the line before this new line
				// and include this line. The edge case where this will fail is
				// new the following segment contains a code block as well as it
				// will not open.
				$end = true;
				$output[$i] = $line;
				$i = $i -1;
			}
			else {
				$output[$i] = $line;
			}

			if($end) {
				$output = self::finalize_code_output($i, $output);

				// reset state
				$started = $inner = $mode = $end = false;
			}
		}

		if($started) {
			$output = self::finalize_code_output($i, $output);
		}

		return join("\n", $output);

	}

	/**
	 * @param int
	 * @param array
	 *
	 * @return array
	 */
	private static function finalize_code_output($i, $output) {
		$j = $i;

		while(isset($output[$j]) && trim($output[$j]) === "") {
			unset($output[$j]);
		
			$j--;
		}
				
		if(isset($output[$j])) {
			$output[$j] .= "</pre>\n";
		}

		else {
			$output[$j] = "</pre>\n\n";
		}

		return $output;				
	}
	
	static function rewrite_image_links($md, $page) {
		// Links with titles
		$re = '/
			!
			\[
				(.*?) # image title (non greedy)
			\] 
			\(
				(.*?) # image url (non greedy)
			\)
		/x';
		preg_match_all($re, $md, $images);
		if($images) foreach($images[0] as $i => $match) {
			$title = $images[1][$i];
			$url = $images[2][$i];
			
			// Don't process absolute links (based on protocol detection)
			$urlParts = parse_url($url);

			if($urlParts && isset($urlParts['scheme'])) continue;
			
			// Rewrite URL (relative or absolute)
			$baselink = Director::makeRelative(dirname($page->getPath()));
			$relativeUrl = rtrim($baselink, '/') . '/' . ltrim($url, '/');
			
			// Resolve relative paths
			while(strpos($relativeUrl, '/..') !== FALSE) {
				$relativeUrl = preg_replace('/\w+\/\.\.\//', '', $relativeUrl);
			}
			
			// Replace any double slashes (apart from protocol)
			$relativeUrl = preg_replace('/([^:])\/{2,}/', '$1/', $relativeUrl);
			
			// Make it absolute again
			$absoluteUrl = Director::absoluteBaseURL() . $relativeUrl;
			
			// Replace in original content
			$md = str_replace(
				$match, 
				sprintf('![%s](%s)', $title, $absoluteUrl),
				$md
			);
		}
		
		return $md;
	}
		
	/**
	 * Rewrite links with special "api:" prefix, from two possible formats:
	 * 1. [api:DataObject]
	 * 2. (My Title)(api:DataObject)
	 * 
	 * Hack: Replaces any backticks with "<code>" blocks,
	 * as the currently used markdown parser doesn't resolve links in backticks,
	 * but does resolve in "<code>" blocks.
	 * 
	 * @param String $md
	 * @param DocumentationPage $page
	 * @return String
	 */
	static function rewrite_api_links($md, $page) {
		// Links with titles
		$re = '/
			`?
			\[
				(.*?) # link title (non greedy)
			\] 
			\(
				api:(.*?) # link url (non greedy)
			\)
			`?
		/x';
		preg_match_all($re, $md, $linksWithTitles);
		if($linksWithTitles) {
			foreach($linksWithTitles[0] as $i => $match) {
				$title = $linksWithTitles[1][$i];
				$subject = $linksWithTitles[2][$i];
				$url = sprintf(self::$api_link_base, $subject, $page->getVersion(), $page->getEntity()->getFolder());
				$md = str_replace(
					$match, 
					sprintf('[%s](%s)', $title, $url),
					$md
				);
			}
		}
		
		// Bare links
		$re = '/
			`?
			\[
				api:(.*?)
			\]
			`?
		/x';
		preg_match_all($re, $md, $links);
		if($links) {
			foreach($links[0] as $i => $match) {
				$subject = $links[1][$i];
				$url = sprintf(self::$api_link_base, $subject, $page->getVersion(), $page->getEntity()->getFolder());
				$md = str_replace(
					$match, 
					sprintf('[%s](%s)', $subject, $url),
					$md
				);
			}
		}
		
		return $md;
	}
	
	/**
	 *
	 */
	public static function rewrite_heading_anchors($md, $page) {
		$re = '/^\#+(.*)/m';	
		$md = preg_replace_callback($re, array('DocumentationParser', '_rewrite_heading_anchors_callback'), $md);
		
		return $md; 
	}
	
	public static function _rewrite_heading_anchors_callback($matches) {
		$heading = $matches[0];
		$headingText = $matches[1];

		if(preg_match('/\{\#.*\}/', $headingText)) return $heading;

		if(!isset(self::$heading_counts[$headingText])) {
			self::$heading_counts[$headingText] = 1; 
		}
		else {
			self::$heading_counts[$headingText]++; 
			$headingText .= "-" . self::$heading_counts[$headingText]; 
		}

		return sprintf("%s {#%s}", preg_replace('/\n/', '', $heading), self::generate_html_id($headingText));
	}
	
	/**
	 * Generate an html element id from a string
	 * 
	 * @return String
	 */ 
	static function generate_html_id($title) {
		$t = $title;
		$t = str_replace('&amp;','-and-',$t);
		$t = str_replace('&','-and-',$t);
		$t = preg_replace('/[^A-Za-z0-9]+/','-',$t);
		$t = preg_replace('/-+/','-',$t);
		$t = trim($t, '-');
		$t = strtolower($t);
				
		return $t;
	}
	
	/**
	 * Resolves all relative links within markdown.
	 * 
	 * @param String $md Markdown content
	 * @param DocumentationPage $page
	 * @param String $baselink
	 * @return String Markdown
	 */
	static function rewrite_relative_links($md, $page, $baselink = null) {
		if(!$baselink) $baselink = $page->getEntity()->getRelativeLink();

		$re = '/
			([^\!]?) # exclude image format
			\[
				(.*?) # link title (non greedy)
			\] 
			\(
				(.*?) # link url (non greedy)
			\)
		/x';
		preg_match_all($re, $md, $matches);
		
		// relative path (relative to module base folder), without the filename.
		// For "sapphire/en/current/topics/templates", this would be "templates"
		$relativePath = dirname($page->getRelativePath());
		if($relativePath == '.') $relativePath = '';
		
		// file base link
		$fileBaseLink = Director::makeRelative(dirname($page->getPath()));
		
		if($matches) {
			foreach($matches[0] as $i => $match) {
				$title = $matches[2][$i];
				$url = $matches[3][$i];
			
				// Don't process API links
				if(preg_match('/^api:/', $url)) continue;
			
				// Don't process absolute links (based on protocol detection)
				$urlParts = parse_url($url);
				if($urlParts && isset($urlParts['scheme'])) continue;

				// for images we need to use the file base path
				if(preg_match('/_images/', $url)) {
					$relativeUrl = Controller::join_links(
						Director::absoluteBaseURL(),
						$fileBaseLink,
						$url
					);
				}
				else {
					// Rewrite public URL
					if(preg_match('/^\//', $url)) {
						// Absolute: Only path to module base
						$relativeUrl = Controller::join_links($baselink, $url);
					} else {
						// Relative: Include path to module base and any folders
						$relativeUrl = Controller::join_links($baselink, $relativePath, $url);
					}
				}
				
				// Resolve relative paths
				while(strpos($relativeUrl, '..') !== FALSE) {
					$relativeUrl = preg_replace('/\w+\/\.\.\//', '', $relativeUrl);
				}
			
				// Replace any double slashes (apart from protocol)
				$relativeUrl = preg_replace('/([^:])\/{2,}/', '$1/', $relativeUrl);
			
				// Replace in original content
				$md = str_replace(
					$match, 
					sprintf('%s[%s](%s)', $matches[1][$i], $title, $relativeUrl),
					$md
				);
			}
		}
		
		return $md;
	}
	
	/**
	 * Strips out the metadata for a page
	 *
	 * @param DocumentationPage
	 */
	public static function retrieve_meta_data(DocumentationPage &$page) {
		if($md = $page->getMarkdown()) {
			$matches = preg_match_all('/
				(?<key>[A-Za-z0-9_-]+): 
				\s*
				(?<value>.*)
			/x', $md, $meta);
		
			if($matches) {
				foreach($meta['key'] as $index => $key) {
					if(isset($meta['value'][$index])) {
						$page->setMetaData($key, $meta['value'][$index]);
					}
				}
			}
		}
	}
}