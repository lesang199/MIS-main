<?php
session_start();
require_once 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Lấy thông tin admin
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Lấy thống kê
$stats = [
    'movies' => $conn->query("SELECT COUNT(*) as count FROM movies")->fetch_assoc()['count'],
    'bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'],
    'users' => $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()['count'],
    'revenue' => $conn->query("SELECT SUM(total_amount) as total FROM bookings WHERE status = 'confirmed'")->fetch_assoc()['total'] ?? 0
];


?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">CGV Admin</h3>
                <nav>
                    <a href="admin_dashboard.php" class="active"><i class="fas fa-home"></i> Trang chủ</a>
                    <a href="admin_movies.php"><i class="fas fa-film"></i> Quản lý phim</a>
                    <a href="admin_showtimes.php"><i class="fas fa-clock"></i> Quản lý suất chiếu</a>
                    <a href="admin_bookings.php"><i class="fas fa-ticket-alt"></i> Quản lý đặt vé</a>
                    <a href="admin_users.php"><i class="fas fa-users"></i> Quản lý người dùng</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="page-title">Dashboard</h2>
                        <div class="admin-info">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php echo htmlspecialchars($admin_username); ?>
                        </div>
                    </div>
                </div>

                <!-- Stats Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5><i class="fas fa-film me-2"></i>Tổng số phim</h5>
                            <p class="display-4"><?php echo $stats['movies']; ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5><i class="fas fa-ticket-alt me-2"></i>Tổng số đặt vé</h5>
                            <p class="display-4"><?php echo $stats['bookings']; ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5><i class="fas fa-users me-2"></i>Tổng số người dùng</h5>
                            <p class="display-4"><?php echo $stats['users']; ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card">
                            <h5><i class="fas fa-money-bill-wave me-2"></i>Doanh thu</h5>
                            <p class="display-4"><?php echo number_format($stats['revenue'], 0, ',', '.'); ?> VNĐ</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Bookings -->
              
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>