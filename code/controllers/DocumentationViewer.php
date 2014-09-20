<?php

/**
 * Documentation Viewer.
 *
 * Reads the bundled markdown files from documentation folders and displays the 
 * output (either via markdown or plain text).
 *
 * For more documentation on how to use this class see the documentation in the
 * docs folder.
 *
 * @package docsviewer
 */

class DocumentationViewer extends Controller {

	/**
	 * @var array
	 */
	private static $extensions = array(
		'DocumentationViewerVersionWarning',
		'DocumentationSearchExtension'
	);

	/**
	 * @var string
	 */
	private static $google_analytics_code = '';

	/**
	 * @var string
	 */
	private static $documentation_title = 'SilverStripe Documentation';
	
	/**
	 * @var array
	 */
	private static $allowed_actions = array(
		'all',
		'results',
		'handleAction'
	);

	/**
	 * The string name of the currently accessed {@link DocumentationEntity}
	 * object. To access the entire object use {@link getEntity()}
	 *
	 * @var string
	 */
	protected $entity = '';

	/**
	 * @var DocumentationPage
	 */
	protected $record;

	/**
	 * @config
	 *
	 * @var string same as the routing pattern set through Director::addRules().
	 */
	private static $link_base = 'dev/docs/';
	
	/**
	 * @config
	 *
	 * @var string|array Optional permission check
	 */
	private static $check_permission = 'ADMIN';

	/**
	 * @var array map of modules to edit links.
	 * @see {@link getEditLink()}
	 */
	private static $edit_links = array();

	/**
	 *
	 */
	public function init() {
		parent::init();

		if(!$this->canView()) {
			return Security::permissionFailure($this);
		}

		Requirements::javascript(THIRDPARTY_DIR .'/jquery/jquery.js');		
		Requirements::combine_files(
			'syntaxhighlighter.js',
			array(
				DOCSVIEWER_DIR .'/thirdparty/syntaxhighlighter/scripts/shCore.js',
				DOCSVIEWER_DIR . '/thirdparty/syntaxhighlighter/scripts/shBrushJScript.js',
				DOCSVIEWER_DIR . '/thirdparty/syntaxhighlighter/scripts/shBrushPhp.js',
				DOCSVIEWER_DIR . '/thirdparty/syntaxhighlighter/scripts/shBrushXml.js',
				DOCSVIEWER_DIR . '/thirdparty/syntaxhighlighter/scripts/shBrushCss.js',
				DOCSVIEWER_DIR . '/thirdparty/syntaxhighlighter/scripts/shBrushYaml.js',
				DOCSVIEWER_DIR . '/thirdparty/syntaxhighlighter/scripts/shBrushBash.js',
				DOCSVIEWER_DIR . '/javascript/shBrushSS.js'
			)
		);
		
		Requirements::javascript(DOCSVIEWER_DIR .'/javascript/DocumentationViewer.js');
		Requirements::css(DOCSVIEWER_DIR .'/css/shSilverStripeDocs.css');
		Requirements::combine_files('docs.css', array(
			DOCSVIEWER_DIR .'/css/normalize.css',
			DOCSVIEWER_DIR .'/css/utilities.css',
			DOCSVIEWER_DIR .'/css/typography.css',
			DOCSVIEWER_DIR .'/css/forms.css',
			DOCSVIEWER_DIR .'/css/layout.css',
			DOCSVIEWER_DIR .'/css/small.css'
		));
	}
	
	/**
	 * Can the user view this documentation. Hides all functionality for private 
	 * wikis.
	 *
	 * @return bool
	 */
	public function canView() {
		return (Director::isDev() || Director::is_cli() || 
			!self::$check_permission || 
			Permission::check(self::$check_permission)
		);
	}

	public function hasAction($action) {
		return true;
	}

	public function checkAccessAction($action) {
		return true;
	}

