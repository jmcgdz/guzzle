<?php

namespace Guzzle\Service\Description;

use Guzzle\Service\Inspector;
use Guzzle\Service\Exception\DescriptionBuilderException;

/**
 * Build service descriptions using an array of configuration data
 */
class ArrayDescriptionBuilder implements DescriptionBuilderInterface
{
    /**
     * {@inheritdoc}
     */
    public function build($config, array $options = null)
    {
        $commands = array();
        if (!empty($config['commands'])) {
            foreach ($config['commands'] as $name => $command) {
                $name = $command['name'] = isset($command['name']) ? $command['name'] : $name;
                // Extend other commands
                if (!empty($command['extends'])) {

                    $originalParams = empty($command['params']) ? false: $command['params'];
                    $resolvedParams = array();

                    foreach ((array) $command['extends'] as $extendedCommand) {
                        if (empty($commands[$extendedCommand])) {
                            throw new DescriptionBuilderException("{$name} extends missing command {$extendedCommand}");
                        }
                        $toArray = $commands[$extendedCommand]->toArray();
                        $resolvedParams = empty($resolvedParams) ? $toArray['params'] : array_merge($resolvedParams, $toArray['params']);
                        $command = array_merge($toArray, $command);
                    }

                    $command['params'] = $originalParams ? array_merge($resolvedParams, $originalParams) : $resolvedParams;
                }
                // Use the default class
                $command['class'] = isset($command['class']) ? $command['class'] : ServiceDescription::DEFAULT_COMMAND_CLASS;
                $commands[$name] = new ApiCommand($command);
            }
        }

        return new ServiceDescription($commands);
    }
}
