<?php

namespace Guzzle\Service\Description;

/**
 * A ServiceDescription stores service information based on a service document
 */
class ServiceDescription implements ServiceDescriptionInterface
{
    /**
     * @var array Array of {@see ApiCommandInterface} objects
     */
    protected $commands = array();

    /**
     * @var ServiceDescriptionFactoryInterface Factory used in factory method
     */
    protected static $descriptionFactory;

    /**
     * {@inheritdoc}
     * @param string|array $config  File to build or array of command information
     * @param array        $options Service description factory options
     */
    public static function factory($config, array $options = null)
    {
        // @codeCoverageIgnoreStart
        if (!self::$descriptionFactory) {
            self::$descriptionFactory = new ServiceDescriptionAbstractFactory();
        }
        // @codeCoverageIgnoreEnd

        return self::$descriptionFactory->build($config, $options);
    }

    /**
     * Create a new ServiceDescription
     *
     * @param array $commands Array of {@see ApiCommandInterface} objects
     */
    public function __construct(array $commands = array())
    {
        foreach ($commands as $name => $command) {
            if (!$command->getName()) {
                $command->setName($name);
            }
            $this->addCommand($command);
        }
    }

    /**
     * Serialize the service description
     *
     * @return string
     */
    public function serialize()
    {
        $commands = array();
        foreach ($this->commands as $name => $command) {
            $commands[$name] = $command->toArray();
        }

        return json_encode($commands);
    }

    /**
     * Unserialize the service description
     *
     * @param string|array $json JSON data
     */
    public function unserialize($json)
    {
        foreach (json_decode($json, true) as $name => $command) {
            $temp = isset($command['params']) ? $command['params'] : array();
            unset($command['params']);
            $command['name'] = $name;
            $this->commands[$name] = new ApiCommand($command);
            foreach ($temp as $paramName => $param) {
                $param['name'] = $paramName;
                $this->commands[$name]->addParam(new ApiParam($param));
            }
        }
    }

    /**
     * Get the API commands of the service
     *
     * @return array Returns an array of {@see ApiCommandInterface} objects
     */
    public function getCommands()
    {
        return $this->commands;
    }

    /**
     * Check if the service has a command by name
     *
     * @param string $name Name of the command to check
     *
     * @return bool
     */
    public function hasCommand($name)
    {
        return array_key_exists($name, $this->commands);
    }

    /**
     * Get an API command by name
     *
     * @param string $name Name of the command
     *
     * @return ApiCommandInterface|null
     */
    public function getCommand($name)
    {
        return $this->hasCommand($name) ? $this->commands[$name] : null;
    }

    /**
     * Add a command to the service description
     *
     * @param ApiCommandInterface $command Command to add
     *
     * @return self
     */
    public function addCommand(ApiCommandInterface $command)
    {
        $this->commands[$command->getName()] = $command;

        return $this;
    }
}
