<?php
namespace SilverStripe\DocsViewer\Controllers;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\DocsViewer\DocumentationSearch;
use SilverStripe\Security\Security;
use SilverStripe\View\ArrayData;


/**
 * Public facing controller for handling an opensearch interface based on
 * the standard search form.
 *
 * @package docsviewer
 */

class DocumentationOpenSearchController extends Controller
{
    private static $allowed_actions = array(
        'description'
    );
    
    public function index()
    {
        return $this->httpError(404);
    }
    
    public function description()
    {
        $viewer = new DocumentationViewer();
        
        if (!$viewer->canView()) {
            return Security::permissionFailure($this);
        }

        if (!Config::inst()->get(DocumentationSearch::class, 'enabled')) {
            return $this->httpError('404');
        }
        
        $data = DocumentationSearch::get_meta_data();
        $link = Director::absoluteBaseUrl() .
        $data['SearchPageLink'] = Controller::join_links(
            $viewer->Link(),
            'results/?Search={searchTerms}&start={startIndex}&length={count}&action_results=1'
        );
        
        $data['SearchPageAtom'] = $data['SearchPageLink'] . '&format=atom';
        
        return $this->customise(
            new ArrayData($data)
        )->renderWith(
            array(
            'OpenSearchDescription'
            )
        );
    }
}
