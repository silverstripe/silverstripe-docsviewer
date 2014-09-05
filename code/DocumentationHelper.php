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
}