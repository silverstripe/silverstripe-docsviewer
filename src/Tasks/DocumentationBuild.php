<?php
namespace SilverStripe\DocsViewer\Tasks;

use SilverStripe\Dev\BuildTask;
use SilverStripe\DocsViewer\DocumentationManifest;


class DocumentationBuild extends BuildTask
{
    public function run($request)
    {
        $manifest = new DocumentationManifest(true);
        echo "<pre>";
        print_r($manifest->getPages());
        echo "</pre>";
        die();
        ;
    }
}
