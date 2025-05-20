<?php
session_start();

// Hủy session admin
unset($_SESSION['admin_logged_in']);

// Hoặc hủy toàn bộ session nếu không có thông tin user nào khác cần giữ lại
// session_destroy();

// Chuyển hướng về trang đăng nhập admin
header('Location: admin_login.php');
exit;
?> 