<?php

namespace Worldfone\BeanstalkMq;

use Serializable;

interface Location extends Serializable
{
    /**
     * @return string
     */
    public function getTube();
    
    /**
     * @return string
     */
    public function getHost();

    /**
     * @return int
     */
    public function getPort();
    
    
}
