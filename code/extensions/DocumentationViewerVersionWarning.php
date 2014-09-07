<?php

/**
 * Check to see if the currently accessed version is out of date or
 * perhaps a future version rather than the stable edition
 *
 * @return false|ArrayData
 */

class DocumentationViewerVersionWarning extends Extension {

	public function VersionWarning() {
		$page = $this->owner->getPage();
		$version = $this->owner->getVersion();
		$versions = $this->owner->getVersions();

		if($page && $verions->count() > 0) {
			$stable = $this->owner->getStableVersion();
			$compare = $version->compare($stable);
			
			// same
			if($version == $stable) {
				return false;
			}
			
			if($version == "master" || $compare > 0) {
				return $this->customise(new ArrayData(array(
					'FutureRelease' => true,
					'StableVersion' => DBField::create_field('HTMLText', $stable)
				)));				
			}
			else {
				return $this->customise(new ArrayData(array(
					'OutdatedRelease' => true,
					'StableVersion' => DBField::create_field('HTMLText', $stable)
				)));
			}
		}
		
		return false;
	}
}