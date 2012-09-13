<?php

namespace Guzzle\Tests\Service\Command\LocationVisitor;

use Guzzle\Common\Collection;
use Guzzle\Service\Command\LocationVisitor\QueryVisitor as Visitor;

/**
 * @covers Guzzle\Service\Command\LocationVisitor\QueryVisitor
 */
class QueryVisitorTest extends AbstractVisitorTestCase
{
    public function testVisitsLocation()
    {
        $visitor = new Visitor();
        $param = $this->getNestedCommand('query')->getParam('foo')->setLocationKey('test');
        $visitor->visit($param, $this->request, '123');
        $this->assertEquals('123', $this->request->getQuery()->get('test'));
    }

    /**
     * @covers Guzzle\Service\Command\LocationVisitor\QueryVisitor
     * @covers Guzzle\Service\Command\LocationVisitor\AbstractVisitor::resolveRecursively
     */
    public function testRecursivelyBuildsQueryStrings()
    {
        $command = $this->getNestedCommand('query');
        $data = new Collection();
        $command->validate($data);
        $visitor = new Visitor();
        $param = $this->getNestedCommand('query')->getParam('foo');
        $visitor->visit($param->setLocationKey('Foo'), $this->request, $data['foo']);
        $visitor->after($this->command, $this->request);
        $this->assertEquals(
            '?Foo[test][baz]=1&Foo[test][Jenga_Yall!]=HELLO&Foo[bar]=123',
            rawurldecode((string) $this->request->getQuery())
        );
    }
}
