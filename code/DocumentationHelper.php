<?php

/**
 * Collection of static helper methods for managing the documentation
 *
 * @package docsviewer
 */
class DocumentationHelper {
	
	/**
	 * Generate an array of every single documentation page installed on the 
	 * system. 
	 *
	 * @return ArrayList
	 */
	public static function get_all_documentation_pages() {
		DocumentationService::load_automatic_registration();
		
		$modules = DocumentationService::get_registered_entities();
		$output = new ArrayList();

		if($modules) {
			foreach($modules as $module) {
				foreach($module->getVersions() as $version) {
					try {
						$pages = DocumentationService::get_pages_from_folder($module, false, true, $version);
						
						if($pages) {
							foreach($pages as $page) {
								$output->push($page);
							}
						}
					}
					catch(Exception $e) {
						user_error($e, E_USER_WARNING);
					}
				}
			}
		}

		return $output;
	}

		
	/**
	 * String helper for cleaning a file name to a readable version. 
	 *
	 * @param string $name to convert
	 *
	 * @return string $name output
	 */
	public static function clean_page_name($name) {
		$name = self::trim_extension_off($name);
		$name = self::trim_sort_number($name);

		$name = str_replace(array('-', '_'), ' ', $name);
			

		return ucwords(trim($name));
	}

	/**
	 * String helper for cleaning a file name to a URL safe version.
	 *
	 * @param string $name to convert
	 *
	 * @return string $name output
	 */
	public static function clean_page_url($name) {
		$name = str_replace(array(' '), '_', $name);

		$name = self::trim_extension_off($name);
		$name = self::trim_sort_number($name);

		if(preg_match('/^[\/]?index[\/]?/', $name)) {
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
	public static function trim_sort_number($name) {
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
	public static function trim_extension_off($name) {
		if(strrpos($name,'.') !== false) {
			return substr($name, 0, strrpos($name,'.'));
		}
		
		return $name;
	}
}