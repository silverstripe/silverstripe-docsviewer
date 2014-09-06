<?php

/**
 * DocumentationService
 *
 * Handles the management of the documentation services delivered by the entity.
 * 
 * Includes registering which components to document and handles the entities 
 * being documented.
 *
 * @package docsviewer
 */

class DocumentationService {

	/**
	 * Registered {@link DocumentationEntity} objects to include in the 
	 * documentation. 
	 *
	 * Either pre-filled by the automatic filesystem parser or via 
	 * {@link DocumentationService::register()}. 
	 *
	 * Stores the {@link DocumentEntity} objects which contain the languages 
	 * and versions of each entity.
	 *
	 * You can remove registered {@link DocumentationEntity} objects by using 
	 * {@link DocumentationService::unregister()}
	 *
	 * @var array
	 */
	private static $registered_entities = array();
	
	
	/**
	 * Should generation of documentation categories be automatic? 
	 *
	 * If this is set to true then it will generate {@link DocumentationEntity}
	 * objects from the filesystem. This can be slow and also some projects 
	 * may want to restrict to specific project folders (rather than everything).
	 *
	 * You can also disable or remove a given folder from registration using 
	 * {@link DocumentationService::unregister()}
	 *
	 * @see DocumentationService::$registered_entities
	 * @see DocumentationService::set_automatic_registration();
	 *
	 * @var bool
	 */
	private static $automatic_registration = true;

	/**
	 * Set automatic registration of entities and documentation folders
	 *
	 * @see DocumentationService::$automatic_registration
	 * @param bool
	 */
	public static function set_automatic_registration($bool = true) {
		self::$automatic_registration = $bool;
		
		if(!$bool) {
			// remove current registed entities when disabling automatic 
			// registration needed to avoid caching issues when running all the 
			// tests
			self::$registered_entities = array();
		}
	}
	
	/**
	 * Is automatic registration of entities enabled.
	 *
	 * @return bool
	 */
	public static function automatic_registration_enabled() {
		return self::$automatic_registration;
	}
	
	/**
	 * Return the entities which are listed for documentation. Optionally only 
	 * get entities which have a version or language given.
	 *
	 * @return array
	 */
	public static function get_registered_entities($version = false, $lang = false) {
		$output = array();
		
		if($entities = self::$registered_entities) {
			if($version || $lang) {
				foreach($entities as $entity) {
					if(self::is_registered_entity($entity->getFolder(), $version, $lang)) {
						$output[$entity->getFolder()] = $entity;
					}
				}
			}
			else {
				$output = $entities;
			}
		}
		
		return $output;
	}
	
	/**
	 * Register a entity to be included in the documentation. To unregister a 
	 * entity use {@link DocumentationService::unregister()}. 
	 *
	 * @param string $entity Name of entity to register
	 * @param string $path Path to documentation root.
	 * @param float $version Version of entity.
	 * @param string $title Nice title to use
	 * @param bool $latest - return is this the latest release.
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return DocumentationEntity
	 */
	public static function register($entity, $path, $version = '', $title = false, $latest = false) {
		if(!file_exists($path)) {
			throw new InvalidArgumentException(sprintf('Path "%s" doesn\'t exist', $path));
		}
		
		// add the entity to the registered array
		if(!isset(self::$registered_entities[$entity])) {
			$de = new DocumentationEntity($entity, $title);

			self::$registered_entities[$entity] = $de;
		}
		else {
			// entity exists so add the version to it
			$de = self::$registered_entities[$entity];
		}

		// create a new version of the entity and attach it the the entity
		$dve = new DocumentationEntityVersion($de, $path, $version, $latest);
		$de->addVersion($dve);

		return $de;
	}
	
	/**
	 * Unregister a entity from being included in the documentation. Useful
	 * for keeping {@link DocumentationService::$automatic_registration} enabled
	 * but disabling entities which you do not want to show. Combined with a 
	 * {@link Director::isLive()} you can hide entities you don't want a client 
	 * to see.
	 *
	 * If no version or lang specified then the whole entity is removed. 
	 * Otherwise only the specified version of the documentation.
	 *
	 * @param string $entity
	 * @param string $version
	 *
	 * @return bool
	 */
	public static function unregister($entityName, $version = false) {
		if(isset(self::$registered_entities[$entityName])) {
			$entity = self::$registered_entities[$entityName];
			
			if($version) {
				$entity->removeVersion($version);
			} else {
				unset(self::$registered_entities[$entityName]);	
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Register the docs from off a file system if automatic registration is 
	 * turned on.
	 *
	 * @see {@link DocumentationService::set_automatic_registration()}
	 */
	public static function load_automatic_registration() {
		if(!self::automatic_registration_enabled()) {
			return;
		}

		$entities = scandir(BASE_PATH);

		if($entities) {
			foreach($entities as $key => $entity) {
				$dir = is_dir(Controller::join_links(BASE_PATH, $entity));
				
				if($dir) {
					// check to see if it has docs
					$docs = Director::baseFolder() . '/' . Controller::join_links($entity, 'docs');

					if(is_dir($docs)) {
						self::register($entity, $docs, 'current', $entity, true);
					}
				}
			}
		}
	}
}
