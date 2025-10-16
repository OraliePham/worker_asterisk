<?php
namespace App\Processor;

interface JobHandlerInterface
{
    /**
     * Thực thi logic xử lý chính cho job.
     * @param array $payload Dữ liệu của job.
     */
    public function handle(array $payload): void;
}