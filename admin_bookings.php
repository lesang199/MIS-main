<?php
include 'db.php';
session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$message = '';

// Xử lý xóa đặt vé
if (isset($_GET['delete'])) {
    $booking_id = $_GET['delete'];
    $delete_sql = "DELETE FROM bookings WHERE id = ?";
    $stmt = $conn->prepare($delete_sql);
    $stmt->bind_param("i", $booking_id);
    
    if ($stmt->execute()) {
        $message = '<div class="alert alert-success">Xóa đặt vé thành công!</div>';
    } else {
        $message = '<div class="alert alert-danger">Lỗi khi xóa đặt vé!</div>';
    }
}

// Lấy danh sách đặt vé
$bookings_sql = "SELECT b.*, m.title as movie_title, s.showtime, r.name as room_name, u.username 
                FROM bookings b 
                JOIN showtimes s ON b.showtime_id = s.id 
                JOIN movies m ON s.movie_id = m.id 
                JOIN rooms r ON s.room_id = r.id 
                JOIN users u ON b.user_id = u.id 
                ORDER BY b.booking_date DESC";
$bookings_result = $conn->query($bookings_sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đặt Vé - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            background-color: #343a40;
            padding-top: 20px;
        }
        .sidebar a {
            color: #fff;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
        }
        .sidebar a:hover {
            background-color: #495057;
        }
        .content {
            padding: 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-white text-center mb-4">CGV Admin</h3>
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
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Quản lý Đặt Vé</h2>
                </div>

                <?php echo $message; ?>

                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Danh Sách Đặt Vé</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Người đặt</th>
                                        <th>Phim</th>
                                        <th>Phòng</th>
                                        <th>Suất chiếu</th>
                                        <th>Ghế</th>
                                        <th>Ngày đặt</th>
                                        <th>Tổng tiền</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($bookings_result && $bookings_result->num_rows > 0) {
                                        while($booking = $bookings_result->fetch_assoc()) {
                                            echo '<tr>';
                                            echo '<td>' . $booking['id'] . '</td>';
                                            echo '<td>' . $booking['username'] . '</td>';
                                            echo '<td>' . $booking['movie_title'] . '</td>';
                                            echo '<td>' . $booking['room_name'] . '</td>';
                                            echo '<td>' . date('d/m/Y H:i', strtotime($booking['showtime'])) . '</td>';
                                            echo '<td>' . $booking['seats'] . '</td>';
                                            echo '<td>' . date('d/m/Y H:i', strtotime($booking['booking_date'])) . '</td>';
                                            echo '<td>' . number_format($booking['total_amount'], 0, ',', '.') . ' VNĐ</td>';
                                            echo '<td>' . $booking['status'] . '</td>';
                                            echo '<td>
                                                    <a href="view_booking.php?id=' . $booking['id'] . '" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="admin_bookings.php?delete=' . $booking['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Bạn có chắc chắn muốn xóa đặt vé này?\')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                  </td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="10" class="text-center">Không có đặt vé nào</td></tr>';
                                    }
                                    ?>
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

<?php
$conn->close();
?> 