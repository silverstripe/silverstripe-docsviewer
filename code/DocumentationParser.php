<?php
/**
 * Parser wrapping the Markdown Extra parser (see http://michelf.com/projects/php-markdown/extra/).
 * As it parses files in context 
 * 
 * @todo Abstract code that doesn't require DocumentationPage context into standalone parser
 *  extending the Markdown_Extra class.
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
			$md = $page->getMarkdown();
			
			// Pre-processing
			$md = self::rewrite_image_links($md, $page);
			$md = self::rewrite_relative_links($md, $page, $baselink);
			$md = self::rewrite_api_links($md, $page);
			$md = self::rewrite_heading_anchors($md, $page);
			// $md = self::rewrite_code_blocks($md, $page);
			
			require_once('../sapphiredocs/thirdparty/markdown.php');
			$html = Markdown($md);

			return $html;
	}
	
	/*
	function rewrite_code_blocks($md) {
		$tabwidth = (defined('MARKDOWN_TAB_WIDTH')) ? MARKDOWN_TAB_WIDTH : 4;
		$md = preg_replace_callback('{
				(?:\n\n|\A\n?)
				[ ]*(\{[a-zA-Z]*\})? # lang
				[ ]* \n # Whitespace and newline following marker.
				(	 # $1 = the code block -- one or more lines, starting with a space/tab
				  (?>
					[ ]{'.$tabwidth.'}  # Lines must start with a tab or a tab-width of spaces
					.*\n+
				  )+
				)
				((?=^[ ]{0,'.$tabwidth.'}\S)|\Z)	# Lookahead for non-space at line-start, or end of doc
			}xm',
			array('DocumentationParser', '_do_code_blocks'), $md);

		return $md;
	}
	static function _do_code_blocks($matches) {
		$tabwidth = (defined('MARKDOWN_TAB_WIDTH')) ? MARKDOWN_TAB_WIDTH : 4;
		$codeblock = $matches[2];

		// outdent
		$codeblock = preg_replace('/^(\t|[ ]{1,'.$tabwidth.'})/m', '', $codeblock);
		$codeblock = htmlspecialchars($codeblock, ENT_NOQUOTES);

		# trim leading newlines and trailing newlines
		$codeblock = preg_replace('/\A\n+|\n+\z/', '', $codeblock);

		$codeblock = "<pre><code>$codeblock\n</code></pre>";
		return "\n\n".$this->hashBlock($codeblock)."\n\n";
	}
	*/
	
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
		if($linksWithTitles) foreach($linksWithTitles[0] as $i => $match) {
			$title = $linksWithTitles[1][$i];
			$subject = $linksWithTitles[2][$i];
			$url = sprintf(self::$api_link_base, $subject, $page->getVersion(), $page->getEntity()->getModuleFolder());
			$md = str_replace(
				$match, 
				sprintf('<code>[%s](%s)</code>', $title, $url),
				$md
			);
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
		if($links) foreach($links[0] as $i => $match) {
			$subject = $links[1][$i];
			$url = sprintf(self::$api_link_base, $subject, $page->getVersion(), $page->getEntity()->getModuleFolder());
			$md = str_replace(
				$match, 
				sprintf('<code>[%s](%s)</code>', $subject, $url),
				$md
			);
		}
		
		return $md;
	}
	
	/**
	 *
	 */
	static function rewrite_heading_anchors($md, $page) {
		$re = '/
			\#+(.*)
		/x';	
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
	static function rewrite_relative_links($md, $page, $baselink) {
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
		
		// relative path (to module base folder), without the filename
		$relativePath = dirname($page->getRelativePath());
		if($relativePath == '.') $relativePath = '';
		
		if($matches) foreach($matches[0] as $i => $match) {
			$title = $matches[2][$i];
			$url = $matches[3][$i];
			
			// Don't process API links
			if(preg_match('/^api:/', $url)) continue;
			
			// Don't process absolute links (based on protocol detection)
			$urlParts = parse_url($url);
			if($urlParts && isset($urlParts['scheme'])) continue;
			
			// Rewrite URL (relative or absolute)
			if(preg_match('/^\//', $url)) {
				$relativeUrl = $baselink . $url;
			} else {
				$relativeUrl = $baselink . '/' . $relativePath . '/' . $url;
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
		
		return $md;
	}
	
	/**
	 * Find a documentation page given a path and a file name. It ignores the extensions
	 * and simply compares the title.
	 *
	 * Name may also be a path /install/foo/bar.
	 *
	 * @param String $modulePath Absolute path to the entity
	 * @param Array $path path to the file in the entity
	 *
	 * @return String|false - File path
	 */
	static function find_page($modulePath, $path) {	
		return self::find_page_recursive($modulePath, $path);
	}
	
	/**
	 * Recursive function for finding the goal
	 */
	private static function find_page_recursive($base, $goal) {
		$handle = opendir($base);

		$name = strtolower(array_shift($goal));
		
		if(!$name) $name = 'index';
		
		if($handle) {
			$extensions = DocumentationService::get_valid_extensions();

			while (false !== ($file = readdir($handle))) {
				if(in_array($file, DocumentationService::get_valid_extensions())) continue;
				
				$formatted = strtolower($file);

				// if the name has a . then take the substr 
				$formatted = ($pos = strrpos($formatted, '.')) ? substr($formatted, 0, $pos) : $formatted;
				$name = ($dot = strrpos($formatted, '.')) ? substr($name, 0, $dot) : $name;
				
				// the folder is the one that we are looking for.
				if($name == $formatted) {

					if(is_dir($base . $file)) {
						// if this is a directory check that there is any more states to get
						// to in the goal. If none then what we want is the 'index.md' file
						if(count($goal) > 0) {
							return self::find_page_recursive($base . $file, $goal);
						}
						else {
							// recurse but check for an index.md file next time around
							return self::find_page_recursive($base . $file, array('index'));
						}
					}
					else {
						// goal state. End of recursion
						$result = $base .'/'. $file;

						return $result;
					}
				}
			}
		}
		
		closedir($handle);
	}
	
	/**
	 * String helper for cleaning a file name to a readable version. 
	 *
	 * @param String $name to convert
	 *
	 * @return String $name output
	 */
	public static function clean_page_name($name) {
		// remove dashs and _
		$name = str_replace(array('-', '_'), ' ', $name);
		
		// remove extension
		$hasExtension = strrpos($name, '.');

		if($hasExtension !== false && $hasExtension > 0) {
			$name = substr($name, 0, $hasExtension);
		}
		
		// convert first letter
		return ucfirst(trim($name));
	}
	
	
	/**
	 * Return the children from a given module sorted by Title using natural ordering. 
	 * It is used for building the tree of the page.
	 *
	 * @param String module name
	 * @param bool Recursive search
	 * @param DataObjectSet set of pages matched so far
	 *
	 * @throws Exception
	 * @return DataObjectSet
	 */
	public static function get_pages_from_folder($folder, $recursive = false, &$pages = false) {
		$output = new DataObjectSet();
		
		if(!$pages) $pages = new DataObjectSet();

		if(!is_dir($folder)) throw new Exception(sprintf('%s is not a folder', $folder));
		
		$handle = opendir($folder);
		
		if($handle) {
			$extensions = DocumentationService::get_valid_extensions();
			$ignore = DocumentationService::get_ignored_files();
			$files = array();
			
			while (false !== ($file = readdir($handle))) {	
				if(!in_array($file, $ignore)) {
					$file = trim(strtolower($file), '/');
					$path = rtrim($folder, '/') . '/'. $file;
					
					if($recursive && is_dir($path)) {
						self::get_pages_from_folder($path, true, $pages);
					} 
					else {
						$files[] = $file;
					}
				}
			}
			
			natsort($files);
			
			if($files) {
				foreach($files as $file) {
					$clean = ($pos = strrpos($file, '.')) ? substr($file, 0, $pos) : $file;
					$path = rtrim($folder, '/') . '/'. $file;
					
					$pages->push(new ArrayData(array(
						'Title' 	=> self::clean_page_name($file),
						'Filename'	=> $clean,
						'Path'		=> $path
					)));
				}
			}
			
		}

		closedir($handle);
		
		return $pages;
	}
}