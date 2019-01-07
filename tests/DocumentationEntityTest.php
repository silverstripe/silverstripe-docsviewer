<?php
namespace SilverStripe\DocsViewer\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\DocsViewer\Models\DocumentationEntity;


class DocumentationEntityTest extends SapphireTest
{
    public function dataCompare()
    {
        return array(
            array('3', '3.0', 1),
            array('3.1', '3.1', 0),
            array('3.0', '3', -1),
            array('4', '3', 1),
            array('3', '4', -1),
            array('3.4.1', '4', -1)
        );
    }

    /**
     * @dataProvider dataCompare
     * @param string $left
     * @param string $right
     * @param int    $result
     */
    public function testCompare($left, $right, $result)
    {
        $leftVersion = new DocumentationEntity('Framework');
        $leftVersion->setVersion($left);

        $rightVersion = new DocumentationEntity('Framework');
        $rightVersion->setVersion($right);

        $this->assertEquals($result, $leftVersion->compare($rightVersion));
    }
}
