<?php
require_once '../config/config.php';
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    echo "<h2>محتوى جدول المستخدمين (users)</h2>";
    $stmt = $conn->query("SELECT * FROM users WHERE role = 'distributor'");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";

    echo "<h2>محتوى جدول الموزعين (distributors)</h2>";
    $stmt = $conn->query("SELECT d.*, u.username FROM distributors d JOIN users u ON d.user_id = u.id");
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";

} catch (PDOException $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
