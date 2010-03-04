<?php

/**
 * Documentation Handler.
 *
 * Reads the bundled markdown files from doc/ folders and displays output in 
 * a formatted page
 *
 * @todo 
 * 		- We should move away from relying on DebugViewer. We could even use the actual
 *			standard template system for the layout
 *
 *		- Abstract html / css out from DebugViewer
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
	static $ignored_files = array('.', '..', '.DS_Store', '.svn', '.git');
	
	/**
	 * Documentation Home
	 *
	 * Displays a welcome message as well as links to the sapphire sections and the
	 * installed modules
	 */
	function index() {
		$this->writeHeader();
		$base = Director::baseURL();
		
		// write the main content (sapphire) on the left
		echo "<div id='LeftColumn'><div class='box'>";
		echo "<h2>Sapphire</h2>";
		
		$this->generateNestedTree('sapphire');

		echo "</div></div>";
		echo "<div id='RightColumn'>";
		
		$modules = scandir(BASE_PATH);
		
		// generate a list of module documentation (not core)
		if($modules) {
			foreach($modules as $module) {
				// skip sapphire since this is on the left
				$ignored_modules = array('sapphire', 'assets', 'themes');
				
				if(!in_array($module, $ignored_modules) && !in_array($module, self::$ignored_files) && is_dir(BASE_PATH .'/'. $module)) {
					echo "<div class='box'><h2>". $module ."</h2>";
					
					// see if docs folder is present
					$subfolders = scandir(BASE_PATH .'/'. $module);
					
					if($subfolders && in_array('doc', $subfolders)) {
						$this->generateNestedTree($module);
					}
					else {
						echo "<p class='noDocs'>No Documentation For Module</p>";
					}
					echo "</div>";
				}
			}
		}
		// for each of the modules. Display them here
		
		echo "</div>";
		
		$this->writeFooter();
	}
	
	/**
	 * @todo - This is nasty, ripped out of DebugView.
	 */
	function writeHeader() {
		echo '<!DOCTYPE html>
				<html>
					<head>
						<base href="'. Director::absoluteBaseURL() .'>	"
						<title>' . htmlentities($_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI']) . '</title>
						<link rel="stylesheet" href="sapphiredocs/css/DocumentationViewer.css" type="text/css">
					</head>
					<body>
					<div class="header">SilverStripe</div>
					<div class="info">
					<h1>SilverStripe Documentation</h1>

					<p class="breadcrumbs"><a href="dev/docs/">docs</a></p></div>';
	}
	
	function writeFooter() {
		echo "</body></html>";		
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
		
		if(!stripos($class, '.md')) $class .= '.md';
		
		$this->writeHeader();

		$base = Director::baseURL();
		
		// find page
		$path = BASE_PATH . '/'. $module .'/doc/';
		
		echo "<div id='LeftColumn'><div class='box'>";
		if($page = $this->findPage($path, $class)) {
			echo Markdown(file_get_contents($page));
		}
		else {
			echo "<p>Documentation Page Not Found</p>";
		}
		
		echo "</div></div> <div id='RightColumn'></div>";
		
		echo '<script type="text/javascript" src="'. Director::absoluteBaseURL(). 'sapphire/thirdparty/jquery/jquery.min.js"></script>
		<script type="text/javascript" src="'. Director::absoluteBaseURL() .'sapphiredocs/javascript/DocumentationViewer.js"></script>
		';
		
		$this->writeFooter();
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
				if(!in_array($file, self::$ignored_files)) {
					if(is_dir($path.$file)) {
						// keep looking down the tree
						return $this->findPage($path.$file, $name);
					}
					elseif(strtolower($file) == strtolower($name)) {
						return $path .'/'. $file;
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
		$path = BASE_PATH . '/'. $module .'/doc/';
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
					$newPath = $path.$file;
				
					// if the file is a dir nest the pages
					if(is_dir($newPath)) {
						
						// if this has a number
						echo "<li class='level'>". $this->formatStringForTitle($file) ."</li>";
						$this->recursivelyGenerateTree($newPath, $module);
						
					}
					else {	
						$file  = str_ireplace('.md', '', $file);
						
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
		
		return $title;
	}
}