<?php

/**
 * A specific documentation folder within a {@link DocumentationEntity}. 
 *
 * Maps to a folder on the file system. 
 *
 * @package docsviewer
 * @subpackage model
 */
class DocumentationFolder extends DocumentationPage {

	/**
	 * @return string
	 */
	public function getTitle() {
		$path = explode(DIRECTORY_SEPARATOR, trim($this->getPath(), DIRECTORY_SEPARATOR));
		$folderName = array_pop($path);

		return DocumentationHelper::clean_page_name($folderName);
	}

}