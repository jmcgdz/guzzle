<?php

namespace Guzzle\Service\Description;

use Guzzle\Common\Exception\InvalidArgumentException;

/**
 * API parameter object used with service descriptions
 */
class ApiParam
{
    protected $name;
    protected $description;
    protected $type;
    protected $required;
    protected $enum;
    protected $pattern;
    protected $min;
    protected $max;
    protected $default;
    protected $static;
    protected $instance_of;
    protected $filters;
    protected $location;
    protected $location_key;
    protected $location_args;
    protected $extra;
    protected $properties = array();
    protected $additional_properties;
    protected $items;
    protected $parent;
    protected $processor;

    /**
     * Create a new ApiParam using an associative array of data. The array can contain the following information:
     * - name:          (string) Parameter name
     * - type:          (string|array) Type of variable (string, number, integer, boolean, object, array, numeric,
     *                  null, any). Types are using for validation and determining the structure of a parameter. You
     *                  can use a union type by providing an array of simple types. If one of the union types matches
     *                  the provided value, then the value is valid.
     * - instance_of:   (string) When the type is an object, you can specify the class that the object must implement
     * - required:      (bool) Whether or not the parameter is required
     * - default:       (mixed) Default value to use if no value is supplied
     * - static:        (mixed) Set a static value when the parameter must have a default value that cannot be changed
     * - description:   (string) Documentation of the parameter
     * - location:      (string) The location of a request used to apply a parameter. Custom locations can be registered
     *                  with a command, but the defaults are uri, query, header, body, json, post_field, post_file.
     * - location_key:  (string) Allows the customization of where in a location a parameter is applied (e.g. renaming)
     * - location_args: (array) Additional location specific arguments when serializing on a message.
     * - filters:       (array) Array of static method names to to run a parameter value through. Each value in the
     *                  array must be a string containing the full class path to a static method or an array of complex
     *                  filter information. You can specify static methods of classes using the full namespace class
     *                  name followed by '::' (e.g. Foo\Bar::baz()). Some filters require arguments in order to properly
     *                  filter a value. For complex filters, use a hash containing a 'method' key pointing to a static
     *                  method, and an 'args' key containing an array of positional arguments to pass to the method.
     *                  Arguments can contain keywords that are replaced when filtering a value: '@value' is replaced
     *                  with the value being validated, '@api' is replaced with the ApiParam object.
     * - properties:    When the type is an object, you can specify nested parameters
     * - additional_properties: (array) This attribute defines a schema for all properties that are not explicitly
     *                  defined in an object type definition. If specified, the value MUST be a schema or a boolean. If
     *                  false is provided, no additional properties are allowed beyond the properties defined in the
     *                  schema. The default value is an empty schema which allows any value for additional properties.
     * - items:         This attribute defines the allowed items in an instance array, and MUST be a schema or an array
     *                  of schemas. The default value is an empty schema which allows any value for items in the
     *                  instance array.
     *                  When this attribute value is a schema and the instance value is an array, then all the items
     *                  in the array MUST be valid according to the schema.
     * - pattern:       When the type is a string, you can specify the regex pattern that a value must match
     * - enum:          When the type is a string, you can specify a list of acceptable values
     * - min:           (int) Minimum length when dealing with a string, minimum elements in an array, or minimum number
     * - max:           (int) Maximum length when dealing with a string, maximum elements in an array, or maximum number
     * - extra:         (array) Any additional custom data to use when serializing, validating, etc
     * - processor:     (ProcessorInterface) Custom parameter schema validator and processor
     *
     * @param array $data Array of data as seen in service descriptions
     *
     * @throws InvalidArgumentException
     */
    public function __construct(array $data = array())
    {
        foreach ($data as $key => $value) {
            $this->{$key} = $value;
        }

        $this->required = $this->required ? true : false;
        $this->extra = (array) $this->extra;

        if ($this->filters) {
            $this->setFilters((array) $this->filters);
        }

        if ($this->type == 'object') {
            if ($this->properties) {
                $this->properties = array();
                foreach ($data['properties'] as $name => $property) {
                    $property['name'] = $name;
                    $this->addProperty(new static($property));
                }
            }
            if ($this->additional_properties && is_array($this->additional_properties)) {
                $this->setAdditionalProperties(new static($this->additional_properties));
            } elseif ($this->additional_properties === null) {
                $this->additional_properties = true;
            }
        } elseif ($this->type == 'array' && $this->items) {
            $this->setItems(new static($this->items));
        }

        if (!$this->processor) {
            $this->processor = DefaultProcessor::getInstance();
        }
    }

    /**
     * Convert the object to an array
     *
     * @return array
     */
    public function toArray()
    {
        $result = array();
        foreach (array(
            'name', 'required', 'description', 'type', 'location', 'location_key', 'location_args',
             'filters', 'enum', 'pattern', 'min', 'max', 'instance_of', 'extra'
        ) as $c) {
            if ($value = $this->{$c}) {
                $result[$c] = $value;
            }
        }

        foreach (array('default', 'static', 'additional_properties', 'min', 'max') as $notNull) {
            $value = $this->{$notNull};
            if ($value !== null) {
                $result[$notNull] = $value;
            }
        }

        if ($this->type) {
            $result['type'] = $this->type;
            if ($this->type == 'array' && $this->items) {
                $result['items'] = $this->items->toArray();
            } elseif ($this->type == 'object' && $this->properties) {
                $result['properties'] = array();
                foreach ($this->properties as $name => $property) {
                    $result['properties'][$name] = $property->toArray();
                }
            }
        }

        return $result;
    }

