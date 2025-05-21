<?php
session_start();
require_once 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Kiểm tra có ID phim không
if (!isset($_GET['id'])) {
    header('Location: admin_movies.php');
    exit();
}

$movie_id = $_GET['id'];

// Bắt đầu transaction
$conn->begin_transaction();

try {
    // Xóa các suất chiếu liên quan trước
    $sql = "DELETE FROM showtimes WHERE movie_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    
    // Xóa các đặt vé liên quan
    $sql = "DELETE FROM bookings WHERE showtime_id IN (SELECT id FROM showtimes WHERE movie_id = ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    
    // Lấy thông tin poster trước khi xóa phim
    $sql = "SELECT poster FROM movies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $movie = $result->fetch_assoc();
    
    // Xóa phim
    $sql = "DELETE FROM movies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    
    // Xóa file poster nếu tồn tại
    if ($movie && !empty($movie['poster'])) {
        $poster_path = "uploads/posters/" . $movie['poster'];
        if (file_exists($poster_path)) {
            unlink($poster_path);
        }
    }
    
    // Commit transaction nếu mọi thứ OK
    $conn->commit();
    $_SESSION['success_message'] = "Xóa phim thành công!";
    
} catch (Exception $e) {
    // Rollback nếu có lỗi
    $conn->rollback();
    $_SESSION['error_message'] = "Lỗi khi xóa phim: " . $e->getMessage();
}

// Chuyển hướng về trang quản lý phim
header('Location: admin_movies.php');
exit();
?> 