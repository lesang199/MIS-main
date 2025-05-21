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
$booking_sql = "SELECT b.*, s.showtime, s.price, m.title as movie_title, m.poster, m.duration, r.name as room_name,
                p.payment_method, p.payment_date
                FROM bookings b 
                JOIN showtimes s ON b.showtime_id = s.id 
                JOIN movies m ON s.movie_id = m.id 
                JOIN rooms r ON s.room_id = r.id 
                JOIN payments p ON b.id = p.booking_id
                WHERE b.id = ? AND b.user_id = ? AND (b.status = 'paid' OR b.status = 'confirmed')";
$booking_stmt = $conn->prepare($booking_sql);
$booking_stmt->bind_param("ii", $booking_id, $_SESSION['user_id']);
$booking_stmt->execute();
$booking_result = $booking_stmt->get_result();
$booking = $booking_result->fetch_assoc();

if (!$booking) {
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt vé thành công - <?php echo $booking['movie_title']; ?> - CGV Cinemas</title>
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

        .success-section {
            padding: 4rem 0;
        }

        .success-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 193, 7, 0.1);
            transition: all 0.3s ease;
        }

        .success-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.1);
        }

        .success-icon {
            width: 100px;
            height: 100px;
            background: #ffc107;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 3rem;
            color: #000;
            animation: scaleIn 0.5s ease-out;
        }

        @keyframes scaleIn {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .success-title {
            color: #ffc107;
            font-size: 2rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 1rem;
        }

        .success-message {
            color: #fff;
            text-align: center;
            margin-bottom: 2rem;
            font-size: 1.1rem;
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

        .booking-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 193, 7, 0.1);
        }

        .booking-title {
            color: #ffc107;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(255, 193, 7, 0.2);
        }

        .booking-content {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }

        .booking-details {
            flex: 1;
        }

        .booking-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .booking-item:last-child {
            border-bottom: none;
        }

        .booking-label {
            color: #ffc107;
            font-weight: 500;
            display: flex;
            align-items: center;
        }

        .booking-label i {
            margin-right: 0.5rem;
        }

        .booking-value {
            color: #fff;
            font-weight: 500;
        }

        .qr-section {
            flex: 0 0 300px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
        }

        .qr-title {
            color: #ffc107;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .qr-code {
            background: #fff;
            padding: 1rem;
            border-radius: 10px;
            margin: 0 auto;
            max-width: 200px;
        }

        .qr-code img {
            width: 100%;
            height: auto;
        }

        @media (max-width: 768px) {
            .booking-content {
                flex-direction: column;
            }
            
            .qr-section {
                flex: 0 0 auto;
                width: 100%;
                margin-top: 2rem;
            }
        }

        .btn-home {
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

        .btn-home:hover {
            background: #ffca2c;
            transform: scale(1.05);
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

    <!-- Success Section -->
    <section class="success-section">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="success-card">
                        <div class="success-icon">
                            <i class="fas fa-check"></i>
                        </div>
                        <h1 class="success-title">Đặt vé thành công!</h1>
                        <p class="success-message">Cảm ơn bạn đã đặt vé. Vui lòng đến rạp trước giờ chiếu 30 phút để nhận vé.</p>
                    </div>

                    
                    <!-- Booking Info -->
                    <div class="booking-info">
                        <h3 class="booking-title"><i class="fas fa-ticket-alt me-2"></i>Thông tin đặt vé</h3>
                        <div class="booking-content">
                            <div class="booking-details">
                                <div class="booking-item">
                                    <span class="booking-label"><i class="fas fa-film"></i>Phim</span>
                                    <span class="booking-value"><?php echo $booking['movie_title']; ?></span>
                                </div>
                                <div class="booking-item">
                                    <span class="booking-label"><i class="fas fa-clock"></i>Suất chiếu</span>
                                    <span class="booking-value"><?php echo date('d/m/Y H:i', strtotime($booking['showtime'])); ?></span>
                                </div>
                                <div class="booking-item">
                                    <span class="booking-label"><i class="fas fa-door-open"></i>Phòng chiếu</span>
                                    <span class="booking-value"><?php echo $booking['room_name']; ?></span>
                                </div>
                                <div class="booking-item">
                                    <span class="booking-label"><i class="fas fa-chair"></i>Ghế</span>
                                    <span class="booking-value"><?php echo $booking['seats']; ?></span>
                                </div>
                                <div class="booking-item">
                                    <span class="booking-label"><i class="fas fa-money-bill-wave"></i>Tổng tiền</span>
                                    <span class="booking-value"><?php echo number_format($booking['total_amount'], 0, ',', '.'); ?> VNĐ</span>
                                </div>
                                <div class="booking-item">
                                    <span class="booking-label"><i class="fas fa-credit-card"></i>Phương thức thanh toán</span>
                                    <span class="booking-value"><?php 
                                        switch($booking['payment_method']) {
                                            case 'momo':
                                                echo 'MoMo';
                                                break;
                                            case 'zalopay':
                                                echo 'ZaloPay';
                                                break;
                                            case 'banking':
                                                echo 'Chuyển khoản ngân hàng';
                                                break;
                                        }
                                    ?></span>
                                </div>
                                <div class="booking-item">
                                    <span class="booking-label"><i class="fas fa-calendar-check"></i>Ngày thanh toán</span>
                                    <span class="booking-value"><?php echo date('d/m/Y H:i', strtotime($booking['payment_date'])); ?></span>
                                </div>
                            </div>
                            <div class="qr-section">
                                <h4 class="qr-title">Mã QR đặt vé</h4>
                                <div class="qr-code">
                                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?php echo urlencode('Booking ID: ' . $booking_id); ?>" alt="QR Code">
                                </div>
                                
                                
                            </div>
                        </div>
                    </div>

                    <a href="index.php" class="btn btn-home">
                        Về trang chủ <i class="fas fa-home ms-2"></i>
                    </a>
                </div>
            </div>
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