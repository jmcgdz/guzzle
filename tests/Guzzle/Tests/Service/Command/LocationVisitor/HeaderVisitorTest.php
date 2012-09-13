<?php

namespace Guzzle\Tests\Service\Command\LocationVisitor;

use Guzzle\Service\Command\LocationVisitor\HeaderVisitor as Visitor;

/**
 * @covers Guzzle\Service\Command\LocationVisitor\HeaderVisitor
 */
class HeaderVisitorTest extends AbstractVisitorTestCase
{
    public function testVisitsLocation()
    {
        $visitor = new Visitor();
        $param = $this->getNestedCommand('header')->getParam('foo')->setLocationKey('test');
        $visitor->visit($param, $this->request, '123');
        $this->assertEquals('123', (string) $this->request->getHeader('test'));
    }
}
