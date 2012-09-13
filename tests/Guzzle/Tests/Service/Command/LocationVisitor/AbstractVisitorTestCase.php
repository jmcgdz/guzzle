<?php

namespace Guzzle\Tests\Service\Command\LocationVisitor;

use Guzzle\Http\Message\EntityEnclosingRequest;
use Guzzle\Service\Description\ApiCommand;
use Guzzle\Service\Description\ApiParam;
use Guzzle\Tests\Service\Mock\Command\MockCommand;

abstract class AbstractVisitorTestCase extends \Guzzle\Tests\GuzzleTestCase
{
    protected $command;
    protected $request;
    protected $param;

    public function setUp()
    {
        $this->command = new MockCommand();
        $this->request = new EntityEnclosingRequest('POST', 'http://www.test.com');
    }

    protected function getNestedCommand($location)
    {
        return new ApiCommand(array(
            'params' => array(
                'foo' => new ApiParam(array(
                    'type'         => 'object',
                    'location'     => $location,
                    'location_key' => 'Foo',
                    'required'     => true,
                    'properties'   => array(
                        'test' => array(
                            'type'      => 'object',
                            'required'  => true,
                            'properties' => array(
                                'baz' => array(
                                    'type'    => 'boolean',
                                    'default' => true
                                ),
                                // Add a nested parameter that uses a different location_key than the input key
                                'jenga' => array(
                                    'type'         => 'string',
                                    'default'      => 'hello',
                                    'location_key' => 'Jenga_Yall!',
                                    'filters'      => array('strtoupper')
                                )
                            )
                        ),
                        'bar' => array('default' => 123)
                    )
                ))
            )
        ));
    }
}
