<?php
namespace SilverStripe\DocsViewer;

use SilverStripe\Control\Director;


/**
 * Collection of static helper methods for managing the documentation
 *
 * @package docsviewer
 */
class DocumentationHelper
{
    /**
     * String helper for cleaning a file name to a readable version.
     *
     * @param string $name to convert
     *
     * @return string $name output
     */
    public static function clean_page_name($name)
    {
        $name = self::trim_extension_off($name);
        $name = self::trim_sort_number($name);

        $name = str_replace(array('-', '_'), ' ', $name);

        return ucfirst(trim($name));
    }

    /**
     * String helper for cleaning a file name to a URL safe version.
     *
     * @param string $name to convert
     *
     * @return string $name output
     */
    public static function clean_page_url($name)
    {
        $name = str_replace(array(' '), '_', $name);

        $name = self::trim_extension_off($name);
        $name = self::trim_sort_number($name);

        if (preg_match('/^[\/]?index[\/]?/', $name)) {
            return '';
        }

        return strtolower($name);
    }

    /**
     * Removes leading numbers from pages (used to control sort order).
     *
     * @param string
     *
     * @return string
     */
    public static function trim_sort_number($name)
    {
        $name = preg_replace("/^[0-9]*[_-]+/", '', $name);

        return $name;
    }
        

    /**
     * Helper function to strip the extension off and return the name without
     * the extension.
     *
     * @param string
     *
     * @return string
     */
    public static function trim_extension_off($name)
    {
        if (strrpos($name, '.') !== false) {
            return substr($name, 0, strrpos($name, '.'));
        }
        
        return $name;
    }

    /**
     * Helper function to get the extension of the filename.
     *
     * @param string
     *
     * @return string
     */
    public static function get_extension($name)
    {
        if (preg_match('/\.[a-z]+$/', $name)) {
            return substr($name, strrpos($name, '.') + 1);
        }

        return null;
    }

    /**
     * Helper function to normalize paths to unix style directory separators
     *
     * @param string
     *
     * @return string
     */
    public static function normalizePath($path)
    {
        if (DIRECTORY_SEPARATOR != '/') {
            return str_replace(DIRECTORY_SEPARATOR, '/', $path);
        }
        
        return $path;
    }

    /**
     * Helper function to make normalized paths relative
     *
     * @param string
     *
     * @return string
     */
    public static function relativePath($path)
    {
        $base = self::normalizePath(Director::baseFolder());
        
        return substr($path, strlen($base));
    }
}
