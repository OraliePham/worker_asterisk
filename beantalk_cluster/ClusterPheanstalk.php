<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ClusterPheanstalk
 *
 * @author nguyenngocbinh
 */
if (!class_exists('Pheanstalk\Pheanstalk')) {
    require_once 'library/vendor/autoload.php';
}

if (!class_exists('ClusterPheanstalk')) {

    class ClusterPheanstalk {

        private $upstream_queues = [];

        public function __construct($hosts) {
            foreach (explode(',', $hosts) as $host) {
                $host = preg_replace('/\s+/', '', $host);
                $test = null;
                try {
                    $socketFactory = new Pheanstalk\SocketFactory($host, 11300);
                    $test = Pheanstalk\Pheanstalk::createWithFactory($socketFactory);
                } catch (Pheanstalk\Exception\ClientException $ex) {
                     $test = null;
                } catch (Pheanstalk\Exception\ConnectionException $cex) {
                    $test = null;
                }
                if (!empty($test)) {
                    $this->upstream_queues[] = $test;
                }
            }
        }

        public function useTube($tube) {
            try {
                $randomidx = rand(0, count($this->upstream_queues) - 1);
                return $this->upstream_queues[$randomidx]->useTube($tube);
            } catch (Exception $e) {
                foreach ($this->upstream_queues as $queue){
                    try{
                        return $queue->useTube($tube);
                    } catch (Exception $ex) {

                    }
                }
                throw $e;
            }
        }

        public function putInTube($tube, $data, $priority = Pheanstalk\Pheanstalk::DEFAULT_PRIORITY, $delay = Pheanstalk\Pheanstalk::DEFAULT_DELAY, $ttr = Pheanstalk\Pheanstalk::DEFAULT_TTR) {
            return $this->useTube($tube)->put($data, $priority, $delay, $ttr);
        }

        public function getSize() {
            return count($this->upstream_queues);
        }
    }
    
}