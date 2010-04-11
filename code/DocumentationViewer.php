<?php

/**
 * Documentation Handler.
 *
 * Reads the bundled markdown files from docs/ folders and displays output in 
 * a formatted page
 *
 * @todo Tidy up template / styling
 *  
 * @package sapphiredocs
 */

class DocumentationViewer extends Controller {
	
	static $url_handlers = array(
		'' => 'index',
		'$Module/$Class' => 'parsePage'
	);
	
	/**
	 * An array of files to ignore from the listing
	 *
	 * @var array
	 */
	static $ignored_files = array('.', '..', '.DS_Store', '.svn', '.git', 'assets');
	
	/**
	 * Documentation Home
	 *
	 * Displays a welcome message as well as links to the sapphire sections and the
	 * installed modules
	 */
	function index() {
		$this->writeHeader();
		$base = Director::baseURL();
		
		$readme = ($this->readmeExists('sapphire')) ? "<a href=''>Read Me</a>" : false;
		
		// write the main content (sapphire) on the left
		echo "<div id='Home'><div id='LeftColumn'><div class='box'>";
		echo "<h2>sapphire $readme</h2>";
		
		$this->generateNestedTree('sapphire');

		echo "</div></div>";
		echo "<div id='RightColumn'>";
		
		$modules = scandir(BASE_PATH);
		
		// modules which are not documented
		$undocumented = array();
		
		// generate a list of module documentation (not core)
		if($modules) {
			foreach($modules as $module) {
				// skip sapphire since this is on the left
				$ignored_modules = array('sapphire', 'assets', 'themes');
				
				if(!in_array($module, $ignored_modules) && !in_array($module, self::$ignored_files) && is_dir(BASE_PATH .'/'. $module)) {
					
					// see if docs folder is present
					$subfolders = scandir(BASE_PATH .'/'. $module);
					
					if($subfolders && in_array('docs', $subfolders)) {
						$readme = ($filename = $this->readmeExists($module)) ? "<a href='todo'>Read Me</a>" : false;
						echo "<div class='box'><h2>". $module .' '. $readme."</h2>";
						$this->generateNestedTree($module);
						echo "</div>";
					}
					else {
						$undocumented[] = $module;
					}

				}
			}
		}
		// for each of the modules. Display them here
		
		echo "</div></div><div class='undocumentedModules'>";
		
		if($undocumented) {
			echo "<p>Undocumented Modules: ";
			echo implode(', ', $undocumented);
		}
		
		
		$this->writeFooter();
	}
	
	
	/**
	 * Parse a given individual markdown page
	 *
	 * @param HTTPRequest
	 */
	function parsePage($request) {
		
		require_once('../sapphiredocs/thirdparty/markdown.php');
		
		$class = $request->param('Class');
		$module = $request->param('Module');
		
		$this->writeHeader($class, $module);

		$base = Director::baseURL();
		
		// find page
		$path = BASE_PATH . '/'. $module .'/docs';
		
		echo "<div id='LeftColumn'><div class='box'>";
		if($page = $this->findPage($path, $class)) {
			echo Markdown(file_get_contents($page));
		}
		else {
			echo "<p>Documentation Page Not Found</p>";
		}
		
		echo "</div></div> <div id='RightColumn'></div>";
		
		echo '<script type="text/javascript" src="'. Director::absoluteBaseURL(). 'sapphire/thirdparty/jquery/jquery.js"></script>
		<script type="text/javascript" src="'. Director::absoluteBaseURL() .'sapphiredocs/javascript/DocumentationViewer.js"></script>
		';
		
		$this->writeFooter();
	}
	
	/**
	 * @todo - This is nasty, ripped out of DebugView.
	 */
	function writeHeader($class = "", $module = "") {
		$breadcrumbs = false;
		if($module) {
			$parts = array();	
			$parts[] = "<a href='dev/docs/'>Documentation Home</a>";
			$parts[] = "<a href='dev/docs/$module'>$module</a>";
		
			if($class) $parts[] = $this->formatStringForTitle($class);
		
			$breadcrumbs = implode("&nbsp;&raquo;&nbsp;", $parts);
			$breadcrumbs = '<p class="breadcrumbs">'. $breadcrumbs .'</p>';
		}

		echo '<!DOCTYPE html>
				<html>
					<head>
						<base href="'. Director::absoluteBaseURL() .'>	"
						<title>' . htmlentities($_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']) . '</title>
						<link rel="stylesheet" href="sapphiredocs/css/DocumentationViewer.css" type="text/css">
					</head>
					<body>
					<div id="Container">
						<div id="Header">
							<h1><a href="dev/docs/">SilverStripe Documentation</a></h1>
							'.$breadcrumbs.'
						</div>
					';
	}
	
	function writeFooter() {
		echo "</div></body></html>";		
	}
	
	/**
	 * Work out if a module contains a readme
	 *
	 * @param String - Module to check
	 * @return bool|String - of path
	 */
	private function readmeExists($module) {
		$children = scandir(BASE_PATH.'/'.$module);
		
		$readmeOptions = array('readme', 'readme.md', 'readme.txt');
		
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
	 * @todo Currently this only works on pages - eg if you go /dev/docs/Forms/ it won't show the 
	 * 			overall forms page
	 *
	 * @param String - Name of Module
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

					if(is_dir($newpath)) {
						// keep looking down the tree
						return $this->findPage($newpath, $name);
					}

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
	private function recursivelyGenerateTree($path, $module) {
		echo "<ul class='tree'>";			
		$handle = opendir($path);
		
		if($handle) {
			while (false !== ($file = readdir($handle))) {
				if(!in_array($file, self::$ignored_files)) {	
					$newPath = $path.'/'.$file;

					// if the file is a dir nest the pages
					if(is_dir($newPath)) {
						
						// if this has a number
						echo "<li class='folder'>". $this->formatStringForTitle($file) ."</li>";
						$this->recursivelyGenerateTree($newPath, $module);
						
					}
					else {	
						$offset = (strpos($file,'-') > 0) ? strpos($file,'-') + 1 : 0;
						
						$file  = substr(str_ireplace('.md', '', $file), $offset);
						
						echo "<li class='page'><a href='". Director::absoluteBaseURL() . 'dev/docs/' . $module .'/'. $file . "'>". $this->formatStringForTitle($file) ."</a></li>";
					}
				}
		    }
		}
		closedir($handle);
		echo "</ul>";

	}
	
	/**
	 * Take a file name and generate a 'nice' title for it
	 *
	 * @todo find a nicer way of removing the numbers.
	 *
	 * @param String
	 * @return String
	 */
	private function formatStringForTitle($title) {
		// remove numbers if used. 
		if(substr($title, 2, 1) == '-') $title = substr($title, 3);
		
		// change - to spaces
		$title = str_ireplace('-', ' ', $title);
		
		// remove extension 
		$title = str_ireplace('.md', '', $title);
		
		return $title;
	}
}