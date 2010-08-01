<?php

/**
 * Documentation Viewer.
 *
 * Reads the bundled markdown files from documentation folders and displays the output (either
 * via markdown or plain text)
 *
 * For more documentation on how to use this class see the documentation in /sapphiredocs/docs folder
 *
 * To view the documentation in the browser use:
 * 	
 * 	http://yoursite.com/dev/docs/ Which is locked to ADMIN only
 *
 * @todo 	- Add ability to have docs on the front end as the main site.
 * 			- Fix Language Selector (enabling it troubles the handleRequest when submitting)
 *			- SS_HTTPRequest when we ask for 10 params it gives us 10. Could be 10 blank ones.
 *				It would mean I could save alot of code if it only gave back an array of size X
 * 				up to a maximum of 10...
 *
 * @package sapphiredocs
 */

class DocumentationViewer extends Controller {

	static $allowed_actions = array(
		'LanguageForm',
		'doLanguageForm',
		'handleRequest',
	);
	
	static $casting = array(
		'Version'			=> 'Text',
		'Lang'				=> 'Text',
		'Module' 			=> 'Text',
		'LanguageTitle'		=> 'Text'
	);	
	
	/**
	 * @var String Same as the routing pattern set through Director::addRules().
	 */
	protected static $link_base = 'dev/docs/';
	
	/**
	 * @var String|array Optional permssion check
	 */
	static $check_permission = 'ADMIN';
	
	function init() {
		parent::init();
		
		$canAccess = (Director::isDev() || Director::is_cli() || !self::$check_permission || Permission::check(self::$check_permission));

		if(!$canAccess) return Security::permissionFailure($this);
	}

	/**
	 * Overloaded to avoid "action doesnt exist" errors - all URL parts in this
	 * controller are virtual and handled through handleRequest(), not controller methods.
	 */
	public function handleAction($request) {
		try{
			$response = parent::handleAction($request);
		} catch(SS_HTTPResponse_Exception $e) {
			if(strpos($e->getMessage(), 'does not exist') !== FALSE) {
				return $this;
			} else {
				throw $e;
			}
		}
		
		return $response;
	}
	
	/**
	 * Handle the url parsing for the documentation. In order to make this
	 * user friendly this does some tricky things..
	 *
	 * The urls which should work
	 * / - index page
	 * /en/sapphire - the index page of sapphire (shows versions)
	 * /2.4/en/sapphire - the docs for 2.4 sapphire.
	 * /2.4/en/sapphire/installation/
	 *
	 * @return SS_HTTPResponse
	 */
	public function handleRequest(SS_HTTPRequest $request) {
		// Workaround for root routing, e.g. Director::addRules(10, array('$Action' => 'DocumentationViewer'))
		$this->Version = ($request->param('Action')) ? $request->param('Action') : $request->shift();		
		$this->Lang = $request->shift();
		$this->ModuleName = $request->shift();
		$this->Remaining = $request->shift(10);
	
		DocumentationService::load_automatic_registration();
	
		if(isset($this->Version)) {
			// check to see if its a valid version. If its not a float then its not actually a version 
			// its actually a language and it needs to change. So this means we support 2 structures
			// /2.4/en/sapphire/page and
			// /en/sapphire/page which is a link to the latest one
		
			if(!is_numeric($this->Version) && $this->Version != 'current') {
				array_unshift($this->Remaining, $this->ModuleName);
				
				// not numeric so /en/sapphire/folder/page
				if(isset($this->Lang) && $this->Lang)
					$this->ModuleName = $this->Lang;
			
				$this->Lang = $this->Version;
				$this->Version = null;
			}
			else {
				// if(!DocumentationService::is_registered_version($this->Version)) {
				//	$this->httpError(404, 'The requested version could not be found.');
				// }
			}
		}
		if(isset($this->Lang)) {
			// check to see if its a valid language
			// if(!DocumentationService::is_registered_language($this->Lang)) {	
			//	$this->httpError(404, 'The requested language could not be found.');
			// }
		}
		else {
			$this->Lang = 'en';
		}

		// 'current' version mapping
		$module = DocumentationService::is_registered_module($this->ModuleName, null, $this->Lang);
		if($this->Version && $module) {
			$current = $module->getCurrentVersion();
			if($this->Version == 'current') {
				$this->Version = $current;
			} else {
				if($current == $this->Version) {
					$this->Version = 'current';
					$link = $this->Link($this->Remaining);
					$this->response = new SS_HTTPResponse();
					$this->redirect($link, 301); // permanent redirect
					return $this->response;
				}
			}
			
		}
		
		
		return parent::handleRequest($request);
	}
	
