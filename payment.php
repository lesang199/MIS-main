<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['booking_id'])) {
    header("Location: index.php");
    exit();
}

$booking_id = $_GET['booking_id'];

// Lấy thông tin đặt vé
$booking_sql = "SELECT b.*, s.showtime, s.price, m.title as movie_title, m.poster, m.duration, r.name as room_name 
                FROM bookings b 
                JOIN showtimes s ON b.showtime_id = s.id 
                JOIN movies m ON s.movie_id = m.id 
                JOIN rooms r ON s.room_id = r.id 
                WHERE b.id = ? AND b.user_id = ?";
$booking_stmt = $conn->prepare($booking_sql);
$booking_stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$booking = $booking_result->fetch_assoc();

if (!$booking) {
    header("Location: index.php");
    exit();
}

// Xử lý thanh toán
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $payment_date = date('Y-m-d H:i:s');
    $user_id = $_SESSION['user_id'];
    
    // Kiểm tra booking có tồn tại và chưa thanh toán
    $check_sql = "SELECT * FROM bookings WHERE id = ? AND user_id = ? AND status = 'pending'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $booking_id, $user_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows === 0) {
        header("Location: index.php");
        exit();
    }
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Cập nhật trạng thái đặt vé
        $update_sql = "UPDATE bookings SET status = 'confirmed' WHERE id = ? AND user_id = ? AND status = 'pending'";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ii", $booking_id, $user_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Lỗi khi cập nhật trạng thái đặt vé");
        }
        
        // Thêm thông tin thanh toán
        $payment_sql = "INSERT INTO payments (booking_id, user_id, amount, payment_method, payment_date, status) VALUES (?, ?, ?, ?, ?, 'completed')";
        $payment_stmt = $conn->prepare($payment_sql);
        $payment_stmt->bind_param("iidss", $booking_id, $user_id, $booking['total_amount'], $payment_method, $payment_date);
        
        if (!$payment_stmt->execute()) {
            throw new Exception("Lỗi khi thêm thông tin thanh toán");
        }
        
        // Commit transaction
        $conn->commit();
        
        // Chuyển hướng đến trang thành công
        header("Location: booking_success.php?booking_id=" . $booking_id);
        exit();
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
        $error = "Có lỗi xảy ra: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - <?php echo $booking['movie_title']; ?> - CGV Cinemas</title>
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

        .payment-section {
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

        .payment-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 193, 7, 0.1);
            transition: all 0.3s ease;
        }

        .payment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.1);
        }

        .payment-title {
            color: #ffc107;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(255, 193, 7, 0.2);
        }

        .payment-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .payment-item:last-child {
            border-bottom: none;
        }

        .payment-label {
            color: #ffc107;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .payment-label i {
            margin-right: 0.5rem;
        }

        .payment-value {
            color: #fff;
            font-weight: 500;
        }

        .payment-total {
            font-size: 1.2rem;
            color: #ffc107;
            font-weight: 600;
        }

        .payment-method {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 193, 7, 0.2);
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .payment-method:hover {
            background: rgba(255, 193, 7, 0.1);
            border-color: #ffc107;
        }

        .payment-method.selected {
            background: rgba(255, 193, 7, 0.1);
            border-color: #ffc107;
        }

        .payment-method-icon {
            font-size: 2rem;
            color: #ffc107;
            margin-bottom: 1rem;
        }

        .payment-method-title {
            color: #ffc107;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .payment-method-description {
            color: #fff;
            opacity: 0.8;
            font-size: 0.9rem;
        }

        .btn-payment {
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

        .btn-payment:hover {
            background: #ffca2c;
            transform: scale(1.05);
        }

        .btn-payment:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
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

    <!-- Payment Section -->
    <section class="payment-section">
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

            <!-- Payment Form -->
            <form method="POST" action="">
                <div class="row justify-content-center">
                    <div class="col-md-8">
                        <div class="payment-card">
                            <h3 class="payment-title"><i class="fas fa-ticket-alt me-2"></i>Thông tin đặt vé</h3>
                            <div class="payment-item">
                                <span class="payment-label"><i class="fas fa-film"></i>Phim</span>
                                <span class="payment-value"><?php echo $booking['movie_title']; ?></span>
                            </div>
                            <div class="payment-item">
                                <span class="payment-label"><i class="fas fa-clock"></i>Suất chiếu</span>
                                <span class="payment-value"><?php echo date('d/m/Y H:i', strtotime($booking['showtime'])); ?></span>
                            </div>
                            <div class="payment-item">
                                <span class="payment-label"><i class="fas fa-door-open"></i>Phòng chiếu</span>
                                <span class="payment-value"><?php echo $booking['room_name']; ?></span>
                            </div>
                            <div class="payment-item">
                                <span class="payment-label"><i class="fas fa-chair"></i>Ghế</span>
                                <span class="payment-value"><?php echo $booking['seats']; ?></span>
                            </div>
                            <div class="payment-item">
                                <span class="payment-label"><i class="fas fa-money-bill-wave"></i>Tổng tiền</span>
                                <span class="payment-value payment-total"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?> VNĐ</span>
                            </div>
                        </div>

                        <div class="payment-card">
                            <h3 class="payment-title"><i class="fas fa-credit-card me-2"></i>Phương thức thanh toán</h3>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label class="payment-method" onclick="selectPaymentMethod('momo')">
                                        <input type="radio" name="payment_method" value="momo" class="d-none">
                                        <div class="payment-method-icon">
                                            <i class="fas fa-mobile-alt"></i>
                                        </div>
                                        <div class="payment-method-title">MoMo</div>
                                        <div class="payment-method-description">Thanh toán qua ví MoMo</div>
                                    </label>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="payment-method" onclick="selectPaymentMethod('zalopay')">
                                        <input type="radio" name="payment_method" value="zalopay" class="d-none">
                                        <div class="payment-method-icon">
                                            <i class="fas fa-wallet"></i>
                                        </div>
                                        <div class="payment-method-title">ZaloPay</div>
                                        <div class="payment-method-description">Thanh toán qua ZaloPay</div>
                                    </label>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="payment-method" onclick="selectPaymentMethod('banking')">
                                        <input type="radio" name="payment_method" value="banking" class="d-none">
                                        <div class="payment-method-icon">
                                            <i class="fas fa-university"></i>
                                        </div>
                                        <div class="payment-method-title">Chuyển khoản</div>
                                        <div class="payment-method-description">Chuyển khoản ngân hàng</div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-payment" id="paymentBtn" disabled>
                            Thanh toán <i class="fas fa-arrow-right ms-2"></i>
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
    <script>
        function selectPaymentMethod(method) {
            // Remove selected class from all payment methods
            document.querySelectorAll('.payment-method').forEach(el => {
                el.classList.remove('selected');
            });
            
            // Add selected class to clicked payment method
            event.currentTarget.classList.add('selected');
            
            // Enable payment button
            document.getElementById('paymentBtn').disabled = false;
        }
    </script>
</body>
</html>

<?php
$conn->close();
?> 