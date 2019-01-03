<?php
namespace SilverStripe\DocsViewer;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\DocsViewer\Controllers\DocumentationViewer;
use SilverStripe\DocsViewer\Models\DocumentationEntity;
use SilverStripe\DocsViewer\Models\DocumentationFolder;
use SilverStripe\DocsViewer\Models\DocumentationPage;
use SilverStripe\ORM\ArrayList;
use SilverStripe\View\ArrayData;
use SilverStripe\i18n\i18n;
use Exception;


/**
 * A class which builds a manifest of all documentation present in a project.
 *
 * The manifest is required to map the provided documentation URL rules to a
 * file path on the server. The stored cache looks similar to the following:
 *
 * <code>
 * array(
 *     'en/someniceurl/' => array(
 *       'filepath' => '/path/to/docs/en/SomeniceFile.md',
 *         'title' => 'Some nice URL',
 *         'summary' => 'Summary Text',
 *         'basename' => 'SomeniceFile.md',
 *         'type' => 'DocumentationPage'
 *     )
 *   )
 * </code>
 *
 * URL format is in the following structures:
 *
 *  {lang}/{path}
 *    {lang}/{module}/{path}
 *  {lang}/{module}/{version}/{/path}
 *
 * @package    framework
 * @subpackage manifest
 */
class DocumentationManifest
{
    /**
     * @config
     *
     * @var boolean $automatic_registration
     */
    private static $automatic_registration = true;

    /**
     * @config
     *
     * @var array $registered_entities
     */
    private static $register_entities = array();

    protected $cache;
    protected $cacheKey;

    protected $inited;
    protected $forceRegen;

    /**
     * @var array $pages
     */
    protected $pages = array();

    protected $redirects = array();

    /**
     * @var DocumentationEntity
     */
    private $entity;

    /**
     * @var boolean
     */
    private $has_default_entity = false;

    /**
     * @var boolean
     */
    private $automaticallyPopulated = false;

    /**
     * @var ArrayList
     */
    private $registeredEntities;

    /**
     * Constructs a new template manifest. The manifest is not actually built
     * or loaded from cache until needed.
     *
     * @param bool $includeTests Include tests in the manifest.
     * @param bool $forceRegen   Force the manifest to be regenerated.
     */
    public function __construct($forceRegen = false)
    {
        $this->cacheKey   = 'manifest';
        $this->forceRegen = $forceRegen;
        $this->registeredEntities = new ArrayList();

        $this->cache = Injector::inst()->get(CacheInterface::class.'.DocsViewerManifest');

        $this->setupEntities();
    }

    /**
     * Sets up the top level entities.
     *
     * Either manually registered through the YAML syntax or automatically
     * loaded through investigating the file system for `docs` folder.
     */
    public function setupEntities()
    {
        if ($this->registeredEntities->Count() > 0) {
            return;
        }

        if (Config::inst()->get(DocumentationManifest::class, 'automatic_registration')) {
            $this->populateEntitiesFromInstall();
        }

        $registered = Config::inst()->get(DocumentationManifest::class, 'register_entities');

        foreach ($registered as $details) {
            // validate the details provided through the YAML configuration
            $required = array('Path', 'Title');

            foreach ($required as $require) {
                if (!isset($details[$require])) {
                    throw new Exception("$require is a required key in DocumentationManifest.register_entities");
                }
            }

            // if path is not an absolute value then assume it is relative from
            // the BASE_PATH.
            $path = $this->getRealPath($details['Path']);

            $key = (isset($details['Key'])) ? $details['Key'] : $details['Title'];

            if (!$path || !is_dir($path)) {
                trigger_error($details['Path'] . ' is not a valid documentation directory', E_USER_WARNING);
                continue;
            }

            $version = (isset($details['Version'])) ? $details['Version'] : '';

            $versionTitle = isset($details['VersionTitle'])
                ? $details['VersionTitle']
                : $version;

            $archived = !empty($details['Archived']);

            $branch = (isset($details['Branch'])) ? $details['Branch'] : '';

            $langs = scandir($path);

            if ($langs) {
                $possible = i18n::getData()->getLanguages();

                foreach ($langs as $lang) {
                    if (array_key_exists($lang, $possible)) {
                        /**
                         * @var DocumentationEntity $entity
                         */
                        $entity = Injector::inst()->create(
                            DocumentationEntity::class,
                            $key
                        );

                        $entity->setPath(DocumentationHelper::normalizePath(Controller::join_links($path, $lang, '/')));
                        $entity->setTitle($details['Title']);
                        $entity->setLanguage($lang);
                        $entity->setVersion($version);
                        $entity->setVersionTitle($versionTitle);
                        $entity->setBranch($branch);
                        $entity->setIsArchived($archived);

                        if (isset($details['Stable'])) {
                            $entity->setIsStable($details['Stable']);
                        }

                        if (isset($details['DefaultEntity'])) {
                            $entity->setIsDefaultEntity($details['DefaultEntity']);
                            if ($entity->getIsDefaultEntity()) {
                                $this->has_default_entity = true;
                            }
                        }

                        $this->registeredEntities->push($entity);
                    }
                }
            }
        }
    }

