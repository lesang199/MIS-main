<?php
session_start();
require_once 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $booking_id = isset($_POST['booking_id']) ? intval($_POST['booking_id']) : 0;
    $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : '';
    $total_amount = isset($_POST['total_amount']) ? floatval($_POST['total_amount']) : 0;
    $user_id = $_SESSION['user_id'];
     if ($booking_id <= 0 || $total_amount <= 0 || empty($payment_method)) {
        $error = 'Dữ liệu thanh toán không hợp lệ.';
    }
    else{
        // Bắt đầu transaction
    $conn->begin_transaction();
    try {
    // Thêm thông tin thanh toán
    $sql = "INSERT INTO payments (booking_id, user_id, amount, payment_method, payment_date, status) 
            VALUES (?, ?, ?, ?, NOW(), 'completed')";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("iids", $booking_id, $user_id, $total_amount, $payment_method);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Cập nhật trạng thái booking
    $sql = "UPDATE bookings SET status = 'confirmed' WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("ii", $booking_id, $user_id);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    // Commit transaction
    $conn->commit();

    // Chuyển hướng đến trang thành công
    header('Location: booking_success.php?booking_id=' . $booking_id);
    exit;
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    $error = 'Có lỗi xảy ra trong quá trình thanh toán: ' . $e->getMessage();
}

  
    if (isset($error)) {
    echo '<div class="alert alert-danger">' . $error . '</div>';
    }
}
}
?> 