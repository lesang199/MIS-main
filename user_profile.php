<?php
include 'db.php';
session_start();

// Kiểm tra đăng nhập người dùng
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Giả định có trang login.php
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Lấy thông tin người dùng
$user_sql = "SELECT id, username, email, phone, full_name FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_sql);
$user_stmt->bind_param("i", $user_id);
$user_stmt->execute();
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

if (!$user) {
    // User not found (should not happen if login is correct, but good practice)
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// Lấy danh sách đặt vé của người dùng
$bookings_sql = "SELECT b.*, m.title as movie_title, s.showtime, r.name as room_name 
                FROM bookings b 
                JOIN showtimes s ON b.showtime_id = s.id 
                JOIN movies m ON s.movie_id = m.id 
                JOIN rooms r ON s.room_id = r.id 
                WHERE b.user_id = ? 
                ORDER BY b.booking_date DESC";
$bookings_stmt = $conn->prepare($bookings_sql);
$bookings_stmt->bind_param("i", $user_id);
$bookings_stmt->execute();
$bookings_result = $bookings_stmt->get_result();

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông tin cá nhân và Vé đã đặt - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            color: #333;
            font-family: 'Arial', sans-serif;
        }
        .container {
            margin-top: 30px;
            margin-bottom: 50px;
        }
        h2, h3 {
            color: #e53935; /* Red color often associated with cinema/CGV */
            margin-bottom: 20px;
        }
        .card {
            margin-bottom: 30px;
        }
        .table th, .table td {
            vertical-align: middle;
        }
        .status-badge {
            padding: .35em .65em;
            border-radius: .25rem;
            font-size: 75%;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            display: inline-block;
        }
        .status-paid { background-color: #28a745; color: white; }
        .status-pending { background-color: #ffc107; color: #212529; }
        .status-cancelled { background-color: #dc3545; color: white; }
    </style>
</head>
<body>

    <!-- Navigation (Có thể include từ file header chung) -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="https://www.cgv.vn/skin/frontend/cgv/default/images/cgvlogo.png" alt="CGV Logo" height="30">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Phim đang chiếu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Phim sắp chiếu</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                     <li class="nav-item"><a class="nav-link" href="user_profile.php">Thông tin cá nhân</a></li>
                    <li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>
                </ul>
            </div>
        </div>
    </nav>

<div class="container">

    <h2>Thông tin cá nhân</h2>
    <div class="card">
        <div class="card-body">
            <p><strong>Tên đăng nhập:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <p><strong>Họ và tên:</strong> <?php echo htmlspecialchars($user['full_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($user['phone']); ?></p>
            <!-- Thêm các thông tin khác nếu có -->
        </div>
    </div>

    <h2 class="mt-4">Vé đã đặt</h2>
    <div class="card">
        <div class="card-body">
            <?php if ($bookings_result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Mã đặt vé</th>
                                <th>Phim</th>
                                <th>Rạp</th>
                                <th>Thời gian chiếu</th>
                                <th>Ghế</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($booking = $bookings_result->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $booking['id']; ?></td>
                                    <td><?php echo htmlspecialchars($booking['movie_title']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['room_name']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($booking['showtime'])); ?></td>
                                    <td><?php echo htmlspecialchars($booking['seats']); ?></td>
                                    <td><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?> VNĐ</td>
                                    <td>
                                        <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch($booking['status']) {
                                                case 'paid':
                                                    $status_class = 'status-paid';
                                                    $status_text = 'Đã thanh toán';
                                                    break;
                                                case 'pending':
                                                    $status_class = 'status-pending';
                                                    $status_text = 'Chờ thanh toán';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'status-cancelled';
                                                    $status_text = 'Đã hủy';
                                                    break;
                                                default:
                                                    $status_class = '';
                                                    $status_text = $booking['status'];
                                            }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>">
                                            <?php echo $status_text; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></td>
                                    <td>
                                         <!-- Link xem chi tiết đặt vé (tùy chọn) -->
                                        <a href="view_booking_user.php?id=<?php echo $booking['id']; ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i> Xem</a>
                                         <!-- Thêm nút Hủy nếu trạng thái là pending và chưa quá giờ chiếu -->
                                         <?php if ($booking['status'] == 'pending' && strtotime($booking['showtime']) > time()): ?>
                                             <a href="cancel_booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Bạn có chắc chắn muốn hủy đặt vé này?')"><i class="fas fa-times"></i> Hủy</a>
                                         <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    Bạn chưa có đặt vé nào.
                </div>
            <?php endif; ?>
        </div>
    </div>

</div>

    <!-- Footer (Có thể include từ file footer chung) -->
    <footer class="bg-light text-center text-lg-start mt-auto">
        <div class="container p-4">
            <div class="row">
                <div class="col-lg-6 col-md-12 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Về chúng tôi</h5>
                    <p>
                        Hệ thống rạp chiếu phim hiện đại.
                    </p>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Liên kết</h5>
                    <ul class="list-unstyled mb-0">
                        <li>
                            <a href="#!" class="text-dark">Điều khoản sử dụng</a>
                        </li>
                        <li>
                            <a href="#!" class="text-dark">Chính sách bảo mật</a>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 mb-4 mb-md-0">
                    <h5 class="text-uppercase">Liên hệ</h5>
                    <ul class="list-unstyled mb-0">
                        <li>
                            <p><i class="fas fa-home me-2"></i> TP. Hồ Chí Minh, Việt Nam</p>
                        </li>
                        <li>
                            <p><i class="fas fa-envelope me-2"></i> info@example.com</p>
                        </li>
                        <li>
                            <p><i class="fas fa-phone me-2"></i> + 01 234 567 89</p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="text-center p-3" style="background-color: rgba(0, 0, 0, 0.2);">
            © 2023 CGV Cinemas
        </div>
    </footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?> 