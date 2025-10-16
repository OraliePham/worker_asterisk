<?php

/* 
 * Copyright © 2014 South Telecom
 */
namespace Worldfone\BeanstalkMq;
use Pheanstalk\Pheanstalk;

final class BeanstalkMqClient implements Client
{
    /**
     * @var Location
     */
    private $location;
    
    /**
     * @var \Pheanstalk\Pheanstalk
     */
    private $queue;
    
    /**
     * @param Location $location
     */
    public function __construct(Location $location)
    {
        $this->location = $location;
    }
    
    public function disconnect() {
        if ($this->queue) {
            try {
                $this->queue->getConnection()->disconnect();
            } catch (Exception $exception) {
                // TODO: find an elegant way to deal with this
            }
        }
    }
    
    /**
     * @param string $name
     * @param array $parameters
     *
     * @return Event
     */
    private function newEvent($name, array $parameters = [])
    {
        return new BeanstalkMqEvent($name, $parameters);
    }
    
    /**
     * @param string $name
     * @param array $parameters
     */
    public function emit($name, array $parameters = array(),$priority=0, $delay=0) {
        if($this->getQueue()){
            $event = $this->newEvent($name, $parameters);
            $this->queue->putInTube($this->location->getTube(), $event->json_encode(), $priority, $delay);
        }
    }
    
    /**
     * @return Pheanstalk\Pheanstalk
     */
    private function getQueue(){
        if ($this->queue === null) {
            $this->queue = new Pheanstalk($this->location->getHost(), $this->location->getPort());
        }
        return $this->queue;
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function serialize() {
        return serialize($this->location);
    }
     /**
     * @inheritdoc
     *
     * @param string $serialized
     */
    public function unserialize( $serialized) {
        $this->location = unserialize($serialized);
    }
    
    public function __destruct()
    {
        $this->disconnect();
    }

}