	/**
	 * Overloaded to avoid "action doesn't exist" errors - all URL parts in 
	 * this controller are virtual and handled through handleRequest(), not 
	 * controller methods.
	 *
	 * @param $request
	 * @param $action
	 *
	 * @return SS_HTTPResponse
	 */
	public function handleAction($request, $action) {
		// if we submitted a form, let that pass
		if(!$request->isGET()) {
			return parent::handleAction($request, $action);
		}

		$url = $request->getURL();

		//
		// If the current request has an extension attached to it, strip that 
		// off and redirect the user to the page without an extension.
		//
		if(DocumentationHelper::get_extension($url)) {
			$this->response = new SS_HTTPResponse();
			$this->response->redirect(
				DocumentationHelper::trim_extension_off($url) .'/', 
				301
			);

			$request->shift();
			$request->shift();

			return $this->response; 
		}

		//
		// Strip off the base url
		//
		$base = ltrim(
			Config::inst()->get('DocumentationViewer', 'link_base'), '/'
		);

		if($base && strpos($url, $base) !== false) {
			$url = substr(
				ltrim($url, '/'), 
				strlen($base)
			);
		} else {

		}
		
		//
		// Handle any permanent redirections that the developer has defined.
		// 
		if($link = DocumentationPermalinks::map($url)) {
			// the first param is a shortcode for a page so redirect the user to
			// the short code.
			$this->response = new SS_HTTPResponse();
			$this->response->redirect($link, 301);
			
			$request->shift();
			$request->shift();

			return $this->response;
		}

		//
		// Validate the language provided. Language is a required URL parameter.
		// as we use it for generic interfaces and language selection. If 
		// language is not set, redirects to 'en'
		//
		$languages = i18n::get_common_languages();

		if(!$request->param('Lang')) {
			return $this->redirect($this->Link('en'));
		} else if(!isset($languages[$request->param('Lang')])) {
			return $this->httpError(404);
		}

		$action = $request->param('Action');
		$allowed = $this->config()->allowed_actions;

		$request->shift();
		$request->shift();

		if(in_array($action, $allowed)) {
			//
			// if it's one of the allowed actions such as search or all then the
			// URL must be prefixed with one of the allowed languages.
			//
			return parent::handleAction($request, $action);
		} else {
			//
			// look up the manifest to see find the nearest match against the
			// list of the URL. If the URL exists then set that as the current
			// page to match against.

			// strip off any extensions.


			// if($cleaned !== $url) {
			// 	$redirect = new SS_HTTPResponse();

			// 	return $redirect->redirect($cleaned, 302);
			// }
			if($record = $this->getManifest()->getPage($url)) {
				$this->record = $record;
				$this->init();

				$type = get_class($this->record);
				$body = $this->renderWith(array(
					"DocumentationViewer_{$type}",
					"DocumentationViewer"
				));

				return new SS_HTTPResponse($body, 200);
			} else if(!$url || $url == $request->param('Lang')) {
				$body = $this->renderWith(array(
					"DocumentationViewer_DocumentationFolder",
					"DocumentationViewer"
				));

				return new SS_HTTPResponse($body, 200);
			}
		}
		
		return $this->httpError(404);
	}

	/**
	 * @param int $status
	 * @param string $message
	 *
	 * @return SS_HTTPResponse
	 */
	public function httpError($status, $message = null) {
		$this->init();
			
		$class = get_class($this);
		$body = $this->customise(new ArrayData(array(
			'Message' => $message
		)))->renderWith(array("{$class}_error", $class));

		return new SS_HTTPResponse($body, $status);
	}

	/**
	 * @return DocumentationManifest
	 */
	public function getManifest() {
		$flush = SapphireTest::is_running_test() || (isset($_GET['flush']));
		
	 	return new DocumentationManifest($flush);
	}

	/**
	 * @return string
	 */
	public function getLanguage() {
		return $this->request->param('Lang');
	}



	/**
	 * Generate a list of {@link Documentation } which have been registered and which can 
	 * be documented. 
	 *
	 * @return DataObject
	 */ 
	public function getMenu() {
		$entities = $this->getManifest()->getEntities();
		$output = new ArrayList();
		$record = $this->getPage();
		$current = $this->getEntity();

		foreach($entities as $entity) {
			// only show entities with the same language
			if($entity->getLanguage() !== $this->getLanguage()) {
				continue;
			}

			$mode = 'link';	
			$children = new ArrayList();

			if($entity->hasRecord($record) || $entity->getIsDefaultEntity()) {
				$mode = 'current';

				// add children
				$children = $this->getManifest()->getChildrenFor(
					$entity->getPath(), ($record) ? $record->getPath() : $entity->getPath()
				);
			} else {
				if($current && $current->getKey() == $entity->getKey()) {
					continue;
				}
			}

			$link = $entity->Link();

			$output->push(new ArrayData(array(
				'Title' 	  => $entity->getTitle(),
				'Link'		  => $link,
				'LinkingMode' => $mode,
				'DefaultEntity' => $entity->getIsDefaultEntity(),
				'Children' => $children
			)));
		}

		return $output;
	}
	
	/**
	 * Return the content for the page. If its an actual documentation page then
	 * display the content from the page, otherwise display the contents from
	 * the index.md file if its a folder
	 *
	 * @return HTMLText
	 */
	public function getContent() {
		$page = $this->getPage();
		$html = $page->getHTML();
		$html = $this->replaceChildrenCalls($html);

		return DBField::create_field("HTMLText", $html);
	}

	public function replaceChildrenCalls($html) {
		$codes = new ShortcodeParser();
		$codes->register('CHILDREN',  array($this, 'includeChildren'));

		return $codes->parse($html);
	}

	/**
	 * Short code parser
	 */
	public function includeChildren($args) {
		if(isset($args['Folder'])) {
			$children = $this->getManifest()->getChildrenFor(
					Controller::join_links(dirname($this->record->getPath()), $args['Folder'])
			);
		} else {
			$children = $this->getManifest()->getChildrenFor(
				dirname($this->record->getPath())
			);
		}

		return $this->customise(new ArrayData(array(
			'Children' => $children
		)))->renderWith('Includes/DocumentationPages');
	}

	/**
	 * @return ArrayList
	 */
	public function getChildren() {
		if($this->record instanceof DocumentationFolder) {
			return $this->getManifest()->getChildrenFor(
				$this->record->getPath()
			);
		} else {
			return $this->getManifest()->getChildrenFor(
				dirname($this->record->getPath())
			);
		}
	}
	
