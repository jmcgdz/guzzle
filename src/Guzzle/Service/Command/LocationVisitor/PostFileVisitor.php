<?php

namespace Guzzle\Service\Command\LocationVisitor;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Http\Message\PostFileInterface;
use Guzzle\Service\Description\ApiParam;
use Guzzle\Service\Command\CommandInterface;

/**
 * Visitor used to apply a parameter to a POST file
 */
class PostFileVisitor extends AbstractVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(ApiParam $param, RequestInterface $request, $value)
    {
        if ($value instanceof PostFileInterface) {
            $request->addPostFile($value);
        } else {
            $request->addPostFile($param->getLocationKey() ?: $param->getName(), $value);
        }
    }
}
