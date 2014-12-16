<?php

class DocumentationManifestFileFinder extends SS_FileFinder {

	/**
	 * @var array
	 */
	private static $ignored_files = array(
		'.', '..', '.ds_store', 
		'.svn', '.git', 'assets', 'themes', '_images'
	);

	/**
	 * @var array
	 */
	protected static $default_options = array(
		'name_regex'           => '/\.(md|markdown)$/i',
		'file_callback'        => null,
		'dir_callback'         => null,
		'ignore_vcs'           => true
	);

	/**
	 * 
	 */
	public function acceptDir($basename, $pathname, $depth) {
		$ignored =  Config::inst()->get('DocumentationManifestFileFinder', 'ignored_files');

		if($ignored) {
			if(in_array(strtolower($basename), $ignored)) {
				return false;
			}
		}

		return true;
	}

}