<?php
include 'db.php';
session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

// Kiểm tra ID đặt vé
if (!isset($_GET['id'])) {
    header("Location: admin_bookings.php");
    exit();
}

$booking_id = $_GET['id'];

// Lấy thông tin chi tiết đặt vé
$sql = "SELECT b.*, m.title as movie_title, s.showtime, r.name as room_name, 
        u.username, u.email, u.phone
        FROM bookings b 
        JOIN showtimes s ON b.showtime_id = s.id 
        JOIN movies m ON s.movie_id = m.id 
        JOIN rooms r ON s.room_id = r.id 
        JOIN users u ON b.user_id = u.id 
        WHERE b.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$result = $stmt->get_result();
$booking = $result->fetch_assoc();

if (!$booking) {
    header("Location: admin_bookings.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chi tiết Đặt Vé - CGV Cinemas</title>
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
                    <h2>Chi tiết Đặt Vé</h2>
                    <a href="admin_bookings.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h4>Thông tin đặt vé</h4>
                                <table class="table">
                                    <tr>
                                        <th>Mã đặt vé:</th>
                                        <td>#<?php echo $booking['id']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Phim:</th>
                                        <td><?php echo $booking['movie_title']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Phòng chiếu:</th>
                                        <td><?php echo $booking['room_name']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Suất chiếu:</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($booking['showtime'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Ghế:</th>
                                        <td><?php echo $booking['seats']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Tổng tiền:</th>
                                        <td><?php echo number_format($booking['total_amount'], 0, ',', '.') . ' VNĐ'; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Trạng thái:</th>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            switch($booking['status']) {
                                                case 'pending':
                                                    $status_class = 'warning';
                                                    break;
                                                case 'paid':
                                                    $status_class = 'success';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="<?php echo $status_class; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Ngày đặt:</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h4>Thông tin người đặt</h4>
                                <table class="table">
                                    <tr>
                                        <th>Tên đăng nhập:</th>
                                        <td><?php echo $booking['username']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Email:</th>
                                        <td><?php echo $booking['email']; ?></td>
                                    </tr>
                                    <tr>
                                        <th>Số điện thoại:</th>
                                        <td><?php echo $booking['phone']; ?></td>
                                    </tr>
                                </table>
                            </div>
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