    public function getRealPath($path)
    {
        if (!Director::is_absolute($path)) {
            $path = Controller::join_links(BASE_PATH, $path);
        }

        return $path;
    }

    /**
     * @return ArrayList
     */
    public function getEntities()
    {
        return $this->registeredEntities;
    }

    /**
     * Scans the current installation and picks up all the SilverStripe modules
     * that contain a `docs` folder.
     *
     * @return void
     */
    public function populateEntitiesFromInstall()
    {
        if ($this->automaticallyPopulated) {
            // already run
            return;
        }

        $entities = [];

        foreach (scandir(BASE_PATH) as $key => $entity) {
            if ($key == "themes") {
                continue;
            }

            $dir = DocumentationHelper::normalizePath(Controller::join_links(BASE_PATH, $entity));

            if (is_dir($dir)) {
                // check to see if it has docs
                $docs = Controller::join_links($dir, 'docs');

                if (is_dir($docs)) {
                    $entities[] = array(
                        'Path' => $docs,
                        'Title' => DocumentationHelper::clean_page_name($entity),
                        'Version' => 'master',
                        'Branch' => 'master',
                        'Stable' => true
                    );
                }
            }
        }

        Config::inst()->update(
            DocumentationManifest::class,
            'register_entities',
            $entities
        );

        $this->automaticallyPopulated = true;
    }

    /**
     *
     */
    protected function init()
    {
        if (!$this->forceRegen && $this->cache->has($this->cacheKey)) {
            $data = $this->cache->get($this->cacheKey);
            $this->pages = $data['pages'];
            $this->redirects = $data['redirects'];
            $this->inited    = true;
        } else {
            $this->regenerate();
        }
    }


    /**
     * Returns a map of all documentation pages.
     *
     * @return array
     */
    public function getPages()
    {
        if (!$this->inited) {
            $this->init();
        }

        return $this->pages;
    }

    public function getRedirects()
    {
        if (!$this->inited) {
            $this->init();
        }

        return $this->redirects;
    }

    /**
     * Returns a particular page for the requested URL.
     *
     * @return DocumentationPage
     */
    public function getPage($url)
    {
        $pages = $this->getPages();
        $url = $this->normalizeUrl($url);

        if (!isset($pages[$url])) {
            return null;
        }


        $record = $pages[$url];

        foreach ($this->getEntities() as $entity) {
            if (strpos($record['filepath'], $entity->getPath()) !== false) {
                $page =  Injector::inst()->create(
                    $record['type'],
                    $entity,
                    $record['basename'],
                    $record['filepath']
                );

                return $page;
            }
        }
    }

    /**
     * Get any redirect for the given url
     *
     * @param  type $url
     * @return string
     */
    public function getRedirect($url)
    {
        $pages = $this->getRedirects();
        $url = $this->normalizeUrl($url);

        if (isset($pages[$url])) {
            return $pages[$url];
        }
    }

