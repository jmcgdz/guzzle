<?php

namespace Guzzle\Tests\Service\Command;

use Guzzle\Http\Utils;
use Guzzle\Http\Message\PostFile;
use Guzzle\Service\Client;
use Guzzle\Service\Command\DynamicCommand;
use Guzzle\Service\Command\Factory\ServiceDescriptionFactory;
use Guzzle\Service\Description\ApiCommand;
use Guzzle\Service\Description\ServiceDescription;
use Guzzle\Service\Command\LocationVisitor\HeaderVisitor;

/**
 * @covers Guzzle\Service\Command\DynamicCommand
 */
class DynamicCommandTest extends \Guzzle\Tests\GuzzleTestCase
{
    /**
     * @var ServiceDescription
     */
    protected $service;

    /**
     * @var ServiceDescriptionFactory
     */
    protected $factory;

    /**
     * Setup the service description
     */
    public function setUp()
    {
        $this->service = new ServiceDescription(array(
            'test_command' => new ApiCommand(array(
                'description' => 'documentationForCommand',
                'method'      => 'HEAD',
                'uri'         => '{/key}',
                'params'      => array(
                    'bucket' => array(
                        'required' => true
                    ),
                    'key' => array(
                        'location' => 'uri'
                    ),
                    'acl' => array(
                        'location' => 'query'
                    ),
                    'meta' => array(
                        'location' => array('name' => 'header', 'key' => 'X-Amz-Meta')
                    )
                )
            )),
            'body' => new ApiCommand(array(
                'description' => 'doc',
                'method'      => 'PUT',
                'params'      => array(
                    'b' => array(
                        'required' => true,
                        'location' => 'body'
                    ),
                    'q' => array(
                        'location'     => 'query',
                        'location_key' => 'test'
                    ),
                    'h' => array(
                        'location'     => 'header',
                        'location_key' => 'X-Custom'
                    ),
                    'i' => array(
                        'static'   => 'test',
                        'location' => 'query'
                    ),
                    'data' => array()
                )
            )),
            'concrete' => new ApiCommand(array(
                'class' => 'Guzzle\\Tests\\Service\\Mock\\Command\\MockCommand',
                'params' => array()
            ))
        ));
        $this->factory = new ServiceDescriptionFactory($this->service);
    }

    /**
     * @expectedException Guzzle\Service\Exception\ValidationException
     */
    public function testValidatesArgs()
    {
        $client = new Client('http://www.fragilerock.com/');
        $client->setDescription($this->service);
        $command = $this->factory->factory('test_command', array());
        $client->execute($command);
    }

    public function testUsesDifferentLocations()
    {
        $client = new Client('http://www.tazmania.com/');
        $command = $this->factory->factory('body', array(
            'b' => 'my-data',
            'q' => 'abc',
            'h' => 'haha'
        ));

        $request = $command->setClient($client)->prepare();

        $this->assertEquals(
            "PUT /?test=abc&i=test HTTP/1.1\r\n" .
            "Host: www.tazmania.com\r\n" .
            "User-Agent: " . Utils::getDefaultUserAgent() . "\r\n" .
            "Expect: 100-Continue\r\n" .
            "Content-Length: 7\r\n" .
            "X-Custom: haha\r\n" .
            "\r\n" .
            "my-data", (string) $request);

        unset($command);
        unset($request);

        $command = $this->factory->factory('body', array(
            'b' => 'my-data',
            'q' => 'abc',
            'h' => 'haha',
            'i' => 'does not change the value because it\'s static'
        ));

        $request = $command->setClient($client)->prepare();

        $this->assertEquals(
            "PUT /?test=abc&i=test HTTP/1.1\r\n" .
            "Host: www.tazmania.com\r\n" .
            "User-Agent: " . Utils::getDefaultUserAgent() . "\r\n" .
            "Expect: 100-Continue\r\n" .
            "Content-Length: 7\r\n" .
            "X-Custom: haha\r\n" .
            "\r\n" .
            "my-data", (string) $request);
    }

