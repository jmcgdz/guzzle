<?php

namespace Guzzle\Service\Command\LocationVisitor;

use Guzzle\Http\EntityBody;
use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Description\ApiParam;

/**
 * Visitor used to apply a body to a request
 */
class BodyVisitor extends AbstractVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(ApiParam $param, RequestInterface $request, $value)
    {
        $request->setBody(EntityBody::factory($value));
    }
}
