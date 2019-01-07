<?php
namespace SilverStripe\DocsViewer\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\DocsViewer\DocumentationHelper;


/**
 * @package docsviewer
 * @subpackage tests
 */
class DocumentationHelperTests extends SapphireTest
{
    public function testCleanName()
    {
        $this->assertEquals(
            'File path',
            DocumentationHelper::clean_page_name(
                '00_file-path.md'
            )
        );
    }

    public function testCleanUrl()
    {
        $this->assertEquals(
            'some_path',
            DocumentationHelper::clean_page_url(
                'Some Path'
            )
        );

        $this->assertEquals(
            'somefilepath',
            DocumentationHelper::clean_page_url(
                '00_SomeFilePath.md'
            )
        );
    }

    public function testTrimSortNumber()
    {
        $this->assertEquals(
            'file',
            DocumentationHelper::trim_sort_number(
                '0_file'
            )
        );

        $this->assertEquals(
            '2.1',
            DocumentationHelper::trim_sort_number(
                '2.1'
            )
        );

        $this->assertEquals(
            'dev/tasks/2.1',
            DocumentationHelper::trim_sort_number(
                'dev/tasks/2.1'
            )
        );
    }

    public function testTrimExtension()
    {
        $this->assertEquals(
            'file',
            DocumentationHelper::trim_extension_off(
                'file.md'
            )
        );

        $this->assertEquals(
            'dev/path/file',
            DocumentationHelper::trim_extension_off(
                'dev/path/file.md'
            )
        );
    }

    public function testGetExtension()
    {
        $this->assertEquals(
            'md',
            DocumentationHelper::get_extension(
                'file.md'
            )
        );

        $this->assertEquals(
            'md',
            DocumentationHelper::get_extension(
                'dev/tasks/file.md'
            )
        );

        $this->assertEquals(
            'txt',
            DocumentationHelper::get_extension(
                'dev/tasks/file.txt'
            )
        );

        $this->assertNull(
            DocumentationHelper::get_extension(
                'doc_test/2.3'
            )
        );

        $this->assertNull(
            DocumentationHelper::get_extension(
                'dev/docs/en/doc_test/2.3/subfolder'
            )
        );
    }
}
