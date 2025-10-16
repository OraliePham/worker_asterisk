<?php

/*
 * Copyright © 2014 South Telecom
 */

namespace Worldfone\BeanstalkMq;
use Pheanstalk\Pheanstalk;
/**
 * Description of BeanstalkMqServer
 *
 * @author nguyenngocbinh
 */
final class BeanstalkMqServer implements Server {
    /**
     * @var Location
     */
    private $location;
    
    /**
     * @var Pheanstalk\Pheanstalk
     */
    private $queue;
    /**
     * @var array
     */
    private $listeners = [];

    /**
     * @param Location $location
     */
    public function __construct(Location $location)
    {
        $this->location = $location;
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
     * @inheritdoc
     *
     * @param string $name
     * @param Closure $closure
     *
     * @return $this
     */
    public function addListener($name, Callable $closure)
    {
        if (!isset($this->listeners[$name])) {
            $this->listeners[$name] = [];
        }

        $this->listeners[$name][] = $closure;

        return $this;
    }
    
    /**
     * @inheritdoc
     */
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
     * @param Event $event
     *
     * @return $this
     */
    private function dispatchEvent(Event $event)
    {
        $name = $event->getName();

        if (isset($this->listeners[$name])) {
            foreach ($this->listeners[$name] as $closure) {
                call_user_func_array($closure, $event->getParameters());
            }
        }

        return $this;
    }

    public function emit($name, array $parameters = array()) {
        return $this->dispatchEvent(
            new BeanstalkMqEvent($name, $parameters)
        );
    }

    /**
     * @return Location
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * Removes an event listener.
     *
     * @param string $name
     * @param Closure $closure
     */
    public function removeListener($name, Callable $closure)
    {
        if (isset($this->listeners[$name])) {
            $index = \array_search($closure, $this->listeners[$name], true);
            if (false !== $index) {
                unset($this->listeners[$name][$index]);
                if (\count($this->listeners[$name]) === 0) {
                    unset($this->listeners[$name]);
                }
            }
        }
        return $this;
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public function serialize() {
        return serialize($this->location);
    }

    public function tick() {
        $this->getQueue();
        try {
            $job = $this->queue->watch($this->location->getTube())->ignore('default')->reserve(1);
            if ($job) {
                $event = BeanstalkMqEvent::json_decode($job->getData());
                $this->dispatchEvent($event);
                $this->queue->delete($job);
                return true;
            }
        } catch (Exception $ex) {
            return false;
        }
        return false;
    }

    /**
     * @inheritdoc
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $this->location = unserialize($serialized);
    }

    public function __destruct()
    {
        $this->disconnect();
    }
}