	/**
	 * Generate a list of breadcrumbs for the user.
	 *
	 * @return ArrayList
	 */
	public function getBreadcrumbs() {
		if($this->record) {
			return $this->getManifest()->generateBreadcrumbs(
				$this->record,
				$this->record->getEntity()
			);
		}
	}

	/**
 	 * @return DocumentationPage
 	 */
	public function getPage() {
		return $this->record;
	}

	/**
	 * @return DocumentationEntity
	 */
	public function getEntity() {
		return ($this->record) ? $this->record->getEntity() : null;
	}

	/**
	 * @return ArrayList
	 */
	public function getVersions() {
		return $this->manifest->getVersions($this->getEntity);
	}

	/**
	 * Generate a string for the title tag in the URL.
	 *
	 * @return string
	 */
	public function getTitle() {
		return ($this->record) ? $this->record->getTitle() : null;
	}
	
	/**
	 * @return string
	 */
	public function AbsoluteLink($action) {
		return Controller::join_links(
			Director::absoluteBaseUrl(),
			$this->Link($action)
		);
	}

	/**
	 * Return the base link to this documentation location.
	 *
	 * @return string
	 */
	public function Link($action = '') {
		$link = Controller::join_links(
			Config::inst()->get('DocumentationViewer', 'link_base'),
			$this->getLanguage(),
			$action,
			'/'
		);

		return $link;
	}

	/**
	 * Generate a list of all the pages in the documentation grouped by the 
	 * first letter of the page.
	 *
	 * @return GroupedList
	 */
	public function AllPages() {
		$pages = $this->getManifest()->getPages();
		$output = new ArrayList();

		foreach($pages as $url => $page) {
			$first = strtoupper(trim(substr($page['title'], 0, 1)));

			if($first) {
				$output->push(new ArrayData(array(
					'Link' => $url,
					'Title' => $page['title'],
					'FirstLetter' => $first
				)));
			}
		}

		return GroupedList::create($output->sort('Title', 'ASC'));
	}
	
	/**
	 * Documentation Search Form. Allows filtering of the results by many entities
	 * and multiple versions.
	 *
	 * @return Form
	 */
	public function DocumentationSearchForm() {
		if(!Config::inst()->get('DocumentationSearch','enabled')) {
			return false;
		}
		
		return new DocumentationSearchForm($this);
	}

	/**
	 * Sets the mapping between a entity name and the link for the end user
	 * to jump into editing the documentation. 
	 *
	 * Some variables are replaced:
	 *	- %version%
	 *	- %entity%
	 *	- %path%
	 * 	- %lang%
	 *
	 * For example to provide an edit link to the framework module in github:
	 *
	 * <code>
	 * DocumentationViewer::set_edit_link(
	 *	'framework', 
	 *	'https://github.com/silverstripe/%entity%/edit/%version%/docs/%lang%/%path%',
	 * 	$opts
	 * ));
	 * </code>
	 *
	 * @param string module name
	 * @param string link
	 * @param array options ('rewritetrunktomaster')
	 */
	public static function set_edit_link($module, $link, $options = array()) {
		self::$edit_links[$module] = array(
			'url' => $link,
			'options' => $options
		);
	}

	/**
	 * Returns an edit link to the current page (optional).
	 *
	 * @return string
	 */
	public function getEditLink() {
		$page = $this->getPage();

		if($page) {
			$entity = $page->getEntity();

			if($entity && isset(self::$edit_links[$entity->title])) {
				// build the edit link, using the version defined
				$url = self::$edit_links[$entity->title];
				$version = $this->getVersion();

				if($version == "trunk" && (isset($url['options']['rewritetrunktomaster']))) {
					if($url['options']['rewritetrunktomaster']) {
						$version = "master";
					}
				}

				return str_replace(
					array('%entity%', '%lang%', '%version%', '%path%'),
					array(
						$entity->getBaseFolder(), 
						$this->getLanguage(), 
						$version, 
						ltrim($page->getPath(), '/')
					),

					$url['url']
				);
			}
		}

		return false;
	}



	/**
	 * Returns the next page. Either retrieves the sibling of the current page
	 * or return the next sibling of the parent page.
	 *
	 * @return DocumentationPage
	 */
	public function getNextPage() {
		return ($this->record) 
			? $this->getManifest()->getNextPage($this->record->getPath()) 
			: null;
	}	

	/**
	 * Returns the previous page. Either returns the previous sibling or the 
	 * parent of this page
	 *
	 * @return DocumentationPage
	 */
	public function getPreviousPage() {
		return ($this->record) 
			? $this->getManifest()->getPreviousPage($this->record->getPath()) 
			: null;
	}
	
	/**
	 * @return string
	 */
	public function getGoogleAnalyticsCode() {
		$code = $this->config()->get('google_analytics_code');

		if($code) {
			return $code;
		}
	}

	/**
	 * @return string
	 */
	public function getDocumentationTitle() {
		return $this->config()->get('documentation_title');
	}
}