    /**
     * Get the default or static value of the command based on a value
     *
     * @param string $value Value that is currently set
     *
     * @return mixed Returns the value, a static value if one is present, or a default value
     */
    public function getValue($value)
    {
        return $this->static !== null
            || ($this->default !== null && !$value && ($this->type != 'boolean' || $value !== false))
            ? ($this->static ?: $this->default)
            : $value;
    }

    /**
     * Validate a value against the acceptable types, regular expressions, minimum, maximums, instance_of, enums, etc
     * Add default and static values to the passed in variable.
     * If the validation completes successfully, run the parameter through its filters.
     *
     * @param mixed  $value Value to validate and process. The value may change during this process.
     *
     * @return bool|array Returns true if valid, or an array of error messages if invalid
     */
    public function process(&$value)
    {
        return $this->processor->process($this, $value);
    }

    /**
     * Run a value through the filters associated with the parameter
     *
     * @param mixed $value Value to filter
     *
     * @return mixed Returns the filtered value
     */
    public function filter($value)
    {
        if ($this->filters) {
            foreach ($this->filters as $filter) {
                if (is_array($filter)) {
                    // Convert complex filters that hold value place holders
                    foreach ($filter['args'] as &$data) {
                        if ($data == '@value') {
                            $data = $value;
                        } elseif ($data == '@api') {
                            $data = $this;
                        }
                    }
                    $value = call_user_func_array($filter['method'], $filter['args']);
                } else {
                    $value = call_user_func($filter, $value);
                }
            }
        }

        return $value;
    }

    /**
     * Get the name of the parameter
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set the name of the parameter
     *
     * @param string $name Name to set
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get the type(s) of the parameter
     *
     * @return string|array
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the type(s) of the parameter
     *
     * @param string|array $type Type of parameter or array of simple types used in a union
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get if the parameter is required
     *
     * @return bool
     */
    public function getRequired()
    {
        return $this->required;
    }

    /**
     * Set if the parameter is required
     *
     * @param bool $isRequired Whether or not the parameter is required
     *
     * @return self
     */
    public function setRequired($isRequired)
    {
        $this->required = (bool) $isRequired;

        return $this;
    }

    /**
     * Get the default value of the parameter
     *
     * @return string|null
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * Set the default value of the parameter
     *
     * @param string|null $default Default value to set
     *
     * @return self
     */
    public function setDefault($default)
    {
        $this->default = $default;

        return $this;
    }

    /**
     * Get the description of the parameter
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set the description of the parameter
     *
     * @param string $description Description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get the minimum allowed length/size of the parameter
     *
     * @return int|null
     */
    public function getMin()
    {
        return $this->min;
    }

    /**
     * Set the minimum allowed length/size of the parameter
     *
     * @param int|null $min Minimum length/size of the parameter
     *
     * @return self
     */
    public function setMin($min)
    {
        $this->min = $min;

        return $this;
    }

    /**
     * Get the maximum allowed length/size of the parameter
     *
     * @return int|null
     */
    public function getMax()
    {
        return $this->max;
    }

    /**
     * Set the maximum allowed length/size of the parameter
     *
     * @param int|null $max Maximum allowed length/size
     *
     * @return self
     */
    public function setMax($max)
    {
        $this->max = $max;

        return $this;
    }

    /**
     * Get the location of the parameter
     *
     * @return string|null
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Set the location of the parameter
     *
     * @param string|null $location Location of the parameter
     *
     * @return self
     */
    public function setLocation($location)
    {
        $this->location = $location;

        return $this;
    }

    /**
     * Get the location key mapping of the parameter
     *
     * @return string|null
     */
    public function getLocationKey()
    {
        return $this->location_key;
    }

    /**
     * Set the location key mapping of the parameter
     *
     * @param string|null $key Location key
     *
     * @return self
     */
    public function setLocationKey($key)
    {
        $this->location_key = $key;

        return $this;
    }

    /**
     * Get the location arguments of the parameter
     *
     * @return array|null
     */
    public function getLocationArgs()
    {
        return $this->location_args;
    }

    /**
     * Set the location arguments of the parameter
     *
     * @param array|null $args Location arguments
     *
     * @return self
     */
    public function setLocationArgs($args)
    {
        $this->location_args = $args;

        return $this;
    }

    /**
     * Get all of the extra data properties of the parameter or a specific property by name
     *
     * @param string|null $name Specify a particular property name to retrieve
     *
     * @return array|mixed|null
     */
    public function getExtra($name = null)
    {
        return $name ? (isset($this->extra[$name]) ? $this->extra[$name] : null) : $this->extra;
    }

