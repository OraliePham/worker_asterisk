<?php

/*
 * Copyright © 2014 South Telecom
 */

namespace Worldfone\BeanstalkMq;

/**
 * Description of BeanstalkMqLocation
 *
 * @author nguyenngocbinh
 */
final class BeanstalkMqLocation implements Location {
    
    /**
     * @var string
     */
    private $tube;
    
    /**
     * @var string
     */
    private $host;

    /**
     * @var int
     */
    private $port;

    /**
     * @param string $host
     * @param int $port
     */
    public function __construct($tube,$host, $port)
    {
        $this->tube = $tube;
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @inheritdoc
     *
     * @return int
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function serialize()
    {
        return serialize([
            "tube" => $this->tube,
            "host" => $this->host,
            "port" => $this->port,
        ]);
    }

    /**
     * @inheritdoc
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);
        $this->tube = $unserialized["tube"];
        $this->host = $unserialized["host"];
        $this->port = $unserialized["port"];
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function getTube() {
        return $this->tube;
    }

}