    /**
     * Regenerates the manifest by scanning the base path.
     *
     * @param bool $cache
     */
    public function regenerate($cache = true)
    {
        $finder = new DocumentationManifestFileFinder();
        $finder->setOptions(
            array(
            'dir_callback' => array($this, 'handleFolder'),
            'file_callback'  => array($this, 'handleFile')
            )
        );

        $this->redirects = array();
        foreach ($this->getEntities() as $entity) {
            $this->entity = $entity;

            $this->handleFolder('', $this->entity->getPath(), 0);
            $finder->find($this->entity->getPath());
        }

        // groupds
        $grouped = array();

        foreach ($this->pages as $url => $page) {
            if (!isset($grouped[$page['entitypath']])) {
                $grouped[$page['entitypath']] = array();
            }

            $grouped[$page['entitypath']][$url] = $page;
        }

        $this->pages = array();

        foreach ($grouped as $entity) {
            uasort(
                $entity,
                function ($a, $b) {
                    // ensure parent directories are first
                    $a['filepath'] = str_replace('index.md', '', $a['filepath']);
                    $b['filepath'] = str_replace('index.md', '', $b['filepath']);

                    if (strpos($b['filepath'], $a['filepath']) === 0) {
                        return -1;
                    }

                    if ($a['filepath'] == $b['filepath']) {
                        return 0;
                    }

                    return ($a['filepath'] < $b['filepath']) ? -1 : 1;
                }
            );

            $this->pages = array_merge($this->pages, $entity);
        }

        if ($cache) {
            $this->cache->set(
                $this->cacheKey,
                array(
                    'pages' => $this->pages,
                    'redirects' => $this->redirects
                )
            );
        }

        $this->inited = true;
    }

    /**
     * Remove the link_base from the start of a link
     *
     * @param  string $link
     * @return string
     */
    protected function stripLinkBase($link)
    {
        // Trim baseURL
        $link = preg_replace('/^' . preg_quote(Director::baseURL(), '/') .'/', '', $link);

        // Trim link_base
        if ($linkBase = Config::inst()->get(DocumentationViewer::class, 'link_base')) {
            $link = preg_replace('/^' . preg_quote($linkBase, '/') .'\/?/', '', $link);
        }

        return $link;
    }

    /**
     *
     * @param DocumentationPage $page
     * @param string            $basename
     * @param string            $path
     */
    protected function addPage($page, $basename, $path)
    {
        $link = $this->stripLinkBase($page->Link());

        $this->pages[$link] = array(
            'title'      => $page->getTitle(),
            'version'    => $page->getVersion(),
            'basename'   => $basename,
            'filepath'   => DocumentationHelper::normalizePath($path),
            'type'       => get_class($page),
            'entitypath' => $this->entity->getPath(),
            'summary'    => $page->getSummary()
        );
    }

    /**
     * Add a redirect
     *
     * @param string $from
     * @param string $to
     */
    protected function addRedirect($from, $to)
    {
        $fromLink = $this->stripLinkBase($from);
        $toLink = $this->stripLinkBase($to);

        // If the redirect "from" is already registered with a "to", don't override it. This ensures
        // that the first version processed is treated as the canonical version.
        if (!isset($this->redirects[$fromLink])) {
            $this->redirects[$fromLink] = $toLink;
        }
    }

    /**
     *
     */
    public function handleFolder($basename, $path, $depth)
    {
        $folder = Injector::inst()->create(
            DocumentationFolder::class,
            $this->entity,
            $basename,
            $path
        );

        // Add main folder link
        $fullLink = $folder->Link();
        $this->addPage($folder, $basename, $path);

        // Add alternative link
        $shortLink = $folder->Link(true);
        if ($shortLink != $fullLink) {
            $this->addRedirect($shortLink, $fullLink);
        }
    }