    /**
     * Set the extra data properties of the parameter or set a specific extra property
     *
     * @param string|array|null $nameOrData The name of a specific extra to set or an array of extras to set
     * @param mixed|null        $data       When setting a specific extra property, specify the data to set for it
     *
     * @return self
     */
    public function setExtra($nameOrData, $data = null)
    {
        if (is_array($nameOrData)) {
            $this->extra = $nameOrData;
        } else {
            $this->extra[$nameOrData] = $data;
        }

        return $this;
    }

    /**
     * Get the static value of the parameter that cannot be changed
     *
     * @return mixed|null
     */
    public function getStatic()
    {
        return $this->static;
    }

    /**
     * Set the static value of the parameter that cannot be changed
     *
     * @param mixed|null $static Static value to set
     *
     * @return self
     */
    public function setStatic($static)
    {
        $this->static = $static;

        return $this;
    }

    /**
     * Get an array of filters used by the parameter
     *
     * @return array
     */
    public function getFilters()
    {
        return $this->filters ?: array();
    }

    /**
     * Set the array of filters used by the parameter
     *
     * @param array $filters Array of functions to use as filters
     *
     * @return self
     */
    public function setFilters(array $filters)
    {
        $this->filters = array();
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }

        return $this;
    }

    /**
     * Add a filter to the parameter
     *
     * @param string|array $filter Method to filter the value through
     *
     * @return self
     * @throws InvalidArgumentException
     */
    public function addFilter($filter)
    {
        if (is_array($filter)) {
            if (!isset($filter['method'])) {
                throw new InvalidArgumentException('A [method] value must be specified for each complex filter');
            }
        }

        if (!$this->filters) {
            $this->filters = array($filter);
        } else {
            $this->filters[] = $filter;
        }

        return $this;
    }

    /**
     * Get the parent object (an {@see ApiCommand} or {@see ApiParam}
     *
     * @return ApiCommandInterface|ApiParam|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * Set the parent object of the parameter
     *
     * @param ApiCommandInterface|ApiParam|null $parent Parent container of the parameter
     *
     * @return self
     */
    public function setParent($parent)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * Get the properties of the parameter
     *
     * @return array
     */
    public function getProperties()
    {
        return $this->properties;
    }

    /**
     * Get a specific property from the parameter
     *
     * @param string $name Name of the property to retrieve
     *
     * @return null|ApiParam
     */
    public function getProperty($name)
    {
        return isset($this->properties[$name]) ? $this->properties[$name] : null;
    }

    /**
     * Remove a property from the parameter
     *
     * @param string $name Name of the property to remove
     *
     * @return self
     */
    public function removeProperty($name)
    {
        unset($this->properties[$name]);

        return $this;
    }

    /**
     * Add a property to the parameter
     *
     * @param ApiParam $property Properties to set
     *
     * @return self
     */
    public function addProperty(ApiParam $property)
    {
        $this->properties[$property->getName()] = $property;
        $property->setParent($this);

        return $this;
    }

    /**
     * Get the additionalProperties value of the parameter
     *
     * @return bool|ApiParam|null
     */
    public function getAdditionalProperties()
    {
        return $this->additional_properties;
    }

    /**
     * Set the additionalProperties value of the parameter
     *
     * @param bool|ApiParam|null $additional Boolean to allow any, an ApiParam to specify a schema, or false to disallow
     *
     * @return self
     */
    public function setAdditionalProperties($additional)
    {
        $this->additional_properties = $additional;
        if ($additional instanceof self) {
            $additional->setParent($this);
        }

        return $this;
    }

    /**
     * Set the items data of the parameter
     *
     * @param ApiParam|null $items Items to set
     */
    public function setItems(ApiParam $items = null)
    {
        if ($this->items = $items) {
            $this->items->setParent($this);
        }

        return $this;
    }

    /**
     * Get the item data of the parameter
     *
     * @return ApiParam|null
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Get the class that the parameter must implement
     *
     * @return null|string
     */
    public function getInstanceOf()
    {
        return $this->instance_of;
    }

    /**
     * Set the class that the parameter must be an instance of
     *
     * @param string|null $instanceOf Class or interface name
     *
     * @return self
     */
    public function setInstanceOf($instanceOf)
    {
        $this->instance_of = $instanceOf;

        return $this;
    }

    /**
     * Get the enum of strings that are valid for the parameter
     *
     * @return array|null
     */
    public function getEnum()
    {
        return $this->enum;
    }

    /**
     * Set the enum of strings that are valid for the parameter
     *
     * @param array|null $enum Array of strings or null
     *
     * @return self
     */
    public function setEnum(array $enum = null)
    {
        $this->enum = $enum;

        return $this;
    }

    /**
     * Get the regex pattern that must match a value when the value is a string
     *
     * @return string
     */
    public function getPattern()
    {
        return $this->pattern;
    }

    /**
     * Set the regex pattern that must match a value when the value is a string
     *
     * @param string $pattern Regex pattern
     *
     * @return self
     */
    public function setPattern($pattern)
    {
        $this->pattern = $pattern;

        return $this;
    }
}
