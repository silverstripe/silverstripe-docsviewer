<?php

/**
 * Check to see if the currently accessed version is out of date or perhaps a 
 * future version rather than the stable edition.
 *
 * @return false|ArrayData
 */

class DocumentationViewerVersionWarning extends Extension {

	public function VersionWarning() {
		$page = $this->owner->getPage();

		if(!$page) {
			return false;
		}
		
		$entity = $page->getEntity();

		if(!$entity) {
			return false;
		}

		$versions = $this->owner->getManifest()->getAllVersionsOfEntity($entity);

		if($entity->getIsStable()) {
			return false;
		}

		$stable = $this->owner->getManifest()->getStableVersion($entity);
		$compare = $entity->compare($stable);
		
		if($entity->getVersion() == "master" || $compare > 0) {
			return $this->owner->customise(new ArrayData(array(
				'FutureRelease' => true,
				'StableVersion' => DBField::create_field('HTMLText', $stable)
			)));				
		}
		else {
			return $this->owner->customise(new ArrayData(array(
				'OutdatedRelease' => true,
				'StableVersion' => DBField::create_field('HTMLText', $stable)
			)));
		}
	
		return false;
	}
}