<?php
require_once '../config/config.php';
require_once '../config/database.php';

try {
    $db = new Database();
    $conn = $db->getConnection();

    // بداية المعاملة
    $conn->beginTransaction();

    // إضافة المستخدمين
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

    // تأكيد المعاملة
    $conn->commit();
    echo "تم إضافة الموزعين بنجاح!";

} catch (PDOException $e) {
    // التراجع عن المعاملة في حالة حدوث خطأ
    $conn->rollBack();
    echo "خطأ: " . $e->getMessage();
}

// التحقق من البيانات المضافة
try {
    $stmt = $conn->query("SELECT d.*, u.username FROM distributors d JOIN users u ON d.user_id = u.id");
    echo "\n\nقائمة الموزعين المضافين:\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "\nاسم الموزع: " . $row['name'];
        echo "\nاسم المستخدم: " . $row['username'];
        echo "\nالهاتف: " . $row['phone'];
        echo "\n-------------------";
    }
} catch (PDOException $e) {
    echo "خطأ في عرض البيانات: " . $e->getMessage();
}
?>
