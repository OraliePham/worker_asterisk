<?php
// bin/test_cdr_producer.php

// DÒNG QUAN TRỌNG CẦN THÊM VÀO!
// Dòng này nạp tất cả các thư viện được quản lý bởi Composer.
require_once dirname(__FILE__) . '/../vendor/autoload.php';

// Nạp các file cấu hình và thư viện tùy chỉnh của bạn
require_once dirname(__FILE__) . '/../includes/config.php';
require_once dirname(__FILE__) . '/../beantalk_cluster/ClusterPheanstalk.php'; // Sử dụng lớp Cluster để gửi job

echo "Producer starting...\n";

// Tạo cluster queue instance
$queueCluster = new ClusterPheanstalk(QUEUE_SERVER);

$jobData = [
    'uniqueid' => '1665987451.123',
    'source' => '101',
    'destination' => '102',
    'duration' => 60,
    'disposition' => 'ANSWERED'
];

$tubeName = 'cdr_processing';

try {
    $queueCluster->putInTube($tubeName, json_encode($jobData));
    echo "Successfully sent 1 job to tube '{$tubeName}'.\n";
    print_r($jobData);

} catch (Exception $e) {
    echo "Error sending job: " . $e->getMessage() . "\n";
}

echo "\nProducer finished.\n";