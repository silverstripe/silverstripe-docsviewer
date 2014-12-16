<?php

class DocumentationBuild extends BuildTask {
	
	public function run($request) {
		$manifest = new DocumentationManifest(true);
		echo "<pre>";
		print_r($manifest->getPages());
		echo "</pre>";
		die();;


	}
}