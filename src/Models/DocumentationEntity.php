<?php
namespace SilverStripe\DocsViewer\Models;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\DocsViewer\DocumentationHelper;
use SilverStripe\DocsViewer\Controllers\DocumentationViewer;
use SilverStripe\View\ViewableData;


/**
 * A {@link DocumentationEntity} represents a module or folder with stored
 * documentation files. An entity not an individual page but a `section` of
 * documentation arranged by version and language.
 *
 * Each entity has a version assigned to it (i.e master) and folders can be
 * labeled with a specific version. For instance, doc.silverstripe.org has three
 * DocumentEntities for Framework - versions 2.4, 3.0 and 3.1. In addition an
 * entity can have a language attached to it. So for an instance with en, de and
 * fr documentation you may have three {@link DocumentationEntities} registered.
 *
 * @package    docsviewer
 * @subpackage models
 */

class DocumentationEntity extends ViewableData
{
    /**
     * The key to match entities with that is not localized. For instance, you
     * may have three entities (en, de, fr) that you want to display a nice
     * title for, but matching needs to occur on a specific key.
     *
     * @var string $key
     */
    protected $key;

    /**
     * The human readable title of this entity. Set when the module is
     * registered.
     *
     * @var string $title
     */
    protected $title;

    /**
     * Label for this version
     *
     * @var string
     */
    protected $versionTitle;

    /**
     * If the system is setup to only document one entity then you may only
     * want to show a single entity in the URL and the sidebar. Set this when
     * you register the entity with the key `DefaultEntity` and the URL will
     * not include any version or language information.
     *
     * @var boolean $default_entity
     */
    protected $defaultEntity;

    /**
     * Set if this version is archived
     *
     * @var bool
     */
    protected $archived = false;

    /**
     * @var mixed
     */
    protected $path;

    /**
     * @see {@link http://php.net/manual/en/function.version-compare.php}
     * @var float $version
     */
    protected $version;

    /**
     * The repository branch name (allows for $version to be an alias on development branches).
     *
     * @var string $branch
     */
    protected $branch;

    /**
     * If this entity is a stable release or not. If it is not stable (i.e it
     * could be a past or future release) then a warning message will be shown.
     *
     * @var boolean $stable
     */
    protected $stable;

    /**
     * @var string
     */
    protected $language;

    /**
     * @param string $key Key of module
     */
    public function __construct($key)
    {
        parent::__construct();
        $this->key = DocumentationHelper::clean_page_url($key);
    }


    /**
     * Get the title of this module.
     *
     * @return string
     */
    public function getTitle()
    {
        if (!$this->title) {
            $this->title = DocumentationHelper::clean_page_name($this->key);
        }

        return $this->title;
    }

    /**
     * @param string $title
     * @return $this
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Returns the web accessible link to this entity.
     *
     * Includes the version information
     *
     * @param  boolean $short If true, will attempt to return a short version of the url
     * This might omit the version number if this is the default version.
     * @return string
     */
    public function Link($short = false)
    {
        if ($this->getIsDefaultEntity()) {
            $base = Controller::join_links(
                Director::baseURL(),
                Config::inst()->get(DocumentationViewer::class, 'link_base'),
                $this->getLanguage(),
                '/'
            );
        } else {
            $base = Controller::join_links(
                Director::baseURL(),
                Config::inst()->get(DocumentationViewer::class, 'link_base'),
                $this->getLanguage(),
                $this->getKey(),
                '/'
            );
        }

        if ($short && $this->stable) {
            return $base;
        }

        return Controller::join_links(
            $base,
            $this->getVersion(),
            '/'
        );
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return sprintf('DocumentationEntity: %s)', $this->getPath());
    }

    /**
     * @param DocumentationPage $page
     *
     * @return boolean
     */
    public function hasRecord($page)
    {
        if (!$page) {
            return false;
        }

        return strstr($page->getPath(), $this->getPath()) !== false;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function setIsDefaultEntity($bool)
    {
        $this->defaultEntity = $bool;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsDefaultEntity()
    {
        return $this->defaultEntity;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return string
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string
     *
     * @return $this
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * @param string
     * @return $this
     */
    public function setVersion($version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * @return float
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Get the version for this title
     *
     * @return string
     */
    public function getVersionTitle()
    {
        return $this->versionTitle;
    }

    /**
     * Sets the title for this version
     *
     * @param  string $title
     * @return $this
     */
    public function setVersionTitle($title)
    {
        $this->versionTitle = $title;
        return $this;
    }

    /**
     * Set if this is archived
     *
     * @param  bool $archived
     * @return $this
     */
    public function setIsArchived($archived)
    {
        $this->archived = $archived;
        return $this;
    }

    /**
     * @return bool
     */
    public function getIsArchived()
    {
        return $this->archived;
    }

    /**
     * @param string
     * @return $this
     */
    public function setBranch($branch)
    {
        $this->branch = $branch;

        return $this;
    }

    /**
     * @return float
     */
    public function getBranch()
    {
        return $this->branch;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @param  string $path
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $path;

        return $this;
    }

    /**
     * @param bool
     * @return $this
     */
    public function setIsStable($stable)
    {
        $this->stable = $stable;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsStable()
    {
        return $this->stable;
    }

    /**
     * Returns an integer value based on if a given version is the latest
     * version. Will return -1 for if the version is older, 0 if versions are
     * the same and 1 if the version is greater than.
     *
     * @param  DocumentationEntity $other
     * @return int
     */
    public function compare(DocumentationEntity $other)
    {
        $v1 = $this->getVersion();
        $v2 = $other->getVersion();

        // Normalise versions prior to comparison
        $dots = substr_count($v1, '.') - substr_count($v2, '.');
        while ($dots > 0) {
            $dots--;
            $v2 .= '.99999';
        }
        while ($dots < 0) {
            $dots++;
            $v1 .= '.99999';
        }
        return version_compare($v1, $v2);
    }

    /**
     * @return array
     */
    public function toMap()
    {
        return array(
            'Key'      => $this->key,
            'Path'     => $this->getPath(),
            'Version'  => $this->getVersion(),
            'Branch'   => $this->getBranch(),
            'IsStable' => $this->getIsStable(),
            'Language' => $this->getLanguage()
        );
    }
}
