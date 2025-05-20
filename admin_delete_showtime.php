<?php
session_start();
require_once 'db.php';

// Kiểm tra xem admin đã đăng nhập chưa
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

// Kiểm tra xem có ID suất chiếu trên URL không
if (isset($_GET['id'])) {
    $showtime_id = $_GET['id'];

    // Kiểm tra xem suất chiếu có tồn tại không
    $check_sql = "SELECT * FROM showtimes WHERE id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $showtime_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows === 0) {
        header('Location: admin_showtimes.php?msg=not_found');
        exit;
    }

    // Kiểm tra xem có đặt vé nào liên quan đến suất chiếu này không
    $check_bookings_sql = "SELECT COUNT(*) as booking_count FROM bookings WHERE showtime_id = ? AND status != 'cancelled'";
    $check_bookings_stmt = $conn->prepare($check_bookings_sql);
    $check_bookings_stmt->bind_param("i", $showtime_id);
    $check_bookings_stmt->execute();
    $bookings_result = $check_bookings_stmt->get_result();
    $bookings_count = $bookings_result->fetch_assoc()['booking_count'];

    if ($bookings_count > 0) {
        header('Location: admin_showtimes.php?msg=has_bookings');
        exit;
    }

    // Bắt đầu transaction
    $conn->begin_transaction();

    try {
        // Xóa các ghế đã đặt liên quan đến suất chiếu này
        $delete_booked_seats_sql = "DELETE bs FROM booked_seats bs 
                                  INNER JOIN bookings b ON bs.booking_id = b.id 
                                  WHERE b.showtime_id = ?";
        $delete_booked_seats_stmt = $conn->prepare($delete_booked_seats_sql);
        $delete_booked_seats_stmt->bind_param("i", $showtime_id);
        $delete_booked_seats_stmt->execute();

        // Xóa các đặt vé liên quan
        $delete_bookings_sql = "DELETE FROM bookings WHERE showtime_id = ?";
        $delete_bookings_stmt = $conn->prepare($delete_bookings_sql);
        $delete_bookings_stmt->bind_param("i", $showtime_id);
        $delete_bookings_stmt->execute();

        // Xóa suất chiếu
        $delete_showtime_sql = "DELETE FROM showtimes WHERE id = ?";
        $delete_showtime_stmt = $conn->prepare($delete_showtime_sql);
        $delete_showtime_stmt->bind_param("i", $showtime_id);
        $delete_showtime_stmt->execute();

        // Commit transaction nếu mọi thứ OK
        $conn->commit();
        header('Location: admin_showtimes.php?msg=deleted');
        exit;

    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
        header('Location: admin_showtimes.php?msg=error&error_msg=' . urlencode($e->getMessage()));
        exit;
    }

} else {
    // Không có ID suất chiếu, chuyển hướng về trang quản lý suất chiếu
    header('Location: admin_showtimes.php');
    exit;
}
?> 