<?php

namespace Guzzle\Service\Command\LocationVisitor;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Description\ApiParam;
use Guzzle\Service\Command\CommandInterface;

/**
 * Visitor used to apply a parameter to a POST field
 */
class PostFieldVisitor extends AbstractVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(ApiParam $param, RequestInterface $request, $value)
    {
        $request->setPostField(
            $param->getLocationKey() ?: $param->getName(),
            $param && is_array($value) ? $this->resolveRecursively($value, $param) : $value
        );
    }
}
