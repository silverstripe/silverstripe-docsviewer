<?php

/**
 * Wrapper for MarkdownUltra parsing in the template and related functionality for
 * parsing paths and documents
 *
 * @package sapphiredocs
 */

class DocumentationParser {
	
	/**
	 * @var String Rewriting of api links in the format "[api:MyClass]" or "[api:MyClass::$my_property]".
	 */
	static $api_link_base = 'http://api.silverstripe.org/search/lookup/?q=%s&version=%s&module=%s';
		
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
	 * @return HTMLText
	 */
	public static function parse(DocumentationPage $page, $baselink = null) {
		require_once('../sapphiredocs/thirdparty/markdown.php');

			$md = $page->getMarkdown();
			
			// Pre-processing
			$md = self::rewrite_relative_links($md, $page, $baselink);
			$md = self::rewrite_api_links($md, $page);
			
			$html = Markdown($md);

			return DBField::create('HTMLText', $html);
	}
	
	/**
	 * @param String $md
	 * @param DocumentationPage $page
	 * @return String
	 */
	static function rewrite_api_links($md, $page) {
		// Links with titles
		$re = '/
			\[
				(.*?) # link title (non greedy)
			\] 
			\(
				api:(.*?) # link url (non greedy)
			\)
		/x';
		preg_match_all($re, $md, $linksWithTitles);
		if($linksWithTitles) foreach($linksWithTitles[0] as $i => $match) {
			$title = $linksWithTitles[1][$i];
			$subject = $linksWithTitles[2][$i];
			$url = sprintf(self::$api_link_base, $subject, $page->getVersion(), $page->getEntity()->getModuleFolder());
			$md = str_replace(
				$match, 
				sprintf('[%s](%s)', $title, $url),
				$md
			);
		}
		
		// Bare links
		$re = '/
			\[
				api:(.*?)
			\]
		/x';
		preg_match_all($re, $md, $links);
		if($links) foreach($links[0] as $i => $match) {
			$subject = $links[1][$i];
			$url = sprintf(self::$api_link_base, $subject, $page->getVersion(), $page->getEntity()->getModuleFolder());
			$md = str_replace(
				$match, 
				sprintf('[%s](%s)', $subject, $url),
				$md
			);
		}
		
		return $md;
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
			$title = $matches[1][$i];
			$url = $matches[2][$i];
			
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
				sprintf('[%s](%s)', $title, $relativeUrl),
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
		$name = str_ireplace(array('-', '_'), ' ', $name);
		
		// remove extension
		$hasExtension = strpos($name, '.');

		if($hasExtension !== false && $hasExtension > 0) {
			$name = substr($name, 0, $hasExtension);
		}
		
		// convert first letter
		return ucfirst($name);
	}
	
	
	/**
	 * Return the children from a given module. Used for building the tree of the page
	 *
	 * @param String module name
	 *
	 * @return DataObjectSet
	 */
	public static function get_pages_from_folder($folder) {
		$handle = opendir($folder);
		$output = new DataObjectSet();
		
		if($handle) {
			$extensions = DocumentationService::get_valid_extensions();
			$ignore = DocumentationService::get_ignored_files();
			
			while (false !== ($file = readdir($handle))) {	
				if(!in_array($file, $ignore)) {
					$file = strtolower($file);
					
					$clean = ($pos = strrpos($file, '.')) ? substr($file, 0, $pos) : $file;

					$output->push(new ArrayData(array(
						'Title' 	=> self::clean_page_name($file),
						'Filename'	=> $clean,
						'Path'		=> $folder . $file .'/'
					)));
				}
			}
		}
		
		return $output;
	}
}