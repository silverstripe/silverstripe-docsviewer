<?php

/**
 * Documentation Viewer.
 *
 * Reads the bundled markdown files from docs/ folders and displays output in a formatted page at /dev/docs/.
 * For more documentation on how to use this class see the documentation online in /dev/docs/ or in the
 * /sapphiredocs/docs folder
 *
 * @author Will Rossiter <will@silverstripe.com>
 * @package sapphiredocs
 */

class DocumentationViewer extends Controller {
	
	static $url_handlers = array(
		'' => 'index',
		'$Module/$Page/$OtherPage' => 'parse'
	);
	
	/**
	 * An array of files to ignore from the listing
	 *
	 * @var array
	 */
	static $ignored_files = array('.', '..', '.DS_Store', '.svn', '.git', 'assets', 'themes');
	
	/**
	 * An array of case insenstive values to use as readmes
	 *
	 * @var array
	 */
	static $readme_files = array('readme', 'readme.md', 'readme.txt', 'readme.markdown');


	/**
	 * Main documentation page
	 */
	function index() {
		return $this->customise(array(
			'DocumentedModules' => $this->DocumentedModules()
		))->renderWith(array('DocumentationViewer_index', 'DocumentationViewer'));
	}
	
	/**
	 * Individual documentation page
	 *
	 * @param HTTPRequest 
	 */
	function parse($request) {	
		require_once('../sapphiredocs/thirdparty/markdown.php');
		
		$page =  $request->param('Page');
		$module = $request->param('Module');
		
		$path = BASE_PATH .'/'. $module .'/docs';
		
		if($content = $this->findPage($path, $page)) {
			$title = $page;
			$content = Markdown(file_get_contents($content));
		}
		else {
			$title = 'Page not Found';
			$content = false;
		}
		
		return $this->customise(array(
			'Title' 	=> $title,
			'Content' 	=> $content
		))->renderWith('DocumentationViewer');
	}
	
	/**
	 * Returns an array of the modules installed. Currently to determine if a module is
	 * installed look at all the folders and check is a _config file.
	 *
	 * @return array
	 */
	function getModules() {
		$modules = scandir(BASE_PATH);
		
		if($modules) {
			foreach($modules as $key => $module) {
				if(!is_dir(BASE_PATH .'/'. $module) || in_array($module, self::$ignored_files, true) || !file_exists(BASE_PATH . '/'. $module .'/_config.php')) {
					unset($modules[$key]);
				}
			}
		}

		return $modules;
	}

	
	/**
	 * Generate a set of modules for the home page
	 *
	 * @return DataObjectSet
	 */
	function DocumentedModules() {
		
		$modules = new DataObjectSet();
		
		// include sapphire first
		$modules->push(new ArrayData(array(
			'Title' 	=> 'sapphire',
			'Content'	=> $this->generateNestedTree('sapphire'),
			'Readme' 	=> $this->readmeExists('sapphire')
		)));
		
		$extra_ignore = array('sapphire');
		
		foreach($this->getModules() as $module) {
			if(!in_array($module, $extra_ignore) && $this->moduleHasDocs($module)) {
				$modules->push(new ArrayData(array(
					'Title'		=> $module,
					'Content'	=> $this->generateNestedTree($module),
					'Readme'	=> $this->readmeExists($module)
				)));
			}
		}
		
		return $modules;
	}
	
	/**
	 * Generate a list of modules (folder which has a _config) which have no /docs/ folder
	 *
	 * @return DataObjectSet
	 */
	function UndocumentedModules() {
		$modules = $this->getModules();
		$undocumented = array();
		
		if($modules) {
			foreach($modules as $module) {
				if(!$this->moduleHasDocs($module)) $undocumented[] = $module;
			}
		}
		
		return implode(',', $undocumented);
	}
	
	/**
	 * Helper function to determine whether a module has documentation
	 *
	 * @param String - Module folder name
	 * @return bool - Has docs folder
	 */
	function moduleHasDocs($module) {
		return is_dir(BASE_PATH .'/'. $module .'/docs/');
	}
	
	
	/**
	 * Work out if a module contains a readme
	 *
	 * @param String - Module to check
	 * @return bool|String - of path
	 */
	private function readmeExists($module) {
		$children = scandir(BASE_PATH.'/'.$module);
		
		$readmeOptions = self::$readme_files;
		
		if($children) {
			foreach($children as $i => $file) {
				if(in_array(strtolower($file), $readmeOptions)) return $file;
			}
		}
		
		return false;
	}

	
	/**
	 * Find a documentation page within a given module. 
	 *
	 * @param String - Path to Module
	 * @param String - Name of doc page
	 *
	 * @return String|false - File path
	 */
	private function findPage($path, $name) {

		// open docs folder
		$handle = opendir($path);

		if($handle) {
			while (false !== ($file = readdir($handle))) {
				$newpath = $path .'/'. $file;

				if(!in_array($file, self::$ignored_files)) {

					if(is_dir($newpath)) return $this->findPage($newpath, $name);

					elseif(strtolower($this->formatStringForTitle($file)) == strtolower($name)) {
						return $newpath;
					}
				}
			}
		}

		return false;
	}
	
	/**
	 * Generate a nested tree for a given folder via recursion 
	 *
	 * @param String - module to generate
	 */
	private function generateNestedTree($module) {
		$path = BASE_PATH . '/'. $module .'/docs/';
		
		return (is_dir($path)) ? $this->recursivelyGenerateTree($path, $module) : false;
	}
	
	/**
	 * Recursive method to generate the tree
	 *
	 * @param String - folder to work through 
	 * @param String - module we're working through
	 */
	private function recursivelyGenerateTree($path, $module, $output = '') {
		$output .= "<ul class='tree'>";			
		$handle = opendir($path);
		
		if($handle) {
			while (false !== ($file = readdir($handle))) {
				if(!in_array($file, self::$ignored_files)) {	
					$newPath = $path.'/'.$file;

					// if the file is a dir nest the pages
					if(is_dir($newPath)) {
						
						// if this has a number
						$output .= "<li class='folder'>". $this->formatStringForTitle($file) ."</li>";
						
						$output = $this->recursivelyGenerateTree($newPath, $module, $output);
						
					}
					else {	
						$offset = (strpos($file,'-') > 0) ? strpos($file,'-') + 1 : 0;
						
						$file  = substr(str_ireplace('.md', '', $file), $offset);
						
						$output .= "<li class='page'><a href='". Director::absoluteBaseURL() . 'dev/docs/' . $module .'/'. $file . "'>". $this->formatStringForTitle($file) ."</a></li>";
					}
				}
		    }
		}

		closedir($handle);
		$output .= "</ul>";

		return $output;
	}
	
	/**
	 * Take a file name and generate a 'nice' title for it.
	 *
	 * example. 01-Getting-Started -> Getting Started
	 *
	 * @param String - raw title
	 * @return String - nicely formatted one
	 */
	private function formatStringForTitle($title) {
		// remove numbers if used. 
		if(substr($title, 2, 1) == '-') $title = substr($title, 3);
		
		// change - to spaces
		$title = str_ireplace('-', ' ', $title);
		
		// remove extension 
		$title = str_ireplace(array('.md', '.markdown'), '', $title);
		
		return $title;
	}
	
}