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
		'DocumentationViewerVersionWarning'
	);

	/**
	 * @var array
	 */
	private static $allowed_actions = array(
		'home',
		'all',
		'LanguageForm',
		'doLanguageForm',
		'handleRequest',
		'DocumentationSearchForm',
		'results'
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
	 * @var array
	 */
	private static $url_handlers = array(
		'$Action' => 'handleAction'
	);

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
		$action = $request->param('Action');

		try {
			if(preg_match('/DocumentationSearchForm/', $request->getURL())) {
				$action = 'results';
			}

			$response = parent::handleAction($request, $action);
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
	 * @return SS_HTTPResponse
	 */
	public function handleRequest(SS_HTTPRequest $request, DataModel $model) {
		$response = parent::handleRequest($request, $model);

		// if we submitted a form, let that pass
		if(!$request->isGET() || isset($_GET['action_results'])) {
			return $response;
		}

		if($response->getStatusCode() !== 200) {
			// look up the manifest to see find the nearest match against the
			// list of the URL. If the URL exists then set that as the current
			// page to match against.
			if($record = $this->getManifest()->getPage($this->request->getURL())) {
				$this->record = $record;

				$type = get_class($this->record);
				$body = $this->renderWith(array(
					"DocumentationViewer_{$type}",
					"DocumentationViewer"
				));

				return new SS_HTTPResponse($body, 200);
			}
			else {
				$this->init();
			
				$class = get_class($this);
				$body = $this->renderWith(array("{$class}_error", $class));

				return new SS_HTTPResponse($body, 404);
			}
		}

		return $response;
	}

	/**
	 * Returns the current version. If no version is set then it is the current
	 * set version so need to pull that from the {@link Entity}.
	 *
	 * @return string
	 */
	public function getVersion() {
		return ($this->record) ? $this->record->getEntity()->getVersion() : null;
	}
	
	/**
	 * Returns the current language.
	 *
	 * @return DocumentationEntityLanguage
	 */
	public function getLanguage() {
		return ($this->record) ? $this->record->getEntity() : null;
	}
	
	/**
	 * @return DocumentationManifest
	 */
	public function getManifest() {
	 	return new DocumentationManifest((isset($_GET['flush'])));
	}

	/**
	 * Return all the available languages for the {@link Entity}.
	 *
	 * @return array
	 */
	public function getLanguages() {
		return ($this->record) ? $this->record->getEntity()->getSupportedLanguages() : null;
	}

	/**
	 * Get all the versions loaded for the current {@link DocumentationEntity}. 
	 * the file system then they are loaded under the 'Current' name space.
	 *
	 * @param String $entity name of {@link Entity} to limit it to eg sapphire
	 * @return ArrayList
	 */
	public function getVersions() {
		return ($this->record) ? $this->record->getEntity()->getVersions() : null;
	}

	/**
	 * @return DocumentationEntityVersion
	 */
	public function getStableVersion() {
		return ($this->record) ? $this->record->getEntity()->getStableVersion() : null;
	}
	
	/**
	 * Generate a list of entities which have been registered and which can 
	 * be documented. 
	 *
	 * @return DataObject
	 */ 
	public function getEntities() {
		$entities = $this->getManifest()->getEntities();
		$output = new ArrayList();

		if($entities) {
			foreach($entities as $entity) {
				$mode = 'link';
				$children = new ArrayList();

				if($this->record) {
					if($entity->hasRecord($this->record)) {
						$mode = 'current';

						// add children
						$children = $this->getManifest()->getChildrenFor(
							$this->getLanguage()->Link(),
							$this->record->Link()
						);
					}
				}

				$link = $entity->Link();
				
				$output->push(new ArrayData(array(
					'Title' 	  => $entity->getTitle(),
					'Link'		  => $link,
					'LinkingMode' => $mode,
					'Children' => $children
				)));
			}
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
		
		return DBField::create_field("HTMLText", $page->getHTML());
	}
	
	/**
	 * Generate a list of breadcrumbs for the user.
	 *
	 * @return ArrayList
	 */
	public function getBreadcrumbs() {
		if($this->record) {
			return $this->getManifest()->generateBreadcrumbs($this->record);
		}
	}

	/**
 	 * @return DocumentationPage
 	 */
	public function getPage() {
		return $this->record;
	}
	/**
	 * Generate a string for the title tag in the URL.
	 *
	 * @return string
	 */
	public function getPageTitle() {
		return ($this->record) ? $this->record->getBreadcrumbTitle() : null;
	}
	
	/**
	 * Return the base link to this documentation location.
	 *
	 * @return string
	 */
	public function Link($action = '') {
		$link = Controller::join_links(
			Director::absoluteBaseURL(), 
			Config::inst()->get('DocumentationViewer', 'link_base'),
			$action
		);

		return $link;
	}

	public function AllPages() {
		$pages = $this->getManifest()->getPages();
		$output = new ArrayList();

		foreach($pages as $url => $page) {
			$output->push(new ArrayData(array(
				'Link' => $url,
				'Title' => $page['title'],
				'FirstLetter' => strtoupper(substr($page['title'], 0, 1))
			)));
		}

		return GroupedList::create($output->sort('Title', 'ASC'));
	}
	
	/**
	 * Build the language dropdown.
	 *
	 * @todo do this on a page by page rather than global
	 *
	 * @return Form
	 */
	public function LanguageForm() {
		$langs = $this->getLanguages();

		$fields = new FieldList(
			$dropdown = new DropdownField(
				'LangCode', 
				_t('DocumentationViewer.LANGUAGE', 'Language'),
				$langs,
				$this->Lang
			)
		);
		
		$actions = new FieldList(
			new FormAction('doLanguageForm', _t('DocumentationViewer.CHANGE', 'Change'))
		);

		return new Form($this, 'LanguageForm', $fields, $actions);
	}
	
	/**
	 * Process the language change
	 *
	 */
	public function doLanguageForm($data, $form) {
		$this->Lang = (isset($data['LangCode'])) ? $data['LangCode'] : 'en';

		return $this->redirect($this->Link());
	}
	
	/**
	 * Documentation Search Form. Allows filtering of the results by many entities
	 * and multiple versions.
	 *
	 * @return Form
	 */
	public function DocumentationSearchForm() {
		if(!DocumentationSearch::enabled()) {
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
		return ($this->record) ? $this->getManifest()->getNextPage($this->record->getPath()) : null;
	}	

	/**
	 * Returns the previous page. Either returns the previous sibling or the 
	 * parent of this page
	 *
	 * @return DocumentationPage
	 */
	public function getPreviousPage() {
		return ($this->record) ? $this->getManifest()->getPreviousPage($this->record->getPath()) : null;
	}
	
	/**
	 * @return string
	 */
	public function getGoogleAnalyticsCode() {
		$code = Config::inst()->get('DocumentationViewer', 'google_analytics_code');

		if($code) {
			return $code;
		}
	}

	/**
	 * @return string
	 */
	public function getDocumentationTitle() {
		return Config::inst()->get('DocumentationViewer', 'documentation_title');
	}
}
