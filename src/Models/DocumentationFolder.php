<?php
namespace SilverStripe\DocsViewer\Models;


/**
 * A specific documentation folder within a {@link DocumentationEntity}.
 *
 * Maps to a folder on the file system.
 *
 * @package    docsviewer
 * @subpackage model
 */
class DocumentationFolder extends DocumentationPage
{
    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->getTitleFromFolder();
    }
}
