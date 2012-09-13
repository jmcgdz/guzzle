<?php

namespace Guzzle\Tests\Service\Description;

use Guzzle\Common\Collection;
use Guzzle\Service\Description\ApiCommand;
use Guzzle\Service\Description\ApiParam;
use Guzzle\Service\Exception\ValidationException;

/**
 * @covers Guzzle\Service\Description\ApiCommand
 */
class ApiCommandTest extends \Guzzle\Tests\GuzzleTestCase
{
    public static function strtoupper($string)
    {
        return strtoupper($string);
    }

    public function testApiCommandIsDataObject()
    {
        $c = new ApiCommand(array(
            'name'               => 'test',
            'description'        => 'doc',
            'description_url'    => 'http://www.example.com',
            'method'             => 'POST',
            'uri'                => '/api/v1',
            'result_type'        => 'array',
            'result_description' => 'returns the json_decoded response',
            'deprecated'         => true,
            'params'             => array(
                'key' => array(
                    'required' => true,
                    'type'     => 'string',
                    'max'      => 10
                ),
                'key_2' => array(
                    'required' => true,
                    'type'     => 'integer',
                    'default'  => 10
                )
           )
        ));

        $this->assertEquals('test', $c->getName());
        $this->assertEquals('doc', $c->getDescription());
        $this->assertEquals('http://www.example.com', $c->getDescriptionUrl());
        $this->assertEquals('POST', $c->getMethod());
        $this->assertEquals('/api/v1', $c->getUri());
        $this->assertEquals('array', $c->getResultType());
        $this->assertEquals('returns the json_decoded response', $c->getResultDescription());
        $this->assertTrue($c->isDeprecated());
        $this->assertEquals('Guzzle\\Service\\Command\\DynamicCommand', $c->getConcreteClass());
        $this->assertEquals(array(
            'key' => new ApiParam(array(
                'name'     => 'key',
                'required' => true,
                'type'     => 'string',
                'max'      => 10
            )),
            'key_2' => new ApiParam(array(
                'name'     => 'key_2',
                'required' => true,
                'type'     => 'integer',
                'default'  => 10
            ))
        ), $c->getParams());

        $this->assertEquals(new ApiParam(array(
            'name' => 'key_2',
            'required' => true,
            'type' => 'integer',
            'default' => 10
        )), $c->getParam('key_2'));

        $this->assertNull($c->getParam('afefwef'));
    }

    public function testAllowsConcreteCommands()
    {
        $c = new ApiCommand(array(
            'name' => 'test',
            'class' => 'Guzzle\\Service\\Command\ClosureCommand',
            'params' => array(
                'p' => new ApiParam(array(
                    'name' => 'foo'
                ))
            )
        ));
        $this->assertEquals('Guzzle\\Service\\Command\ClosureCommand', $c->getConcreteClass());
    }

    public function testConvertsToArray()
    {
        $data = array(
            'name'            => 'test',
            'class'           => 'Guzzle\\Service\\Command\ClosureCommand',
            'description'     => 'test',
            'description_url' => 'http://www.example.com',
            'method'          => 'PUT',
            'uri'             => '/',
            'params'          => array(
                'p' => array('name' => 'foo')
            )
        );
        $c = new ApiCommand($data);
        $toArray = $c->toArray();
        $this->assertArrayHasKey('params', $toArray);
        $this->assertInternalType('array', $toArray['params']);

        // Normalize the array
        unset($data['params']);
        unset($toArray['params']);
        $this->assertEquals($data, $toArray);
    }

    public function testAddsDefaultAndInjectsConfigs()
    {
        $col = new Collection(array(
            'username' => 'user',
            'string'   => 'test',
            'float'    => 1.23
        ));

        $this->getApiCommand()->validate($col);
        $this->assertEquals(false, $col->get('bool_2'));
        $this->assertEquals(1.23, $col->get('float'));
    }

    /**
     * @expectedException Guzzle\Service\Exception\ValidationException
     */
    public function testValidatesTypeHints()
    {
        $this->getApiCommand()->validate(new Collection(array(
            'test' => 'uh oh',
            'username' => 'test'
        )));
    }

    public function testConvertsBooleanDefaults()
    {
        $c = new Collection(array(
            'test' => $this,
            'username' => 'test'
        ));

        $this->getApiCommand()->validate($c);
        $this->assertTrue($c->get('bool_1'));
        $this->assertFalse($c->get('bool_2'));
    }

    public function testValidatesArgs()
    {
        $config = new Collection(array(
            'data' => 123,
            'min'  => 'a',
            'max'  => 'aaa'
        ));

        $command = new ApiCommand(array(
            'params' => array(
                'data' => new ApiParam(array(
                    'name' => 'data',
                    'type' => 'string'
                )),
                'min' => new ApiParam(array(
                    'name' => 'min',
                    'type' => 'string',
                    'min'  => 2
                )),
                'max' => new ApiParam(array(
                    'name' => 'max',
                    'type' => 'string',
                    'max'  => 2
                ))
            )
        ));

        try {
            $command->validate($config);
            $this->fail('Did not throw expected exception');
        } catch (ValidationException $e) {
            $concat = implode("\n", $e->getErrors());
            $this->assertContains("[data] must be of type string", $concat);
            $this->assertContains("[min] length must be greater than or equal to 2", $concat);
            $this->assertContains("[max] length must be greater than or equal to 2", $concat);
        }
    }

