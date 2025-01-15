<?php
// حذف ملف قاعدة البيانات القديم إذا وجد
$dbFile = __DIR__ . '/inventory.db';
if (file_exists($dbFile)) {
    unlink($dbFile);
}

// إعادة إنشاء قاعدة البيانات
require_once '../config/config.php';
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();
    
    // إضافة الموزعين
    $users = [
        ['distributor1', 'password123', 'Distributeur 1', 'Adresse 1', '0611111111'],
        ['distributor2', 'password123', 'Distributeur 2', 'Adresse 2', '0622222222'],
        ['distributor3', 'password123', 'Distributeur 3', 'Adresse 3', '0633333333']
    ];

    foreach ($users as $user) {
        // إضافة المستخدم
        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, 'distributor')");
        $stmt->execute([$user[0], password_hash($user[1], PASSWORD_DEFAULT)]);
        $userId = $conn->lastInsertId();

        // إضافة الموزع
        $stmt = $conn->prepare("INSERT INTO distributors (user_id, name, address, phone) VALUES (?, ?, ?, ?)");
        $stmt->execute([$userId, $user[2], $user[3], $user[4]]);
    }

    echo "<h2>تم إعادة إنشاء قاعدة البيانات وإضافة الموزعين بنجاح!</h2>";
    
    // عرض الموزعين المضافين
    $stmt = $conn->query("SELECT d.*, u.username FROM distributors d JOIN users u ON d.user_id = u.id");
    echo "<h3>الموزعون المضافون:</h3>";
    echo "<pre>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";

} catch (PDOException $e) {
    echo "خطأ: " . $e->getMessage();
}
?>
