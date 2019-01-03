<?php
namespace SilverStripe\DocsViewer\Tasks;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\DocsViewer\DocumentationManifest;


/**
 * Check status of sources dirs
 */
class CheckDocsSourcesTask extends BuildTask
{

    protected $errors = 0;

    protected $description = "Check validity of all docs source files registered";

    public function start()
    {
        if (!Director::is_cli()) {
            echo "<ul>";
        }
    }

    public function end()
    {
        if (Director::is_cli()) {
            echo "\nTotal errors: {$this->errors}\n";
        } else {
            echo "</ul>";
            echo "<p>Total errors: {$this->errors}</p>";
        }
    }

    public function showError($error)
    {
        $this->errors++;
        if (Director::is_cli()) {
            echo "\n$error";
        } else {
            echo "<li>" . Convert::raw2xml($error) . "</li>";
        }
    }

    /**
     * Validate all source files
     *
     * @param  SS_HTTPRequest $request
     * @throws Exception
     */
    public function run($request)
    {
        $this->start();
        $registered = Config::inst()->get(DocumentationManifest::class, 'register_entities');
        foreach ($registered as $details) {
            // validate the details provided through the YAML configuration
            $required = array('Path', 'Title');

            // Check required configs
            foreach ($required as $require) {
                if (!isset($details[$require])) {
                    $this->showError("$require is a required key in DocumentationManifest.register_entities");
                }
            }

            // Check path is loaded
            $path = $this->getRealPath($details['Path']);
            if (!$path || !is_dir($path)) {
                $this->showError($details['Path'] . ' is not a valid documentation directory');
            }
        }
        $this->end();
    }

    public function getRealPath($path)
    {
        if (!Director::is_absolute($path)) {
            $path = Controller::join_links(BASE_PATH, $path);
        }

        return $path;
    }
}
