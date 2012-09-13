<?php

namespace Guzzle\Tests\Service\Description;

use Guzzle\Service\Description\ApiParam;

/**
 * @covers Guzzle\Service\Description\ApiParam
 */
class ApiParamTest extends \Guzzle\Tests\GuzzleTestCase
{
    protected $data = array(
        'name'            => 'foo',
        'type'            => 'bar',
        'required'        => true,
        'default'         => '123',
        'description'     => '456',
        'min'             => 2,
        'max'             => 5,
        'location'        => 'body',
        'static'          => 'static!',
        'filters'         => array('trim', 'json_encode')
    );

    public function testCreatesParamFromArray()
    {
        $p = new ApiParam($this->data);
        $this->assertEquals('foo', $p->getName());
        $this->assertEquals('bar', $p->getType());
        $this->assertEquals(true, $p->getRequired());
        $this->assertEquals('123', $p->getDefault());
        $this->assertEquals('456', $p->getDescription());
        $this->assertEquals(2, $p->getMin());
        $this->assertEquals(5, $p->getMax());
        $this->assertEquals('body', $p->getLocation());
        $this->assertEquals('static!', $p->getStatic());
        $this->assertEquals(array('trim', 'json_encode'), $p->getFilters());
    }

    public function testCanConvertToArray()
    {
        $p = new ApiParam($this->data);
        $this->assertEquals($this->data, $p->toArray());
    }

    public function testUsesStatic()
    {
        $d = $this->data;
        $d['static'] = 'foo';
        $p = new ApiParam($d);
        $this->assertEquals('foo', $p->getValue('bar'));
    }

    public function testUsesDefault()
    {
        $d = $this->data;
        $d['default'] = 'foo';
        $d['static'] = null;
        $p = new ApiParam($d);
        $this->assertEquals('foo', $p->getValue(null));
    }

    public function testReturnsYourValue()
    {
        $d = $this->data;
        $d['static'] = null;
        $p = new ApiParam($d);
        $this->assertEquals('foo', $p->getValue('foo'));
    }

    public function testFiltersValues()
    {
        $d = $this->data;
        $d['static'] = null;
        $d['filters'] = 'strtoupper';
        $p = new ApiParam($d);
        $this->assertEquals('FOO', $p->filter('foo'));
    }

    public function testUsesArrayByDefaultForFilters()
    {
        $d = $this->data;
        $d['filters'] = null;
        $p = new ApiParam($d);
        $this->assertEquals(array(), $p->getFilters());
    }

    public function testAllowsSimpleLocationValueAndDefaultLocationKey()
    {
        $p = new ApiParam(array('name' => 'myname', 'location' => 'foo'));
        $this->assertEquals('foo', $p->getLocation());
        $p->setLocationArgs(array('test' => 123));
        $this->assertEquals(array('test' => 123), $p->getLocationArgs());
    }

    public function testParsesTypeValues()
    {
        $p = new ApiParam(array('type' => 'foo'));
        $this->assertEquals('foo', $p->getType());
    }

    /**
     * @expectedException InvalidArgumentException
     * @expectedExceptionMessage A [method] value must be specified for each complex filter
     */
    public function testValidatesComplexFilters()
    {
        $p = new ApiParam(array('filters' => array(array('args' => 'foo'))));
    }

    public function testCanBuildUpParams()
    {
        $p = new ApiParam(array());
        $p->setName('foo')
            ->setDefault('b')
            ->setDescription('c')
            ->setFilters(array('d'))
            ->setLocation('e')
            ->setLocationKey('f')
            ->setMax(2)
            ->setMin(1)
            ->setRequired(true)
            ->setStatic('h')
            ->setType('i');

        $p->addFilter('foo');

        $this->assertEquals('foo', $p->getName());
        $this->assertEquals('b', $p->getDefault());
        $this->assertEquals('c', $p->getDescription());
        $this->assertEquals(array('d', 'foo'), $p->getFilters());
        $this->assertEquals('e', $p->getLocation());
        $this->assertEquals('f', $p->getLocationKey());
        $this->assertEquals(2, $p->getMax());
        $this->assertEquals(1, $p->getMin());
        $this->assertEquals(true, $p->getRequired());
        $this->assertEquals('h', $p->getStatic());
        $this->assertEquals('i', $p->getType());
    }