    public function testBuildsConcreteCommands()
    {
        $c = $this->factory->factory('concrete');
        $this->assertEquals('Guzzle\\Tests\\Service\\Mock\\Command\\MockCommand', get_class($c));
    }

    public function testUsesAbsolutePaths()
    {
        $service = new ServiceDescription(array(
            'test_path' => new ApiCommand(array(
                'method' => 'GET',
                'uri'    => '/test',
            ))
        ));

        $client = new Client('http://www.test.com/');
        $client->setDescription($service);
        $command = $client->getCommand('test_path');
        $request = $command->prepare();
        $this->assertEquals('/test', $request->getPath());
    }

    public function testUsesRelativePaths()
    {
        $service = new ServiceDescription(array(
            'test_path' => new ApiCommand(array(
                'method' => 'GET',
                'uri'    => 'test/abc',
            ))
        ));

        $client = new Client('http://www.test.com/api/v2');
        $client->setDescription($service);
        $command = $client->getCommand('test_path');
        $request = $command->prepare();
        $this->assertEquals('/api/v2/test/abc', $request->getPath());
    }

    public function testAddsToUriTemplate()
    {
        $service = new ServiceDescription(array(
            'test' => new ApiCommand(array(
                'method' => 'GET',
                'uri'    => '/test/abc{/foo}',
                'params' => array(
                    'foo' => array(
                        'location' => 'uri'
                    )
                )
            ))
        ));

        $client = new Client('http://foo.com');
        $client->setDescription($service);
        $command = $client->getCommand('test');
        $command->set('foo', 'Baz');
        $request = $command->prepare();
        $this->assertEquals('/test/abc/Baz', $request->getPath());
    }

    public function testAllowsPostFieldsAndFiles()
    {
        $service = new ServiceDescription(array(
            'post_command' => new ApiCommand(array(
                'method' => 'POST',
                'uri'    => '/key',
                'params' => array(
                    'test' => array(
                        'location' => 'post_field'
                    ),
                    'test_2' => array(
                        'location' => 'post_field',
                        'location_key' => 'foo'
                    ),
                    'test_3' => array(
                        'location' => 'post_file'
                    )
                )
            ))
        ));

        $client = new Client('http://www.test.com/api/v2');
        $client->setDescription($service);

        $command = $client->getCommand('post_command', array(
            'test'   => 'Hi!',
            'test_2' => 'There',
            'test_3' => __FILE__
        ));
        $request = $command->prepare();
        $this->assertEquals('Hi!', $request->getPostField('test'));
        $this->assertEquals('There', $request->getPostField('foo'));
        $this->assertInternalType('array', $request->getPostFile('test_3'));

        $command = $client->getCommand('post_command', array(
            'test_3' => new PostFile('baz', __FILE__)
        ));
        $request = $command->prepare();
        $this->assertInternalType('array', $request->getPostFile('baz'));
    }

    public function testAllowsCustomVisitor()
    {
        $service = new ServiceDescription(array(
            'foo' => new ApiCommand(array(
                'params' => array(
                    'test' => array(
                        'location' => 'query'
                    )
                )
            ))
        ));
        $client = new Client();
        $client->setDescription($service);

        $command = $client->getCommand('foo', array('test' => 'hi'));
        // Flip query and header
        $command->addVisitor('query', new HeaderVisitor());
        $request = $command->prepare();
        $this->assertEquals('hi', (string) $request->getHeader('test'));
    }

    public function testUsesFiltersCorrectly()
    {
        $this->service = new ServiceDescription(array(
            'test_command' => new ApiCommand(array(
                'method' => 'HEAD',
                'params' => array(
                    'X-Bucket' => array(
                        'filters'  => 'strtoupper',
                        'location' => 'header'
                    )
                )
            ))
        ));

        $client = new Client();
        $client->setDescription($this->service);
        $command = $client->getCommand('test_command');
        $request = $command->prepare();
        $this->assertFalse($request->hasHeader('X-Bucket'));

        $command = $client->getCommand('test_command');
        $command['X-Bucket'] = 'abc';
        $request = $command->prepare();
        $this->assertEquals('ABC', (string) $request->getHeader('X-Bucket'));
    }
}
