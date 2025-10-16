<?php

namespace Worldfone\BeanstalkMq;

use Serializable;

interface Event extends Serializable
{
    /**
     * @return string
     */
    public function getName();

    /**
     * @return array
     */
    public function getParameters();
    
    public function json_encode();
    
    public static function json_decode($json);
}