    public function testAllowsNestedShape()
    {
        $command = $this->getServiceBuilder()->get('mock')->getCommand('mock_command')->getApiCommand();
        $param = new ApiParam(array(
            'parent'     => $command,
            'name'       => 'foo',
            'type'       => 'object',
            'location'   => 'query',
            'properties' => array(
                'foo' => array(
                    'type'      => 'object',
                    'required'  => true,
                    'properties' => array(
                        'baz' => array(
                            'name' => 'baz',
                            'type' => 'bool',
                        )
                    )
                ),
                'bar' => array(
                    'name'    => 'bar',
                    'default' => '123'
                )
            )
        ));

        $this->assertSame($command, $param->getParent());
        $this->assertNotEmpty($param->getProperties());
        $this->assertInstanceOf('Guzzle\Service\Description\ApiParam', $param->getProperty('foo'));
        $this->assertSame($param, $param->getProperty('foo')->getParent());
        $this->assertSame($param->getProperty('foo'), $param->getProperty('foo')->getProperty('baz')->getParent());
        $this->assertInstanceOf('Guzzle\Service\Description\ApiParam', $param->getProperty('bar'));
        $this->assertSame($param, $param->getProperty('bar')->getParent());

        $array = $param->toArray();
        $this->assertInternalType('array', $array['properties']);
        $this->assertArrayHasKey('foo', $array['properties']);
        $this->assertArrayHasKey('bar', $array['properties']);
    }

    public function testAllowsComplexFilters()
    {
        $that = $this;
        $param = new ApiParam(array());
        $param->setFilters(array(array('method' => function ($a, $b, $c, $d) use ($that, $param) {
            $that->assertEquals('test', $a);
            $that->assertEquals('my_value!', $b);
            $that->assertEquals('bar', $c);
            $that->assertSame($param, $d);
            return 'abc' . $b;
        }, 'args' => array('test', '@value', 'bar', '@api'))));
        $this->assertEquals('abcmy_value!', $param->filter('my_value!'));
    }

    public function testCanChangeParentOfNestedParameter()
    {
        $param1 = new ApiParam(array('name' => 'parent'));
        $param2 = new ApiParam(array('name' => 'child'));
        $param2->setParent($param1);
        $this->assertSame($param1, $param2->getParent());
    }

    public function testCanRemoveFromNestedStructure()
    {
        $param1 = new ApiParam(array('name' => 'parent'));
        $param2 = new ApiParam(array('name' => 'child'));
        $param1->addProperty($param2);
        $this->assertSame($param1, $param2->getParent());
        $this->assertSame($param2, $param1->getProperty('child'));

        // Remove a single child from the structure
        $param1->removeProperty('child');
        $this->assertNull($param1->getProperty('child'));
        // Remove the entire structure
        $param1->addProperty($param2);
        $param1->removeProperty('child');
        $this->assertNull($param1->getProperty('child'));
    }

    public function testProcessesValue()
    {
        $p = new ApiParam(array(
            'name'     => 'test',
            'type'     => 'string',
            'required' => true
        ));
        $v = null;
        $this->assertEquals(array('[test] is required'), $p->process($v));
    }

    public function testAddsAdditionalProperties()
    {
        $p = new ApiParam(array(
            'type' => 'object',
            'additional_properties' => array('type' => 'string')
        ));
        $this->assertInstanceOf('Guzzle\Service\Description\ApiParam', $p->getAdditionalProperties());
        $this->assertNull($p->getAdditionalProperties()->getAdditionalProperties());
        $p = new ApiParam(array('type' => 'object'));
        $this->assertTrue($p->getAdditionalProperties());
    }

    public function testAddsItems()
    {
        $p = new ApiParam(array(
            'type'  => 'array',
            'items' => array('type' => 'string')
        ));
        $this->assertInstanceOf('Guzzle\Service\Description\ApiParam', $p->getItems());
        $out = $p->toArray();
        $this->assertEquals('array', $out['type']);
        $this->assertInternalType('array', $out['items']);
    }

    public function testHasExtraProperties()
    {
        $p = new ApiParam();
        $this->assertEquals(array(), $p->getExtra());
        $p->setExtra(array('foo' => 'bar'));
        $this->assertEquals('bar', $p->getExtra('foo'));
        $p->setExtra('baz', 'boo');
        $this->assertEquals(array('foo' => 'bar', 'baz' => 'boo'), $p->getExtra());
    }

    public function testHasInstanceOf()
    {
        $p = new ApiParam();
        $this->assertNull($p->getInstanceOf());
        $p->setInstanceOf('Foo');
        $this->assertEquals('Foo', $p->getInstanceOf());
    }

    public function testHasPattern()
    {
        $p = new ApiParam();
        $this->assertNull($p->getPattern());
        $p->setPattern('/[0-9]+/');
        $this->assertEquals('/[0-9]+/', $p->getPattern());
    }

    public function testHasEnum()
    {
        $p = new ApiParam();
        $this->assertNull($p->getEnum());
        $p->setEnum(array('foo', 'bar'));
        $this->assertEquals(array('foo', 'bar'), $p->getEnum());
    }
}
