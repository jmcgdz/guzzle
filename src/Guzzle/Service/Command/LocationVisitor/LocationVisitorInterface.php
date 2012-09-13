<?php

namespace Guzzle\Service\Command\LocationVisitor;

use Guzzle\Http\Message\RequestInterface;
use Guzzle\Service\Description\ApiParam;
use Guzzle\Service\Command\CommandInterface;

/**
 * Location visitor used to add values to different locations in a request with different behaviors as needed
 */
interface LocationVisitorInterface
{
    /**
     * Called after visiting all parameters
     *
     * @param CommandInterface $command Command being prepared
     * @param RequestInterface $request Request being prepared
     */
    public function after(CommandInterface $command, RequestInterface $request);

    /**
     * Called once for each parameter being visited that matches the location type
     *
     * @param ApiParam         $param   Parameter being visited
     * @param RequestInterface $request Request being prepared
     * @param string           $value   Value to set
     */
    public function visit(ApiParam $param, RequestInterface $request, $value);
}
