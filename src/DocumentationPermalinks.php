<?php
namespace SilverStripe\DocsViewer;

use SilverStripe\ORM\ArrayLib;


/**
 * A mapping store of given permalinks to the full documentation path or useful
 * for customizing the shortcut URLs used in the viewer.
 *
 * Redirects the user from example.com/foo to example.com/en/module/foo
 *
 * @package docsviewer
 */

class DocumentationPermalinks
{
    /**
     * @var array
     */
    private static $mapping = array();
    
    /**
     * Add a mapping of nice short permalinks to a full long path
     *
     * <code>
     * DocumentationPermalinks::add(array(
     *     'debugging' => 'current/en/sapphire/topics/debugging'
     * ));
     * </code>
     *
     * Do not need to include the language or the version current as it
     * will add it based off the language or version in the session
     *
     * @param array
     */
    public static function add($map = array())
    {
        if (ArrayLib::is_associative($map)) {
            self::$mapping = array_merge(self::$mapping, $map);
        } else {
            user_error("DocumentationPermalinks::add() requires an associative array", E_USER_ERROR);
        }
    }
    
    /**
     * Return the location for a given short value.
     *
     * @return string|false
     */
    public static function map($url)
    {
        return (isset(self::$mapping[$url])) ? self::$mapping[$url] : false;
    }
}
