<?php

namespace Guzzle\Service\Description;

use Guzzle\Common\Collection;
use Guzzle\Service\Exception\ValidationException;

/**
 * Interface defining data objects that hold the information of an API command
 */
interface ApiCommandInterface
{
    /**
     * Get as an array
     *
     * @return array
     */
    public function toArray();

    /**
     * Get the params of the command
     *
     * @return array
     */
    public function getParams();

    /**
     * Returns an array of parameter names
     *
     * @return array
     */
    public function getParamNames();

    /**
     * Check if the command has a specific parameter by name
     *
     * @param string $name Name of the param
     *
     * @return bool
     */
    public function hasParam($name);

    /**
     * Get a single parameter of the command
     *
     * @param string $param Parameter to retrieve by name
     *
     * @return ApiParam|null
     */
    public function getParam($param);

    /**
     * Get the HTTP method of the command
     *
     * @return string|null
     */
    public function getMethod();

    /**
     * Get the concrete command class that implements this command
     *
     * @return string
     */
    public function getConcreteClass();

    /**
     * Get the name of the command
     *
     * @return string|null
     */
    public function getName();

    /**
     * Get the documentation for the command
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Get the documentation URL of the command
     *
     * @return string|null
     */
    public function getDescriptionUrl();

    /**
     * Get the type of data stored in the result of the command
     *
     * @return string|null
     */
    public function getResultType();

    /**
     * Get the documentation specific to the result of the command
     *
     * @return string|null
     */
    public function getResultDescription();

    /**
     * Get whether or not the command is deprecated
     *
     * @return bool
     */
    public function isDeprecated();

    /**
     * Get the URI that will be merged into the generated request
     *
     * @return string
     */
    public function getUri();

    /**
     * Validates that all required args are included in a config object, and if not, throws an
     * InvalidArgumentException with a helpful error message. Adds default args to the passed config object if the
     * parameter was not set in the config object.
     *
     * @param Collection $config Configuration settings
     *
     * @throws ValidationException when validation errors occur
     */
    public function validate(Collection $config);
}
