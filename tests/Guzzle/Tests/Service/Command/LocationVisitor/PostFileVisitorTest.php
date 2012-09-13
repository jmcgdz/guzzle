<?php

namespace Guzzle\Tests\Service\Command\LocationVisitor;

use Guzzle\Http\Message\PostFile;
use Guzzle\Service\Command\LocationVisitor\PostFileVisitor as Visitor;

/**
 * @covers Guzzle\Service\Command\LocationVisitor\PostFileVisitor
 */
class PostFileVisitorTest extends AbstractVisitorTestCase
{
    public function testVisitsLocation()
    {
        $visitor = new Visitor();
        $param = $this->getNestedCommand('post_file')->getParam('foo');

        // Test using a path to a file
        $visitor->visit($param->setLocationKey('test_3'), $this->request, __FILE__);
        $this->assertInternalType('array', $this->request->getPostFile('test_3'));

        // Test with a PostFile
        $visitor->visit($param->setLocationKey(null), $this->request, new PostFile('baz', __FILE__));
        $this->assertInternalType('array', $this->request->getPostFile('baz'));
    }
}
