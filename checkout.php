<?php
include 'db.php';
session_start();

// Kiểm tra đăng nhập người dùng
if (!isset($_SESSION['user_id'])) {
    // Lưu lại trang hiện tại để redirect sau khi đăng nhập thành công
    $_SESSION['redirect_url'] = $_SERVER['REQUEST_URI'];
    header("Location: login.php"); // Giả định có trang login.php cho người dùng
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// Lấy showtime_id và seats từ URL
$showtime_id = isset($_GET['showtime_id']) ? intval($_GET['showtime_id']) : 0;
$selected_seats_string = isset($_GET['seats']) ? $_GET['seats'] : '';
$selected_seats = $selected_seats_string ? explode(',', $selected_seats_string) : [];

$showtime = null;
$total_amount = 0;

if ($showtime_id > 0 && !empty($selected_seats)) {
    // Lấy thông tin suất chiếu, phim, phòng
    $sql = "SELECT s.id, s.showtime, s.price, 
            m.title as movie_title,
            m.poster,
            m.duration,
            r.name as room_name
            FROM showtimes s
            JOIN movies m ON s.movie_id = m.id
            JOIN rooms r ON s.room_id = r.id
            WHERE s.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $showtime_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $showtime = $result->fetch_assoc();

    if ($showtime) {
        // Tính tổng tiền
        $total_amount = $showtime['price'] * count($selected_seats);

        // Kiểm tra xem các ghế đã chọn còn trống không (tránh trường hợp đặt trùng)
        $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
        $check_seats_sql = "SELECT seat_number FROM booked_seats WHERE showtime_id = ? AND seat_number IN ($placeholders)";
        $check_seats_stmt = $conn->prepare($check_seats_sql);
        $types = "i" . str_repeat("s", count($selected_seats));
        $params = array_merge([$showtime_id], $selected_seats);
        $check_seats_stmt->bind_param($types, ...$params);
        $check_seats_stmt->execute();
        $booked_conflicting_seats_result = $check_seats_stmt->get_result();

        if ($booked_conflicting_seats_result->num_rows > 0) {
            $booked_seats_list = [];
            while($row = $booked_conflicting_seats_result->fetch_assoc()) {
                $booked_seats_list[] = $row['seat_number'];
            }
            $message = '<div class="alert alert-danger">Rất tiếc, các ghế ' . implode(', ', $booked_seats_list) . ' vừa được đặt. Vui lòng chọn lại ghế khác. <a href="select_seats.php?showtime_id=' . $showtime_id . '">Quay lại chọn ghế</a></div>';
            $showtime = null; // Invalidate showtime to prevent booking
        }

    } else {
        $message = '<div class="alert alert-danger">Không tìm thấy thông tin suất chiếu.</div>';
    }
} else {
    $message = '<div class="alert alert-danger">Thông tin đặt vé không hợp lệ.</div>';
}

// Xử lý khi người dùng xác nhận đặt vé (ví dụ: nhấn nút "Xác nhận")
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['confirm_booking'])) {
    if ($showtime_id > 0 && !empty($selected_seats)) {
        // Re-check seat availability just before inserting (important for race conditions)
        $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
        $check_seats_sql = "SELECT seat_number FROM booked_seats WHERE showtime_id = ? AND seat_number IN ($placeholders)";
        $check_seats_stmt = $conn->prepare($check_seats_sql);
        $types = "i" . str_repeat("s", count($selected_seats));
        $params = array_merge([$showtime_id], $selected_seats);
        $check_seats_stmt->bind_param($types, ...$params);
        $check_seats_stmt->execute();
        $booked_conflicting_seats_result = $check_seats_stmt->get_result();

        if ($booked_conflicting_seats_result->num_rows > 0) {
             $booked_seats_list = [];
            while($row = $booked_conflicting_seats_result->fetch_assoc()) {
                $booked_seats_list[] = $row['seat_number'];
            }
            $message = '<div class="alert alert-danger">Rất tiếc, các ghế ' . implode(', ', $booked_seats_list) . ' vừa được đặt. Vui lòng chọn lại ghế khác. <a href="select_seats.php?showtime_id=' . $showtime_id . '">Quay lại chọn ghế</a></div>';
        } else {
            // Tiến hành lưu đặt vé vào DB
            $conn->begin_transaction();
            try {
                // Thêm vào bảng bookings
                $booking_sql = "INSERT INTO bookings (user_id, showtime_id, booking_date, total_amount, seats, status) VALUES (?, ?, NOW(), ?, ?, 'pending')";
                $booking_stmt = $conn->prepare($booking_sql);
                // Join seats array into a string for storing
                $seats_string_db = implode(',', $selected_seats);
                $booking_stmt->bind_param("iids", $user_id, $showtime_id, $total_amount, $seats_string_db);
                
                if (!$booking_stmt->execute()) {
                    throw new Exception("Lỗi khi tạo đặt vé: " . $booking_stmt->error);
                }
                
                $booking_id = $conn->insert_id;
                if (!$booking_id) {
                    throw new Exception("Không thể lấy ID đặt vé");
                }

                // Thêm vào bảng booked_seats
                $booked_seats_insert_sql = "INSERT INTO booked_seats (booking_id, showtime_id, seat_number) VALUES (?, ?, ?)";
                $booked_seats_stmt = $conn->prepare($booked_seats_insert_sql);

                foreach ($selected_seats as $seat_number) {
                    $booked_seats_stmt->bind_param("iis", $booking_id, $showtime_id, $seat_number);
                    if (!$booked_seats_stmt->execute()) {
                        throw new Exception("Lỗi khi đặt ghế: " . $booked_seats_stmt->error);
                    }
                }

                $conn->commit();
                header("Location: payment.php?booking_id=" . $booking_id);
                exit();

            } catch (Exception $e) {
                $conn->rollback();
                $message = '<div class="alert alert-danger">Lỗi khi xử lý đặt vé: ' . $e->getMessage() . '</div>';
            }
        }
    } else {
         $message = '<div class="alert alert-danger">Thông tin đặt vé không hợp lệ.</div>';
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - <?php echo $showtime['movie_title']; ?> - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="footer.css">
    <style>
        body {
            background-color: #000 !important;
            color: #fff;
        }
        
        .navbar {
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(255, 193, 7, 0.1);
        }
        
        .navbar-brand img {
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover img {
            transform: scale(1.05);
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s ease;
        }
        
        .nav-link:hover {
            color: #ffc107 !important;
        }

        .checkout-section {
            padding: 4rem 0;
        }

        .movie-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 193, 7, 0.1);
        }

        .movie-poster {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(255, 193, 7, 0.2);
            transition: transform 0.3s ease;
        }

        .movie-poster:hover {
            transform: scale(1.02);
        }

        .movie-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #ffc107;
        }

        .movie-meta {
            margin-bottom: 1.5rem;
        }

        .movie-meta span {
            display: inline-block;
            margin-right: 1.5rem;
            color: #ffc107;
        }

        .movie-meta i {
            margin-right: 0.5rem;
        }

        .booking-steps {
            margin-bottom: 3rem;
        }

        .step-item {
            text-align: center;
            position: relative;
            padding: 1rem;
        }

        .step-item::after {
            content: '';
            position: absolute;
            top: 50%;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #ffc107;
            z-index: 1;
        }

        .step-item:last-child::after {
            display: none;
        }

        .step-number {
            width: 40px;
            height: 40px;
            background: #ffc107;
            color: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-weight: 600;
            position: relative;
            z-index: 2;
        }

        .step-title {
            color: #ffc107;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .step-description {
            font-size: 0.9rem;
            color: #fff;
            opacity: 0.8;
        }

        .checkout-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 193, 7, 0.1);
            transition: all 0.3s ease;
        }

        .checkout-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.1);
        }

        .checkout-title {
            color: #ffc107;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(255, 193, 7, 0.2);
        }

        .checkout-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .checkout-item:last-child {
            border-bottom: none;
        }

        .checkout-label {
            color: #ffc107;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .checkout-label i {
            margin-right: 0.5rem;
        }

        .checkout-value {
            color: #fff;
            font-weight: 500;
        }

        .checkout-total {
            font-size: 1.2rem;
            color: #ffc107;
            font-weight: 600;
        }

        .btn-checkout {
            background: #ffc107;
            color: #000;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            margin-top: 2rem;
        }

        .btn-checkout:hover {
            background: #ffca2c;
            transform: scale(1.05);
        }

        .form-label {
            color: #ffc107;
            font-weight: 500;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.2);
            color: #fff;
            padding: 0.75rem 1rem;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #ffc107;
            color: #fff;
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-warning sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="CGV Logo" height="60">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
                            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-film me-1"></i>Phim đang chiếu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-calendar me-1"></i>Phim sắp chiếu</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php
                    if(isset($_SESSION['user_id'])) {
                        echo '<li class="nav-item"><a class="nav-link" href="book_ticket.php"><i class="fas fa-ticket-alt me-1"></i>Đặt vé</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Đăng xuất</a></li>';
                    } else {
                        echo '<li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Đăng nhập</a></li>';
                    }
                    ?>
                </ul>
                    </div>
                </div>
    </nav>

    <!-- Checkout Section -->
    <section class="checkout-section">
        <div class="container">
            

            <!-- Booking Steps -->
            <div class="booking-steps">
                <div class="row">
                    <div class="col-md-4">
                        <div class="step-item">
                            <div class="step-number">1</div>
                            <div class="step-title">Chọn suất chiếu</div>
                            <div class="step-description">Chọn ngày và giờ chiếu phù hợp</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="step-item">
                            <div class="step-number">2</div>
                            <div class="step-title">Chọn ghế</div>
                            <div class="step-description">Chọn vị trí ghế mong muốn</div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-title">Thanh toán</div>
                            <div class="step-description">Hoàn tất đặt vé và thanh toán</div>
                        </div>
                    </div>
            </div>
        </div>

            <!-- Checkout Form -->
        <form method="POST" action="">
                <div class="row justify-content-center">
                    <div class="col-md-6">
                        <div class="checkout-card">
                            <h3 class="checkout-title"><i class="fas fa-ticket-alt me-2"></i>Thông tin đặt vé</h3>
                            <div class="checkout-item">
                                <span class="checkout-label"><i class="fas fa-film"></i>Phim</span>
                                <span class="checkout-value"><?php echo $showtime['movie_title']; ?></span>
                            </div>
                            <div class="checkout-item">
                                <span class="checkout-label"><i class="fas fa-clock"></i>Suất chiếu</span>
                                <span class="checkout-value"><?php echo date('d/m/Y H:i', strtotime($showtime['showtime'])); ?></span>
                            </div>
                            <div class="checkout-item">
                                <span class="checkout-label"><i class="fas fa-door-open"></i>Phòng chiếu</span>
                                <span class="checkout-value"><?php echo $showtime['room_name']; ?></span>
                            </div>
                            <div class="checkout-item">
                                <span class="checkout-label"><i class="fas fa-chair"></i>Ghế</span>
                                <span class="checkout-value"><?php echo implode(', ', $selected_seats); ?></span>
                            </div>
                            <div class="checkout-item">
                                <span class="checkout-label"><i class="fas fa-money-bill-wave"></i>Tổng tiền</span>
                                <span class="checkout-value checkout-total"><?php echo number_format($total_amount, 0, ',', '.'); ?> VNĐ</span>
                            </div>
                        </div>

                        <button type="submit" name="confirm_booking" class="btn btn-checkout">
                            Tiếp tục thanh toán <i class="fas fa-arrow-right ms-2"></i>
            </button>
                    </div>
                </div>
        </form>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'?>

    <!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?> 