<?php

namespace Guzzle\Service\Command\LocationVisitor;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Description\ApiParam;

/**
 * Visitor used to apply a parameter to a header value
 */
class HeaderVisitor extends AbstractVisitor
{
    /**
     * {@inheritdoc}
     */
    public function visit(ApiParam $param, RequestInterface $request, $value)
    {
        $request->setHeader($param->getLocationKey() ?: $param->getName(), $value);
    }
}
