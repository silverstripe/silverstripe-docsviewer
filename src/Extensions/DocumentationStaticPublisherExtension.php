<?php
namespace SilverStripe\DocsViewer\Extensions;

use SilverStripe\Core\Extension;
use SilverStripe\DocsViewer\DocumentationManifest;


/**
 * An extension to StaticPublisher to enable exporting the documentation pages
 * as HTML files to the server.
 *
 * If you want to add exporting functionality then install the static publisher
 * module and set the following configuration in your applications config.yml:
 *
 * <code>
 * StaticExporter:
 *   extensions:
 *     - DocumentationStaticPublisherExtension
 * </code>
 *
 * If you don't plan on using static publisher for anything else and you have
 * the cms module installed, make sure you disable that from being published.
 *
 * Again, in your applications config.yml file
 *
 * <code>
 * StaticExporter:
 *   disable_sitetree_export: true
 * </code>
 *
 * @package docsviewer
 */
class DocumentationStaticPublisherExtension extends Extension
{
    public function alterExportUrls(&$urls)
    {
        $manifest = new DocumentationManifest(true);

        foreach ($manifest->getPages() as $url => $page) {
            $urls[$url] = $url;
        }
    }
}