	/**
	 * Custom templates for each of the sections. 
	 */
	function getViewer($action) {
		// count the number of parameters after the language, version are taken
		// into account. This automatically includes ' ' so all the counts
		// are 1 more than what you would expect
		if($this->ModuleName || $this->Remaining) {

			$paramCount = count($this->Remaining);
			
			if($paramCount == 0) {
				return parent::getViewer('folder');
			}
			else if($module = $this->getModule()) {
				$params = $this->Remaining;
				
				$path = implode('/', array_unique($params));
				
				if(is_dir($module->getPath() . $path)) return parent::getViewer('folder');
			}
		}
		else {
			return parent::getViewer('home');
		}
		
		return parent::getViewer($action);
	}
	
	/**
	 * Return all the available languages. Optionally the languages which are
	 * available for a given module
	 *
	 * @param String - The name of the module
	 * @return DataObjectSet
	 */
	function getLanguages($module = false) {
		$output = new DataObjectSet();
		
		if($module) {
			// lookup the module for the available languages
			
			// @todo
		}
		else {
			$languages = DocumentationService::get_registered_languages();

			if($languages) {
				foreach($languages as $key => $lang) {
	
					if(stripos($_SERVER['REQUEST_URI'], '/'. $this->Lang .'/') === false) {
						// no language is in the URL currently. It needs to insert the language 
						// into the url like /sapphire/install to /en/sapphire/install
						//
						// @todo
					} 
					
					$link = str_ireplace('/'.$this->Lang .'/', '/'. $lang .'/', $_SERVER['REQUEST_URI']);

					$output->push(new ArrayData(array(
						'Title' => $lang,
						'Link' => $link
					)));
				}
			}
		}
			
		return $output;
	}

	/**
	 * Get all the versions loaded into the module. If the project is only displaying from 
	 * the filesystem then they are loaded under the 'Current' namespace.
	 *
	 * @todo Only show 'core' versions (2.3, 2.4) versions of the modules are going
	 *		to spam this
	 *
	 * @param String $module name of module to limit it to eg sapphire
	 * @return DataObjectSet
	 */
	function getVersions($module = false) {
		$versions = DocumentationService::get_registered_versions($module);
		$output = new DataObjectSet();
		
		foreach($versions as $key => $version) {
			// work out the link to this version of the documentation. 
			// 
			// @todo Keep the user on their given page rather than redirecting to module.
			// @todo Get links working
			$linkingMode = ($this->Version == $version) ? 'current' : 'link';
			
			if(!$version) $version = 'Current';
			$major = (in_array($version, DocumentationService::get_major_versions())) ? true : false;
			
			$output->push(new ArrayData(array(
				'Title' => $version,
				'Link' => $_SERVER['REQUEST_URI'],
				'LinkingMode' => $linkingMode,
				'MajorRelease' => $major
			)));
		}
		
		return $output;
	}
	
	/**
	 * Generate the module which are to be documented. It filters
	 * the list based on the current head version. It displays the contents
	 * from the index.md file on the page to use.
	 *
	 * @return DataObject
	 */ 
	function getModules($version = false, $lang = false) {
		if(!$version) $version = $this->Version;
		if(!$lang) $lang = $this->Lang;
		
		$modules = DocumentationService::get_registered_modules($version, $lang);
		$output = new DataObjectSet();

		if($modules) {
			foreach($modules as $module) {
				$absFilepath = $module->getPath() . '/index.md';
				$relativeFilePath = str_replace($module->getPath(), '', $absFilepath);
				if(file_exists($absFilepath)) {
					$page = new DocumentationPage(
						$relativeFilePath,
						$module,
						$this->Lang,
						$this->Version
					);
					$content = DocumentationParser::parse($page, $this->Link(array_slice($this->Remaining, -1, -1)));
				} else {
					$content = '';
				}
				
				// build the dataset. Load the $Content from an index.md
				$output->push(new ArrayData(array(
					'Title' 	=> $module->getTitle(),
					'Code'		=> $module,
					'Content' 	=> DBField::create("HTMLText", $content)
				)));
			}
		}

		return $output;
	}
	
	/**
	 * Get the currently accessed entity from the site.
	 *
	 * @return false|DocumentationEntity
	 */
	function getModule() {
		if($this->ModuleName) {
			return DocumentationService::is_registered_module($this->ModuleName, $this->Version, $this->Lang);
		}

		return false;
	}
	
	/**
	 * @return DocumentationPage
	 */
	function getPage() {
		$module = $this->getModule();
		if(!$module) return false;
		
		$absFilepath = DocumentationParser::find_page($module->getPath(), $this->Remaining);
		if($absFilepath) {
			$relativeFilePath = str_replace($module->getPath(), '', $absFilepath);
			return new DocumentationPage(
				$relativeFilePath,
				$module,
				$this->Lang,
				$this->Version
			);
		} else {
			return false;
		}
	}
	
