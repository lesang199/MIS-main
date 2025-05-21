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

// Xử lý cập nhật trạng thái đặt vé
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $status = $_POST['status'];
    
    $sql = "UPDATE bookings SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $status, $booking_id);
    
    if ($stmt->execute()) {
        $success_message = "Cập nhật trạng thái thành công!";
    } else {
        $error_message = "Lỗi: " . $conn->error;
    }
}

// Lấy danh sách đặt vé
$bookings = $conn->query("
    SELECT b.*, m.title as movie_title, u.username, s.showtime, s.price, r.name as room_name
    FROM bookings b 
    JOIN showtimes s ON b.showtime_id = s.id
    JOIN movies m ON s.movie_id = m.id 
    JOIN users u ON b.user_id = u.id
    JOIN rooms r ON s.room_id = r.id
    ORDER BY b.booking_date DESC
");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý đặt vé - CGV Cinemas</title>
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
                    <a href="admin_dashboard.php"><i class="fas fa-home"></i> Trang chủ</a>
                    <a href="admin_movies.php"><i class="fas fa-film"></i> Quản lý phim</a>
                    <a href="admin_showtimes.php"><i class="fas fa-clock"></i> Quản lý suất chiếu</a>
                    <a href="admin_bookings.php" class="active"><i class="fas fa-ticket-alt"></i> Quản lý đặt vé</a>
                    <a href="admin_users.php"><i class="fas fa-users"></i> Quản lý người dùng</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="page-title">Quản lý đặt vé</h2>
                        <div class="admin-info">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php echo htmlspecialchars($admin_username); ?>
                        </div>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <!-- Bookings List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Danh sách đặt vé</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Phim</th>
                                        <th>Người đặt</th>
                                        <th>Phòng chiếu</th>
                                        <th>Suất chiếu</th>
                                        <th>Ghế</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Ngày đặt</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $booking['id']; ?></td>
                                        <td><?php echo htmlspecialchars($booking['movie_title']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                        <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($booking['showtime'])); ?></td>
                                        <td><?php echo $booking['seats']; ?></td>
                                        <td><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?> VNĐ</td>
                                        <td>
                                            <form action="" method="POST" class="d-inline">
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                    <option value="pending" <?php echo $booking['status'] == 'pending' ? 'selected' : ''; ?>>Chờ thanh toán</option>
                                                    <option value="confirmed" <?php echo $booking['status'] == 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                                    <option value="cancelled" <?php echo $booking['status'] == 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                                </select>
                                                <input type="hidden" name="update_status" value="1">
                                            </form>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></td>
                                        <td>
                                            <a href="admin_view_booking.php?id=<?php echo $booking['id']; ?>" 
                                               class="btn btn-info btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="admin_delete_booking.php?id=<?php echo $booking['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa đặt vé này?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 