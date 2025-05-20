<?php
session_start();
require_once 'db.php';

// Kiểm tra xem admin đã đăng nhập chưa
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

// Kiểm tra xem có ID phim trên URL không
if (isset($_GET['id'])) {
    $movie_id = $_GET['id'];

    // Chuẩn bị và thực thi câu lệnh DELETE
    $sql = "DELETE FROM movies WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $movie_id);

    if ($stmt->execute()) {
        // Xóa thành công, chuyển hướng về trang quản lý phim
        header('Location: admin_movies.php?msg=deleted');
        exit;
    } else {
        // Xảy ra lỗi khi xóa
        $error_msg = 'Lỗi khi xóa phim: ' . $stmt->error;
        // Chuyển hướng về trang quản lý phim kèm thông báo lỗi
        header('Location: admin_movies.php?msg=error&error_msg=' . urlencode($error_msg));
        exit;
    }

    $stmt->close();

} else {
    // Không có ID phim, chuyển hướng về trang quản lý phim
    header('Location: admin_movies.php');
    exit;
}
?> 