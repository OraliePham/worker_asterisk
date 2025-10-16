<?php
namespace App\Processor;

use Exception;

class ProcessorFactory
{
    private $dbConnection;

    public function __construct($dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function getHandler(string $tubeName): JobHandlerInterface
    {
        switch ($tubeName) {
            case 'cdr_processing':
                return new CdrHandler($this->dbConnection);

            // Sau này bạn chỉ cần thêm case cho 'queuelog_processing' ở đây
            // case 'queuelog_processing':
            //     return new QueueLogHandler($this->dbConnection);

            default:
                throw new Exception("No handler found for tube '{$tubeName}'.");
        }
    }
}