    /**
     * Individual files can optionally provide a nice title and a better URL
     * through the use of markdown meta data. This creates a new
     * {@link DocumentationPage} instance for the file.
     *
     * If the markdown does not specify the title in the meta data it falls back
     * to using the file name.
     *
     * @param string $basename
     * @param string $path
     * @param int    $depth
     */
    public function handleFile($basename, $path, $depth)
    {
        $page = Injector::inst()->create(
            DocumentationPage::class,
            $this->entity,
            $basename,
            $path
        );

        // populate any meta data
        $page->getMarkdown();

        // Add main link
        $fullLink = $page->Link();
        $this->addPage($page, $basename, $path);

        // If this is a stable version, add the short link
        $shortLink = $page->Link(true);
        if ($fullLink != $shortLink) {
            $this->addRedirect($shortLink, $fullLink);
        }
    }

    /**
     * Generate an {@link ArrayList} of the pages to the given page.
     *
     * @param DocumentationPage
     * @param DocumentationEntityLanguage
     *
     * @return ArrayList
     */
    public function generateBreadcrumbs($record, $base)
    {
        $output = new ArrayList();

        $parts = explode('/', trim($record->getRelativeLink(), '/'));

        // Add the base link.
        $output->push(
            new ArrayData(
                array(
                'Link' => $base->Link(),
                'Title' => $base->Title
                )
            )
        );

        $progress = $base->Link();

        foreach ($parts as $part) {
            if ($part) {
                $progress = Controller::join_links($progress, $part, '/');

                $output->push(
                    new ArrayData(
                        array(
                        'Link' => $progress,
                        'Title' => DocumentationHelper::clean_page_name($part)
                        )
                    )
                );
            }
        }

        return $output;
    }

    /**
     * Create a clean domain-relative URL form a docuetn URL
     */
    protected function buildUrl($url)
    {
        return Controller::join_links(
            Director::baseURL(),
            Config::inst()->get(DocumentationViewer::class, 'link_base'),
            $url,
            '/'
        );
    }

    /**
     * Determine the next page from the given page.
     *
     * Relies on the fact when the manifest was built, it was generated in
     * order.
     *
     * @param string $filepath
     * @param string $entityBase
     *
     * @return ArrayData
     */
    public function getNextPage($filepath, $entityBase)
    {
        $grabNext = false;
        $fallback = null;

        foreach ($this->getPages() as $url => $page) {
            if ($grabNext && strpos($page['filepath'], $entityBase) !== false) {
                return new ArrayData(
                    array(
                    'Link' => $this->buildUrl($url),
                    'Title' => $page['title']
                    )
                );
            }

            if ($filepath == $page['filepath']) {
                $grabNext = true;
            } elseif (!$fallback && strpos($page['filepath'], $filepath) !== false) {
                $fallback = new ArrayData(
                    array(
                    'Link' => $this->buildUrl($url),
                    'Title' => $page['title'],
                    'Fallback' => true
                    )
                );
            }
        }

        if (!$grabNext) {
            return $fallback;
        }

        return null;
    }

    /**
     * Determine the previous page from the given page.
     *
     * Relies on the fact when the manifest was built, it was generated in
     * order.
     *
     * @param string $filepath
     * @param string $entityBase
     *
     * @return ArrayData
     */
    public function getPreviousPage($filepath, $entityPath)
    {
        $previousUrl = $previousPage = null;

        foreach ($this->getPages() as $url => $page) {
            if ($filepath == $page['filepath']) {
                if ($previousUrl) {
                    return new ArrayData(
                        array(
                        'Link' => $this->buildUrl($previousUrl),
                        'Title' => $previousPage['title']
                        )
                    );
                }
            }

            if (strpos($page['filepath'], $entityPath) !== false) {
                $previousUrl = $url;
                $previousPage = $page;
            }
        }

        return null;
    }

    /**
     * @param string
     *
     * @return string
     */
    public function normalizeUrl($url)
    {
        $url = trim($url, '/') .'/';

        // if the page is the index page then hide it from the menu
        if (strpos(strtolower($url), '/index.md/')) {
            $url = substr($url, 0, strpos($url, "index.md/"));
        }

        return $url;
    }

