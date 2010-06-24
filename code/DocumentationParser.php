<?php

/**
 * Wrapper for MarkdownUltra parsing in the template and related functionality for
 * parsing paths and documents
 *
 * @package sapphiredocs
 */

class DocumentationParser {
	
	/**
	 * Parse a given path to the documentation for a file. Performs a case insensitive 
	 * lookup on the file system. Automatically appends the file extension to one of the markdown
	 * extensions as well so /install/ in a web browser will match /install.md or /INSTALL.md
	 *
	 * @param String $module path to a module
	 * @param Array path of urls. Should be folders, last one is a page
	 *
	 * @return HTMLText
	 */
	public static function parse($module, $path) {
		require_once('../sapphiredocs/thirdparty/markdown.php');

		if($content = self::find_page($module, $path)) {
			$content = Markdown(file_get_contents($content));

			return DBField::create('HTMLText', $content);
		}
		
		return false;
	}
	
	/**
	 * Find a documentation page given a path and a file name. It ignores the extensions
	 * and simply compares the title.
	 *
	 * Name may also be a path /install/foo/bar.
	 *
	 * @param String $entity path to the entity
	 * @param Array $path path to the file in the entity
	 *
	 * @return String|false - File path
	 */
	private static function find_page($entity, $path) {	
		return self::find_page_recursive($entity, $path);
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