	/**
	 * Get the related pages to this module and the children to those pages
	 *
	 * @todo this only handles 2 levels. Could make it recursive
	 *
	 * @return false|DataObjectSet
	 */
	function getModulePages() {
		if($module = $this->getModule()) {
			$pages = DocumentationParser::get_pages_from_folder($module->getPath());
			
			if($pages) {
				foreach($pages as $page) {
					$linkParts = array();
					
					// don't include the 'index in the url
					if($page->Title != "Index") $linkParts[] = $page->Filename;

					$page->Link = $this->Link($linkParts);
					
					$page->LinkingMode = 'link';
					$page->Children = false;
			
					if(isset($this->Remaining[0])) {
						if(strtolower($this->Remaining[0]) == $page->Filename) {
							$page->LinkingMode = 'current';
							
							if(is_dir($page->Path)) {
								$children = DocumentationParser::get_pages_from_folder($page->Path);
								foreach($children as $child) {
									$child->Link = $this->Link(array($this->Remaining[0], $child->Filename));
								}
								
								$page->Children = $children;
							}
						}
					}
				}
			}
			
			return $pages;
		}
		
		return false;
	}
	
	/**
	 * Return the content for the page. If its an actual documentation page then
	 * display the content from the page, otherwise display the contents from
	 * the index.md file if its a folder
	 *
	 * @return HTMLText
	 */
	function getContent() {
		if($page = $this->getPage()) {
			// Remove last portion of path (filename), we want a link to the folder base
			$html = DocumentationParser::parse($page, $this->Link(array_slice($this->Remaining, -1, -1)));
			return DBField::create("HTMLText", $html);
		}
		
		return false;
	}
	
	/**
	 * Generate a list of breadcrumbs for the user. Based off the remaining params
	 * in the url
	 *
	 * @return DataObjectSet
	 */
	function getBreadcrumbs() {
		$pages = array_merge(array($this->ModuleName), $this->Remaining);;
		
		$output = new DataObjectSet();
		
		// $output->push(new ArrayData(array(
		// 	'Title' => ($this->Version) ? $this->Version : _t('DocumentationViewer.DOCUMENTATION', 'Documentation'),
		// 	'Link' => $this->Link()
		// )));
		
		if($pages) {
			$path = array();
			
			foreach($pages as $i => $title) {
				if($title) {
					// Don't add module name, already present in Link()
					if($i > 0) $path[] = $title;

					$output->push(new ArrayData(array(
						'Title' => DocumentationParser::clean_page_name($title),
						'Link' => $this->Link($path)
					)));
				}
			}
		}
		
		return $output;
	}
	
	/**
	 * Return the base link to this documentation location
	 *
	 * @todo Make this work on non /dev/ 
	 * @return String
	 */
	public function Link($path = false) {
		$base = Director::absoluteBaseURL();
		
		$version = ($this->Version) ? $this->Version . '/' : false;
		$lang = ($this->Lang) ? $this->Lang .'/' : false;
		$module = ($this->ModuleName) ? $this->ModuleName .'/' : false;
		
		$action = '';
		if(is_string($path)) $action = $path . '/';
		
		if(is_array($path)) {
			foreach($path as $key => $value) {
				if($value) {
					$action .= $value .'/';
				}
			}
		}
		
		return $base . self::$link_base . $version . $lang . $module . $action;
	} 
	
	/**
	 * Build the language dropdown.
	 *
	 * @todo do this on a page by page rather than global
	 *
	 * @return Form
	 */
	function LanguageForm() {
		if($module = $this->getModule()) {
			$langs = DocumentationService::get_registered_languages($module->getModuleFolder());
		}
		else {
			$langs = DocumentationService::get_registered_languages();
		}
		
		$fields = new FieldSet(
			$dropdown = new DropdownField(
				'LangCode', 
				_t('DocumentationViewer.LANGUAGE', 'Language'),
				$langs,
				$this->Lang
			)
		);
		
		$actions = new FieldSet(
			new FormAction('doLanguageForm', _t('DocumentationViewer.CHANGE', 'Change'))
		);
		
		$dropdown->setDisabled(true);
		
		return new Form($this, 'LanguageForm', $fields, $actions);
	}
	
	/**
	 * Process the language change
	 *
	 */
	function doLanguageForm($data, $form) {
		$this->Lang = (isset($data['LangCode'])) ? $data['LangCode'] : 'en';

		return $this->redirect($this->Link());
	}
	
	/**
	 * @param String
	 */
	static function set_link_base($base) {
		self::$link_base = $base;
	}
	
	/**
	 * @return String
	 */
	static function get_link_base() {
		return self::$link_base;
	}
}