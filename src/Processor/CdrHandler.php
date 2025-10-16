<?php
namespace App\Processor;

class CdrHandler implements JobHandlerInterface
{
    private $db; // Sẽ là đối tượng kết nối mysqli

    public function __construct($dbConnection)
    {
        $this->db = $dbConnection;
    }

    public function handle(array $payload): void
    {
        $cdrUniqueId = $payload['uniqueid'] ?? null;
        if (!$cdrUniqueId) {
            echo "   -> [CdrHandler] Error: Job is missing 'uniqueid'.\n";
            return;
        }

        echo "   -> [CdrHandler] Processing CDR with uniqueid: {$cdrUniqueId}\n";

        // --- LOGIC XỬ LÝ CDR THỰC TẾ SẼ Ở ĐÂY ---
        // 1. Dùng $this->db để truy vấn CSDL, lấy chi tiết bản ghi CDR.
        // 2. Làm giàu dữ liệu (kết hợp thông tin từ các bảng khác).
        // 3. Chuẩn hóa dữ liệu.
        // 4. Gọi API callback hoặc đẩy dữ liệu vào Elasticsearch.
        // -------------------------------------------

        // Giả lập thời gian xử lý
        sleep(1);
    }
}