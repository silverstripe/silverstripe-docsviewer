<?php
/**
 * Parser wrapping the Markdown Extra parser (see http://michelf.com/projects/php-markdown/extra/). 
 *
 * @package sapphiredocs
 */
class DocumentationParser {

	/**
	 * @var String Rewriting of api links in the format "[api:MyClass]" or "[api:MyClass::$my_property]".
	 */
	static $api_link_base = 'http://api.silverstripe.org/search/lookup/?q=%s&version=%s&module=%s';
	
	static $heading_counts = array();
	
	/**
	 * Parse a given path to the documentation for a file. Performs a case insensitive 
	 * lookup on the file system. Automatically appends the file extension to one of the markdown
	 * extensions as well so /install/ in a web browser will match /install.md or /INSTALL.md
	 * 
	 * Filepath: /var/www/myproject/src/cms/en/folder/subfolder/page.md
	 * URL: http://myhost/mywebroot/dev/docs/2.4/cms/en/folder/subfolder/page
	 * Webroot: http://myhost/mywebroot/
	 * Baselink: dev/docs/2.4/cms/en/
	 * Pathparts: folder/subfolder/page
	 * 
	 * @param DocumentationPage $page
	 * @param String $baselink Link relative to webroot, up until the "root" of the module.  
	 *  Necessary to rewrite relative links
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
		$md = self::rewrite_code_blocks($md, $page);
	
		require_once(BASE_PATH . '/sapphiredocs/thirdparty/markdown/markdown.php');
		
		$parser = new MarkdownExtra_Parser();
		$parser->no_markup = true;
		
		return $parser->transform($md);
	}
	
	function rewrite_code_blocks($md) {
		$started = false;
		$inner = false;
		
		$lines = split("\n", $md);
		foreach($lines as $i => $line) {
			if(!$started && preg_match('/^\t*:::\s*(.*)/', $line, $matches)) {
				// first line with custom formatting
				$started = true;
				$lines[$i] = sprintf('<pre class="brush: %s">', $matches[1]);
			} elseif(preg_match('/^\t(.*)/', $line, $matches)) {
				// inner line of ::: block, or first line of standard markdown code block
				// regex removes first tab (any following tabs are part of the code).
				$lines[$i] = ($started) ? '' : '<pre>' . "\n";
				$lines[$i] .= htmlentities($matches[1], ENT_COMPAT, 'UTF-8');
				$inner = true;
				$started = true;
			} elseif($started && $inner) {
				// remove any previous blank lines
				$j = $i-1;
				while(isset($lines[$j]) && preg_match('/^[\t\s]*$/', $lines[$j])) {
					unset($lines[$j]);
					$j--;
				}
				
				// last line, close pre
				$lines[$i] = '</pre>' . "\n\n" . $line;
				
				// reset state
				$started = $inner = false;
			}
		}
		
		return join("\n", $lines);

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
				$url = sprintf(self::$api_link_base, $subject, $page->getVersion(), $page->getEntity()->getModuleFolder());
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
				$url = sprintf(self::$api_link_base, $subject, $page->getVersion(), $page->getEntity()->getModuleFolder());
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
	static function rewrite_heading_anchors($md, $page) {
		$re = '/^\#+(.*)/m';	
		$md = preg_replace_callback($re, array('DocumentationParser', '_rewrite_heading_anchors_callback'), $md);
		
		return $md; 
	}
	
	static function _rewrite_heading_anchors_callback($matches) {
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
		$t = ereg_replace('[^A-Za-z0-9]+','-',$t);
		$t = ereg_replace('-+','-',$t);
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
		
		if($matches) {
			foreach($matches[0] as $i => $match) {
				$title = $matches[2][$i];
				$url = $matches[3][$i];
			
				// Don't process API links
				if(preg_match('/^api:/', $url)) continue;
			
				// Don't process absolute links (based on protocol detection)
				$urlParts = parse_url($url);
				if($urlParts && isset($urlParts['scheme'])) continue;

				// Rewrite URL
				if(preg_match('/^\//', $url)) {
					// Absolute: Only path to module base
					$relativeUrl = Controller::join_links($baselink, $url);
				} else {
					// Relative: Include path to module base and any folders
					$relativeUrl = Controller::join_links($baselink, $relativePath, $url);
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