    public function testRunsValuesThroughFilters()
    {
        $data = new Collection(array(
            'username'      => 'TEST',
            'test_function' => 'foo'
        ));

        $this->getApiCommand()->validate($data);
        $this->assertEquals('test', $data->get('username'));
        $this->assertEquals('FOO', $data->get('test_function'));
    }

    public function testSkipsFurtherValidationIfNotSet()
    {
        $command = $this->getTestCommand();
        $command->validate(new Collection());
    }

    public function testDeterminesIfHasParam()
    {
        $command = $this->getTestCommand();
        $this->assertTrue($command->hasParam('data'));
        $this->assertFalse($command->hasParam('baz'));
    }

    public function testReturnsParamNames()
    {
        $command = $this->getTestCommand();
        $this->assertEquals(array('data'), $command->getParamNames());
    }

    protected function getTestCommand()
    {
        return new ApiCommand(array(
            'params' => array(
                'data' => new ApiParam(array(
                    'type' => 'string'
                ))
            )
        ));
    }

    public function testCanBuildUpCommands()
    {
        $c = new ApiCommand(array());
        $c->setName('foo')
            ->setConcreteClass('Baz')
            ->setDeprecated(false)
            ->setDescription('doc')
            ->setDescriptionUrl('http://www.foo.com')
            ->setMethod('PUT')
            ->setResultDescription('oh')
            ->setResultType('string')
            ->setUri('/foo/bar')
            ->addParam(new ApiParam(array(
                'name' => 'test'
            )));

        $this->assertEquals('foo', $c->getName());
        $this->assertEquals('Baz', $c->getConcreteClass());
        $this->assertEquals(false, $c->isDeprecated());
        $this->assertEquals('doc', $c->getDescription());
        $this->assertEquals('http://www.foo.com', $c->getDescriptionUrl());
        $this->assertEquals('PUT', $c->getMethod());
        $this->assertEquals('oh', $c->getResultDescription());
        $this->assertEquals('string', $c->getResultType());
        $this->assertEquals('/foo/bar', $c->getUri());
        $this->assertEquals(array('test'), $c->getParamNames());
    }

    public function testCanRemoveParams()
    {
        $c = new ApiCommand(array());
        $c->addParam(new ApiParam(array('name' => 'foo')));
        $this->assertTrue($c->hasParam('foo'));
        $c->removeParam('foo');
        $this->assertFalse($c->hasParam('foo'));
    }

    public function testRecursivelyValidatesAndFormatsInput()
    {
        $command = new ApiCommand(array(
            'params' => array(
                'foo' => new ApiParam(array(
                    'name'      => 'foo',
                    'type'      => 'object',
                    'location'  => 'query',
                    'required'  => true,
                    'properties' => array(
                        'baz' => array(
                            'type'       => 'object',
                            'required'   => true,
                            'properties' => array(
                                'bam' => array(
                                    'type'    => 'boolean',
                                    'default' => true
                                ),
                                'boo' => array(
                                    'type'    => 'string',
                                    'filters' => 'strtoupper',
                                    'default' => 'mesa'
                                )
                            )
                        ),
                        'bar' => array(
                            'default' => '123'
                        )
                    )
                ))
            )
        ));

        $input = new Collection();
        $command->validate($input);
        $this->assertEquals(array(
            'foo' => array(
                'baz' => array(
                    'bam' => true,
                    'boo' => 'MESA'
                ),
                'bar' => '123'
            )
        ), $input->getAll());
    }

    public function testAddsNameToApiParamsIfNeeded()
    {
        $command = new ApiCommand(array('params' => array('foo' => new ApiParam(array()))));
        $this->assertEquals('foo', $command->getParam('foo')->getName());
    }

    public function testContainsApiErrorInformation()
    {
        $command = $this->getApiCommand();
        $this->assertEquals(1, count($command->getErrors()));
        $arr = $command->toArray();
        $this->assertEquals(1, count($arr['errors']));
        $command->addError(400, 'Foo', 'Baz\\Bar');
        $this->assertEquals(2, count($command->getErrors()));
    }

    public function testBuildsApiParamsLazily()
    {
        $command = $this->getApiCommand();
        $this->assertTrue($command->hasParam('test'));
        $params = $this->readAttribute($command, 'params');
        $this->assertInternalType('array', $params['test']);
        $this->assertInstanceOf('Guzzle\Service\Description\ApiParam', $command->getParam('test'));
        $params = $this->readAttribute($command, 'params');
        $this->assertInstanceOf('Guzzle\Service\Description\ApiParam', $params['test']);
    }

    /**
     * @return ApiCommand
     */
    protected function getApiCommand()
    {
        return new ApiCommand(array(
            'name' => 'ApiCommandTest',
            'class' => get_class($this),
            'params' => array(
                'test' => array(
                    'type' => 'object'
                ),
                'bool_1' => array(
                    'default' => true,
                    'type'    => 'boolean'
                ),
                'bool_2' => array('default' => false),
                'float' => array('type' => 'numeric'),
                'int' => array('type' => 'integer'),
                'date' => array('type' => 'string'),
                'timestamp' => array('type' => 'string'),
                'string' => array('type' => 'string'),
                'username' => array(
                    'type'     => 'string',
                    'required' => true,
                    'filters'  => 'strtolower'
                ),
                'test_function' => array(
                    'type'    => 'string',
                    'filters' => __CLASS__ . '::strtoupper'
                )
            ),
            'errors' => array(
                array(
                    'code'   => 503,
                    'reason' => 'InsufficientCapacity',
                    'class'  => 'Guzzle\\Exception\\RuntimeException'
                )
            )
        ));
    }
}
