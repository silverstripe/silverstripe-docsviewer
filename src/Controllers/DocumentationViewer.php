<?php
namespace SilverStripe\DocsViewer\Controllers;

use SilverStripe\Assets\Folder;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\DevelopmentAdmin;
use SilverStripe\DocsViewer\DocumentationHelper;
use SilverStripe\DocsViewer\DocumentationManifest;
use SilverStripe\DocsViewer\DocumentationPermalinks;
use SilverStripe\DocsViewer\Extensions\DocumentationViewerVersionWarning;
use SilverStripe\DocsViewer\Models\DocumentationFolder;
use SilverStripe\ORM\ArrayList;
use SilverStripe\ORM\GroupedList;
use SilverStripe\Security\Permission;
use SilverStripe\Security\PermissionProvider;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use SilverStripe\View\Parsers\ShortcodeParser;
use SilverStripe\i18n\i18n;



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

class DocumentationViewer extends Controller implements PermissionProvider
{
    /**
     * @var array
     */
    private static $extensions = array(
        DocumentationViewerVersionWarning::class,
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
     * @var DocumentationManifest
     */
    protected $manifest;

    /**
     * @config
     *
     * @var string Site-relative root URL of documentation. Same as the routing pattern set through Director::addRules().
     */
    private static $link_base = 'dev/docs/';

    /**
     * @config
     *
     * @var string|array Optional permission check
     */
    private static $check_permission = 'CMS_VIEW_DEVDOCS';

    /**
     * @var array map of modules to edit links.
     * @see {@link getEditLink()}
     */
    private static $edit_links = array();

    /**
     * Determines whether css and js files are injected into the document.
     *
     * @var bool
     */
    private static $apply_default_theme = true;

    /**
     *
     */
    public function init()
    {
        parent::init();

        if (!$this->canView()) {
            return Security::permissionFailure($this);
        }

        if ($this->config()->apply_default_theme) {
            Requirements::javascript('//use.typekit.net/emt4dhq.js');
            Requirements::customScript('try{Typekit.load();}catch(e){}');

            Requirements::combine_files(
                'docs.js',
                array(
                    'silverstripe/admin: thirdparty/jquery/jquery.js',
                    'silverstripe/docsviewer: thirdparty/highlight/highlight.pack.js',
                    'silverstripe/docsviewer: javascript/DocumentationViewer.js',
                )
            );
            Requirements::combine_files(
                'docs.css',
                array(
                    'silverstripe/docsviewer: css/highlight.css',
                    'silverstripe/docsviewer: css/normalize.css',
                    'silverstripe/docsviewer: css/utilities.css',
                    'silverstripe/docsviewer: css/typography.css',
                    'silverstripe/docsviewer: css/forms.css',
                    'silverstripe/docsviewer: css/layout.css',
                    'silverstripe/docsviewer: css/small.css'
                )
            );
        }
    }

    /**
     * Permission provider to allow more control over who can view docs
     *
     * @return array
     */

    public function providePermissions()
    {
        return array(
            'CMS_VIEW_DEVDOCS' => array(
                'name' => 'View Site Documentation',
                'category' => 'Documentation',
            )
        );
    }

    /**
     * Can the user view this documentation. Hides all functionality for private
     * wikis.
     *
     * @return bool
     */
    public function canView()
    {
        return (Director::isDev() || Director::is_cli() ||
            !$this->config()->get('check_permission') ||
            Permission::check($this->config()->get('check_permission'))
        );
    }

    public function hasAction($action)
    {
        return true;
    }

    public function checkAccessAction($action)
    {
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
    public function handleAction($request, $action)
    {
        if ($request->param('Controller') == DevelopmentAdmin::class && $request->param('Action') == 'docs') {
            $request->shiftAllParams();
        }

        // if we submitted a form, let that pass
        if (!$request->isGET() && !$request->isHEAD()) {
            return parent::handleAction($request, $action);
        }

        $url = $request->getURL();

        //
        // If the current request has an extension attached to it, strip that
        // off and redirect the user to the page without an extension.
        //
        if (DocumentationHelper::get_extension($url)) {
            $this->response = new HTTPResponse();
            $this->response->redirect(
                Director::absoluteURL(DocumentationHelper::trim_extension_off($url)) .'/',
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
            Config::inst()->get(DocumentationViewer::class, 'link_base'),
            '/'
        );

        if ($base && strpos($url, $base) !== false) {
            $url = substr(
                ltrim($url, '/'),
                strlen($base)
            );
        }

        //
        // Handle any permanent redirections that the developer has defined.
        //
        if ($link = DocumentationPermalinks::map($url)) {
            // the first param is a shortcode for a page so redirect the user to
            // the short code.
            $this->response = new HTTPResponse();
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
        $languages = i18n::getData()->getLanguages();

        if (!$lang = $request->param('Lang')) {
            $lang = $request->param('Action');
            $action = $request->param('ID');
        } else {
            $action = $request->param('Action');
        }

        if (!$lang) {
            return $this->redirect($this->Link('en'));
        } elseif (!isset($languages[$lang])) {
            return $this->httpError(404);
        }

        $request->shift(10);

        $allowed = $this->config()->allowed_actions;

        if (in_array($action, $allowed)) {
            //
            // if it's one of the allowed actions such as search or all then the
            // URL must be prefixed with one of the allowed languages and versions
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
            if ($record = $this->getManifest()->getPage($url)) {
                // In SS 3 offsetSet() isn't implemented for some reason... this is a workaround
                $routeParams = $this->request->routeParams();
                $routeParams['Lang'] = $lang;
                $this->request->setRouteParams($routeParams);

                $this->record = $record;
                $this->init();

                $type = $this->record->getType();
                $body = $this->renderWith(
                    array(
                        DocumentationViewer::class . "_{$type}",
                        DocumentationViewer::class
                    )
                );

                return new HTTPResponse($body, 200);
            } elseif ($redirect = $this->getManifest()->getRedirect($url)) {
                $response = new HTTPResponse();
                $to = Controller::join_links(Director::baseURL(), $base, $redirect);
                return $response->redirect($to, 301);
            } elseif (!$url || $url == $lang) {
                $body = $this->renderWith(
                    array(
                        DocumentationViewer::class . '_DocumentationFolder',
                        DocumentationViewer::class
                    )
                );

                return new HTTPResponse($body, 200);
            }
        }

        return $this->httpError(404);
    }

    /**
     * @param int    $status
     * @param string $message
     *
     * @return SS_HTTPResponse
     */
    public function httpError($status, $message = null)
    {
        $this->init();

        $class = get_class($this);
        $body = $this->customise(
            new ArrayData(
                array(
                'Message' => $message
                )
            )
        )->renderWith(array("{$class}_error", $class));

        return new HTTPResponse($body, $status);
    }

    /**
     * @return DocumentationManifest
     */
    public function getManifest()
    {
        if (!$this->manifest) {
            $flush = Director::isTest() || (isset($_GET['flush']));

            $this->manifest = new DocumentationManifest($flush);
        }

        return $this->manifest;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        if (!$lang = $this->request->param('Lang')) {
            $lang = $this->request->param('Action');
        }

        return $lang;
    }



    /**
     * Generate a list of {@link Documentation } which have been registered and which can
     * be documented.
     *
     * @return DataObject
     */
    public function getMenu()
    {
        $entities = $this->getManifest()->getEntities();
        $output = new ArrayList();
        $record = $this->getPage();
        $current = $this->getEntity();

        foreach ($entities as $entity) {
            $checkLang = $entity->getLanguage();
            $checkVers = $entity->getVersion();

            // only show entities with the same language or any entity that
            // isn't registered under any particular language (auto detected)
            if ($checkLang && $checkLang !== $this->getLanguage()) {
                continue;
            }

            if ($current && $checkVers) {
                if ($entity->getVersion() !== $current->getVersion()) {
                    continue;
                }
            }

            $mode = 'link';
            $children = new ArrayList();

            if ($entity->hasRecord($record) || $entity->getIsDefaultEntity()) {
                $mode = 'current';

                // add children
                $children = $this->getManifest()->getChildrenFor(
                    $entity->getPath(),
                    ($record) ? $record->getPath() : $entity->getPath()
                );
            } else {
                if ($current && $current->getKey() == $entity->getKey()) {
                    continue;
                }
            }

            $link = $entity->Link();

            $output->push(
                new ArrayData(
                    array(
                    'Title'         => $entity->getTitle(),
                    'Link'          => $link,
                    'LinkingMode'   => $mode,
                    'DefaultEntity' => $entity->getIsDefaultEntity(),
                    'Children'      => $children
                    )
                )
            );
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
    public function getContent()
    {
        $page = $this->getPage();
        $html = $page->getHTML();
        $html = $this->replaceChildrenCalls($html);

        return $html;
    }

    public function replaceChildrenCalls($html)
    {
        $codes = new ShortcodeParser();
        $codes->register('CHILDREN', array($this, 'includeChildren'));

        return $codes->parse($html);
    }

    /**
     * Short code parser
     */
    public function includeChildren($args)
    {
        if (isset($args[Folder::class])) {
            $children = $this->getManifest()->getChildrenFor(
                Controller::join_links(dirname($this->record->getPath()), $args[Folder::class])
            );
        } else {
            $children = $this->getManifest()->getChildrenFor(
                dirname($this->record->getPath())
            );
        }

        if (isset($args['Exclude'])) {
            $exclude = explode(',', $args['Exclude']);

            foreach ($children as $k => $child) {
                foreach ($exclude as $e) {
                    if ($child->Link == Controller::join_links($this->record->Link(), strtolower($e), '/')) {
                        unset($children[$k]);
                    }
                }
            }
        }

        return $this->customise(
            new ArrayData(
                array(
                    'Children' => $children
                )
            )
        )->renderWith('SilverStripe/DocsViewer/Controllers/Includes/DocumentationPages');
    }

    /**
     * @return ArrayList
     */
    public function getChildren()
    {
        if ($this->record instanceof DocumentationFolder) {
            return $this->getManifest()->getChildrenFor(
                $this->record->getPath()
            );
        } elseif ($this->record) {
            return $this->getManifest()->getChildrenFor(
                dirname($this->record->getPath())
            );
        }

        return new ArrayList();
    }

    /**
     * Generate a list of breadcrumbs for the user.
     *
     * @return ArrayList
     */
    public function getBreadcrumbs()
    {
        if ($this->record) {
            return $this->getManifest()->generateBreadcrumbs(
                $this->record,
                $this->record->getEntity()
            );
        }
    }

    /**
     * @return DocumentationPage
     */
    public function getPage()
    {
        return $this->record;
    }

    /**
     * @return DocumentationEntity
     */
    public function getEntity()
    {
        return ($this->record) ? $this->record->getEntity() : null;
    }

    /**
     * @return ArrayList
     */
    public function getVersions()
    {
        return $this->getManifest()->getVersions($this->getEntity());
    }

    /**
     * Generate a string for the title tag in the URL.
     *
     * @return string
     */
    public function getTitle()
    {
        return ($this->record) ? $this->record->getTitle() : null;
    }

    /**
     * @return string
     */
    public function AbsoluteLink($action)
    {
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
    public function Link($action = '')
    {
        $link = Controller::join_links(
            Director::baseURL(),
            Config::inst()->get(DocumentationViewer::class, 'link_base'),
            $this->getLanguage(),
            $action,
            '/'
        );

        return $link;
    }

    /**
     * Return the canonical URL from the page
     *
     * @return string
     */
    public function getCanonicalUrl()
    {
        if (!$this->getPage()) {
            return '';
        }
        return $this->getPage()->getCanonicalUrl();
    }

    /**
     * Generate a list of all the pages in the documentation grouped by the
     * first letter of the page.
     *
     * @param  string|int|null $version
     * @return GroupedList
     */
    public function AllPages($version = null)
    {
        $pages = $this->getManifest()->getPages();
        $output = new ArrayList();
        $baseLink = $this->getDocumentationBaseHref();

        foreach ($pages as $url => $page) {
            // Option to skip Pages that do not belong to the current version
            if (!is_null($version) && (string) $page['version'] !== (string) $version) {
                continue;
            }

            $first = strtoupper(trim(substr($page['title'], 0, 1)));

            if ($first) {
                $output->push(
                    new ArrayData(
                        array(
                            'Link'        => Controller::join_links($baseLink, $url),
                            'Title'       => $page['title'],
                            'FirstLetter' => $first
                        )
                    )
                );
            }
        }

        return GroupedList::create($output->sort('Title', 'ASC'));
    }

    /**
     * Return all Pages that apply to the current version (from the route)
     *
     * @return GroupedList
     */
    public function getAllVersionPages()
    {
        return $this->AllPages($this->getRequestedVersion());
    }

    /**
     * Sets the mapping between a entity name and the link for the end user
     * to jump into editing the documentation.
     *
     * Some variables are replaced:
     *    - %version%
     *    - %entity%
     *    - %path%
     *    - %lang%
     *
     * For example to provide an edit link to the framework module in github:
     *
     * <code>
     * DocumentationViewer::set_edit_link(
     *     'framework',
     *     'https://github.com/silverstripe/%entity%/edit/%version%/docs/%lang%/%path%',
     *     $opts
     * ));
     * </code>
     *
     * @param string module name
     * @param string link
     * @param array options ('rewritetrunktomaster')
     */
    public static function set_edit_link($module, $link, $options = array())
    {
        self::$edit_links[$module] = array(
            'url' => $link,
            'options' => $options
        );
    }

    /**
     * Returns an edit link to the current page (optional).
     *
     * @return string|false
     */
    public function getEditLink()
    {
        $editLink = false;
        $entity = null;

        $page = $this->getPage();

        if ($page) {
            $entity = $page->getEntity();

            if ($entity && isset(self::$edit_links[strtolower($entity->title)])) {
                // build the edit link, using the version defined
                $url = self::$edit_links[strtolower($entity->title)];
                $version = $entity->getVersion();

                if ($entity->getBranch()) {
                    $version = $entity->getBranch();
                }


                if ($version === 'trunk' && (isset($url['options']['rewritetrunktomaster']))) {
                    if ($url['options']['rewritetrunktomaster']) {
                        $version = "master";
                    }
                }

                $editLink = str_replace(
                    array('%entity%', '%lang%', '%version%', '%path%'),
                    array(
                        $entity->title,
                        $this->getLanguage(),
                        $version,
                        ltrim($page->getRelativePath(), '/')
                    ),
                    $url['url']
                );
            }
        }

        $this->extend('updateDocumentationEditLink', $editLink, $entity);

        return $editLink;
    }



    /**
     * Returns the next page. Either retrieves the sibling of the current page
     * or return the next sibling of the parent page.
     *
     * @return DocumentationPage|null
     */
    public function getNextPage()
    {
        return ($this->record)
            ? $this->getManifest()->getNextPage(
                $this->record->getPath(),
                $this->getEntity()->getPath()
            )
            : null;
    }

    /**
     * Returns the previous page. Either returns the previous sibling or the
     * parent of this page
     *
     * @return DocumentationPage|null
     */
    public function getPreviousPage()
    {
        return ($this->record)
            ? $this->getManifest()->getPreviousPage(
                $this->record->getPath(),
                $this->getEntity()->getPath()
            )
            : null;
    }

    /**
     * @return string|void
     */
    public function getGoogleAnalyticsCode()
    {
        $code = $this->config()->get('google_analytics_code');

        if ($code) {
            return $code;
        }
    }

    /**
     * @return string
     */
    public function getDocumentationTitle()
    {
        return $this->config()->get('documentation_title');
    }

    /**
     * @return string
     */
    public function getDocumentationBaseHref()
    {
        return Controller::join_links(
            Director::baseURL(),
            Config::inst()->get(DocumentationViewer::class, 'link_base')
        );
    }

    /**
     * Gets whether there is a default entity or not
     *
     * @return boolean
     * @see    DocumentationManifest::getHasDefaultEntity()
     */
    public function getHasDefaultEntity()
    {
        return $this->getManifest()->getHasDefaultEntity();
    }

    /**
     * Gets the requested version from the URL
     *
     * @return string
     */
    public function getRequestedVersion()
    {
        return (string) $this->request->param('Version');
    }

    /**
     * Gets the link to the "documentation index" containing the currently requested version
     *
     * @return string
     */
    public function getDocumentationIndexLink()
    {
        return $this->Link($this->getRequestedVersion() . '/all');
    }
}
