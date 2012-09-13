<?php

namespace Guzzle\Tests\Service\Command\LocationVisitor;

use Guzzle\Common\Collection;
use Guzzle\Service\Command\LocationVisitor\JsonBodyVisitor as Visitor;
use Guzzle\Service\Description\ApiParam;
use Guzzle\Service\Description\ApiCommand;

/**
 * @covers Guzzle\Service\Command\LocationVisitor\JsonBodyVisitor
 */
class JsonBodyVisitorTest extends AbstractVisitorTestCase
{
    public function testVisitsLocation()
    {
        $visitor = new Visitor();
        // Test after when no body query values were found
        $visitor->after($this->command, $this->request);

        $param = $this->getNestedCommand('json')->getParam('foo');
        $visitor->visit($param->setLocationKey('test'), $this->request, '123');
        $visitor->visit($param->setLocationKey('test2'), $this->request, 'abc');
        $visitor->after($this->command, $this->request);
        $this->assertEquals('{"test":"123","test2":"abc"}', (string) $this->request->getBody());
    }

    public function testAddsJsonHeader()
    {
        $visitor = new Visitor();
        $visitor->setContentTypeHeader('application/json-foo');
        $param = $this->getNestedCommand('json')->getParam('foo');
        $visitor->visit($param->setLocationKey('test'), $this->request, '123');
        $visitor->after($this->command, $this->request);
        $this->assertEquals('application/json-foo', (string) $this->request->getHeader('Content-Type'));
    }

    /**
     * @covers Guzzle\Service\Command\LocationVisitor\JsonBodyVisitor
     * @covers Guzzle\Service\Command\LocationVisitor\AbstractVisitor::resolveRecursively
     */
    public function testRecursivelyBuildsJsonBodies()
    {
        $command = $this->getNestedCommand('json');
        $data = new Collection();
        $command->validate($data);
        $visitor = new Visitor();
        $param = $this->getNestedCommand('json')->getParam('foo');
        $visitor->visit($param->setLocationKey('Foo'), $this->request, $data['foo']);
        $visitor->after($this->command, $this->request);
        $this->assertEquals('{"Foo":{"test":{"baz":true,"Jenga_Yall!":"HELLO"},"bar":123}}', (string) $this->request->getBody());
    }
}
