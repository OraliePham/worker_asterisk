<?php

namespace Worldfone\BeanstalkMq;

use Serializable;

interface Client extends Serializable
{
    /**
     * @return Location
     */
    public function getLocation();

    /**
     * Emits an event.
     *
     * @param string $name
     * @param array $parameters
     */
    public function emit($name, array $parameters = [],$priority=0, $delay=0);

    /**
     * Closes the connection to a server.
     */
    public function disconnect();
}
