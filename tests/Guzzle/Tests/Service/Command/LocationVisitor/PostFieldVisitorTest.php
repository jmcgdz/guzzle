<?php

namespace Guzzle\Tests\Service\Command\LocationVisitor;

use Guzzle\Common\Collection;
use Guzzle\Service\Command\LocationVisitor\PostFieldVisitor as Visitor;

/**
 * @covers Guzzle\Service\Command\LocationVisitor\PostFieldVisitor
 */
class PostFieldVisitorTest extends AbstractVisitorTestCase
{
    public function testVisitsLocation()
    {
        $visitor = new Visitor();
        $param = $this->getNestedCommand('post_field')->getParam('foo');
        $visitor->visit($param->setLocationKey('test'), $this->request, '123');
        $this->assertEquals('123', (string) $this->request->getPostField('test'));
    }

    public function testRecursivelyBuildsPostFields()
    {
        $command = $this->getNestedCommand('post_field');
        $data = new Collection();
        $command->validate($data);
        $visitor = new Visitor();
        $param = $this->getNestedCommand('post_field')->getParam('foo');
        $visitor->visit($param->setLocation('Foo'), $this->request, $data['foo']);
        $visitor->after($this->command, $this->request);
        $this->assertEquals(
            'Foo[test][baz]=1&Foo[test][Jenga_Yall!]=HELLO&Foo[bar]=123',
            rawurldecode((string) $this->request->getPostFields())
        );
    }
}