    /**
     * Return the children of the provided record path.
     *
     * Looks for any pages in the manifest which have one more slash attached.
     *
     * @param string $path
     *
     * @return ArrayList
     */
    public function getChildrenFor($entityPath, $recordPath = null)
    {
        if (!$recordPath) {
            $recordPath = $entityPath;
        }

        $output = new ArrayList();
        $entityPath = $this->normalizeUrl($entityPath);
        $recordPath = $this->normalizeUrl($recordPath);
        $recordParts = explode('/', trim($recordPath, '/'));
        $currentRecordPath = end($recordParts);
        $depth = substr_count($entityPath, '/');

        foreach ($this->getPages() as $url => $page) {
            $pagePath = $this->normalizeUrl($page['filepath']);

            // check to see if this page is under the given path
            if (strpos($pagePath, $entityPath) === false) {
                continue;
            }

            // only pull it up if it's one more level depth
            if (substr_count($pagePath, '/') == ($depth + 1)) {
                $pagePathParts = explode('/', trim($pagePath, '/'));
                $currentPagePath = end($pagePathParts);
                if ($currentPagePath == $currentRecordPath) {
                    $mode = 'current';
                } elseif (strpos($recordPath, $pagePath) !== false) {
                    $mode = 'section';
                } else {
                    $mode = 'link';
                }

                $children = new ArrayList();

                if ($mode == 'section' || $mode == 'current') {
                    $children = $this->getChildrenFor($pagePath, $recordPath);
                }

                $output->push(
                    new ArrayData(
                        array(
                        'Link' => $this->buildUrl($url),
                        'Title' => $page['title'],
                        'LinkingMode' => $mode,
                        'Summary' => $page['summary'],
                        'Children' => $children
                        )
                    )
                );
            }
        }

        return $output;
    }

    /**
     * @param DocumentationEntity
     *
     * @return ArrayList
     */
    public function getAllVersionsOfEntity(DocumentationEntity $entity)
    {
        $all = new ArrayList();

        foreach ($this->getEntities() as $check) {
            if ($check->getKey() == $entity->getKey()) {
                if ($check->getLanguage() == $entity->getLanguage()) {
                    $all->push($check);
                }
            }
        }

        return $all;
    }

    /**
     * @param DocumentationEntity
     *
     * @return DocumentationEntity
     */
    public function getStableVersion(DocumentationEntity $entity)
    {
        foreach ($this->getEntities() as $check) {
            if ($check->getKey() == $entity->getKey()) {
                if ($check->getLanguage() == $entity->getLanguage()) {
                    if ($check->getIsStable()) {
                        return $check;
                    }
                }
            }
        }

        return $entity;
    }

    /**
     * @param DocumentationEntity $entity
     * @return ArrayList
     */
    public function getVersions($entity)
    {
        if (!$entity) {
            return null;
        }

        $output = new ArrayList();

        /**
         * @var DocumentationEntity $check
         */
        foreach ($this->getEntities() as $check) {
            if ($check->getKey() == $entity->getKey()) {
                if ($check->getLanguage() == $entity->getLanguage()) {
                    $same = ($check->getVersion() == $entity->getVersion());

                    $output->push(
                        new ArrayData(
                            array(
                            'Title' => $check->getVersionTitle(),
                            'Version' => $check->getVersion(),
                            'Archived' => $check->getIsArchived(),
                            'Link' => $check->Link(),
                            'LinkingMode' => ($same) ? 'current' : 'link',
                            'IsStable' => $check->getIsStable()
                            )
                        )
                    );
                }
            }
        }

        return $output;
    }

    /**
     * Returns a sorted array of all the unique versions registered
     */
    public function getAllVersions()
    {
        $versions = array();

        foreach ($this->getEntities() as $entity) {
            if ($entity->getVersion()) {
                $versions[$entity->getVersion()] = $entity->getVersion();
            } else {
                $versions['0.0'] = _t('SilverStripe\\DocsViewer\\DocumentationManifest.MASTER', 'Master');
            }
        }

        asort($versions);

        return $versions;
    }

    /**
     * Gets whether there is a default entity or not
     *
     * @return boolean
     */
    public function getHasDefaultEntity()
    {
        return $this->has_default_entity;
    }
}
