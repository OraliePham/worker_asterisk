<?php
// Ghi log lỗi vào file riêng theo ngày
ini_set("log_errors", 1);
ini_set("error_log", dirname(__FILE__) . "/../var/log/worker_errors.".date("Ymd").".txt");

// Nạp các file cấu hình và kết nối cơ bản
require_once dirname(__FILE__) . '/../includes/config.php';
require_once dirname(__FILE__) . '/../beantalk/library/vendor/autoload.php';
require_once dirname(__FILE__) . '/../includes/MysqliConnection.php';

// Nạp các lớp xử lý logic của chúng ta
require_once dirname(__FILE__) . '/../src/Processor/JobHandlerInterface.php';
require_once dirname(__FILE__) . '/../src/Processor/CdrHandler.php';
require_once dirname(__FILE__) . '/../src/Processor/ProcessorFactory.php';

use App\Processor\ProcessorFactory;

echo "CDR Pipeline Worker starting...\n";

// Khởi tạo các kết nối cần thiết
$dbConnection = MysqliConnection::getInstance(); // Tái sử dụng lớp kết nối có sẵn
$queue = new Pheanstalk\Pheanstalk(QUEUE_SERVER);
$factory = new ProcessorFactory($dbConnection); // Truyền kết nối DB vào nhà máy

// Worker sẽ lắng nghe trên 2 tube này
$queue->watch('cdr_processing')->watch('queuelog_processing')->ignore('default');

$jobCount = 0;
$maxJobs = 10000; // Worker sẽ tự khởi động lại sau 10,000 job

while ($jobCount < $maxJobs) {
    try {
        // Chờ và nhận job, có timeout
        if (!$job = $queue->reserve(30)) {
            continue; // Nếu không có job nào sau 30s, lặp lại để kiểm tra
        }

        $jobCount++;
        $jobId = $job->getId();
        $payload = $job->getData();
        $jobStats = $queue->statsJob($job);
        $tubeName = $jobStats['tube'];

        echo "Received job #{$jobId} from tube '{$tubeName}'. Processing...\n";

        // --- Logic điều phối thông minh ---
        // 1. Lấy đúng chuyên gia từ nhà máy
        $handler = $factory->getHandler($tubeName);

        // 2. Giao việc cho chuyên gia
        $data = json_decode($payload, true);
        $handler->handle($data);
        // ---------------------------------

        // Xóa job sau khi xử lý thành công
        $queue->delete($job);
        echo "Finished and deleted job #{$jobId}.\n\n";

    } catch (Exception $e) {
        echo "Error processing job: " . $e->getMessage() . "\n";
        if (isset($job)) {
            // Nếu có lỗi, chôn job để xem xét sau
            $queue->bury($job);
        }
        // Chờ một chút trước khi thử lại
        sleep(5);
    }
}

echo "Worker reached max job limit ({$maxJobs}). Exiting to restart.\n";