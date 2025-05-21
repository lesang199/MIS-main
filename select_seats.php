<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['showtime_id'])) {
    header("Location: index.php");
    exit();
}

$showtime_id = $_GET['showtime_id'];

// Lấy thông tin suất chiếu và phim
$showtime_sql = "SELECT s.*, m.title, m.poster, m.duration, r.name as room_name 
            FROM showtimes s
            JOIN movies m ON s.movie_id = m.id
            JOIN rooms r ON s.room_id = r.id
            WHERE s.id = ?";
$showtime_stmt = $conn->prepare($showtime_sql);
$showtime_stmt->bind_param("i", $showtime_id);
$showtime_stmt->execute();
$showtime_result = $showtime_stmt->get_result();
$showtime = $showtime_result->fetch_assoc();

if (!$showtime) {
    header("Location: index.php");
    exit();
}

// Lấy danh sách ghế đã đặt
        $booked_seats_sql = "SELECT seat_number FROM booked_seats WHERE showtime_id = ?";
        $booked_seats_stmt = $conn->prepare($booked_seats_sql);
        $booked_seats_stmt->bind_param("i", $showtime_id);
        $booked_seats_stmt->execute();
        $booked_seats_result = $booked_seats_stmt->get_result();
$booked_seats = [];
        while ($row = $booked_seats_result->fetch_assoc()) {
            $booked_seats[] = $row['seat_number'];
}

// Cấu hình ghế mặc định
$seat_rows = 8; // Số hàng ghế
$seat_cols = 10; // Số cột ghế
$total_seats = $seat_rows * $seat_cols; // Tổng số ghế
$alphabet = range('A', 'Z');

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chọn ghế - <?php echo $showtime['title']; ?> - CGV Cinemas</title>
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

        .booking-section {
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

        .screen-container {
            position: relative;
            margin-bottom: 3rem;
        }

        .screen {
            background: linear-gradient(to bottom, #ffc107, #ff9800);
            height: 70px;
            width: 80%;
            margin: 0 auto 2rem;
            border-radius: 50% 50% 0 0;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 0 20px rgba(255, 193, 7, 0.3);
            position: relative;
            overflow: hidden;
        }

        .screen::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 100%);
            pointer-events: none;
        }

        .screen::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 100%;
            height: 20px;
            background: rgba(0,0,0,0.3);
            border-radius: 50%;
            filter: blur(10px);
        }

        .screen-text {
            color: #000;
            font-weight: 600;
            font-size: 1.2rem;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .center-position {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .center-line {
            width: 2px;
            height: 100px;
            background: rgba(255, 193, 7, 0.5);
            position: absolute;
            top: -100px;
        }

        .center-text {
            color: #ffc107;
            font-size: 0.9rem;
            font-weight: 500;
            background: rgba(0, 0, 0, 0.7);
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            white-space: nowrap;
        }

        .seat-info {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .seat-type {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .seat-sample {
            width: 25px;
            height: 25px;
            border-radius: 5px;
        }

        .seat-sample.available {
            background: #28a745;
        }

        .seat-sample.selected {
            background: #ffc107;
        }

        .seat-sample.occupied {
            background: #dc3545;
        }

        .seat-type span {
            color: #fff;
            font-size: 0.9rem;
        }

        .seats-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(255, 193, 7, 0.1);
        }

        .seat-row {
            display: flex;
            justify-content: center;
            margin-bottom: 1rem;
        }

        .seat {
            width: 40px;
            height: 40px;
            margin: 0 5px;
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 193, 7, 0.2);
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.8rem;
            color: #fff;
        }

        .seat:hover {
            background: rgba(255, 193, 7, 0.2);
            transform: scale(1.1);
        }

        .seat.selected {
            background: #ffc107;
            color: #000;
            border-color: #ffc107;
        }

        .seat.booked {
            background: rgba(255, 0, 0, 0.2);
            border-color: rgba(255, 0, 0, 0.3);
            cursor: not-allowed;
        }

        .seat-legend {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin: 2rem 0;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .legend-box {
            width: 20px;
            height: 20px;
            border-radius: 3px;
        }

        .legend-box.available {
            background: rgba(255, 255, 255, 0.1);
            border: 2px solid rgba(255, 193, 7, 0.2);
        }

        .legend-box.selected {
            background: #ffc107;
            border: 2px solid #ffc107;
        }

        .legend-box.booked {
            background: rgba(255, 0, 0, 0.2);
            border: 2px solid rgba(255, 0, 0, 0.3);
        }

        .booking-summary {
            margin-top: 2rem;
        }
        
        .summary-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1.5rem;
            height: 100%;
            border: 1px solid rgba(255, 193, 7, 0.1);
            transition: all 0.3s ease;
        }
        
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.1);
        }
        
        .summary-title {
            color: #ffc107;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(255, 193, 7, 0.2);
        }
        
        .summary-content {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .summary-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
        }
        
        .summary-label {
            color: #ffc107;
            font-weight: 500;
            display: flex;
            align-items: center;
        }
        
        .summary-value {
            color: #fff;
            font-weight: 500;
        }
        
        .summary-value#selectedSeats {
            color: #ffc107;
            font-weight: 600;
        }
        
        .summary-value#totalPrice {
            color: #ffc107;
            font-weight: 600;
            font-size: 1.2rem;
        }

        .btn-continue {
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

        .btn-continue:hover {
            background: #ffca2c;
            transform: scale(1.05);
        }

        .btn-continue:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
        }

        .center-seat {
            position: relative;
            background: rgba(255, 193, 7, 0.1) !important;
            border: 2px solid #ffc107 !important;
        }

        .center-seat::after {
            content: '';
            position: absolute;
            top: -5px;
            left: -5px;
            right: -5px;
            bottom: -5px;
            border: 2px solid rgba(255, 193, 7, 0.3);
            border-radius: 7px;
            pointer-events: none;
        }

        .center-seat:hover {
            background: rgba(255, 193, 7, 0.2) !important;
        }

        .center-seat.selected {
            background: #ffc107 !important;
            color: #000;
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

    <!-- Booking Section -->
    <section class="booking-section">
        <div class="container">
            <!-- Movie Info -->
            

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

            <!-- Screen and Seats -->
            <div class="seats-container">
                <div class="screen-container">
                    <div class="screen">
                        <div class="screen-text">Màn hình</div>
                    </div>
               
                </div>

                <div class="seat-legend">
                    <div class="legend-item">
                        <div class="legend-box available"></div>
                        <span>Có thể chọn</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box selected"></div>
                        <span>Đã chọn</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-box booked"></div>
                        <span>Đã đặt</span>
                    </div>
                </div>

                <div class="seats-grid">
                    <?php
                    $rows = ceil($total_seats / 10);
                    $alphabet = range('A', 'Z');
                    for ($i = 0; $i < $rows; $i++) {
                        echo '<div class="seat-row">';
                        for ($j = 1; $j <= 10; $j++) {
                            $seat_number = $alphabet[$i] . $j;
                            if (($i * 10 + $j) <= $total_seats) {
                                $is_booked = in_array($seat_number, $booked_seats);
                                $class = $is_booked ? 'booked' : '';
                                echo '<div class="seat ' . $class . '" data-seat="' . $seat_number . '">' . $seat_number . '</div>';
                            }
                        }
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>

            <!-- Booking Summary -->
            <div class="booking-summary">
                <div class="row">
                    <div class="col-md-6">
                        <div class="summary-card">
                            <h4 class="summary-title"><i class="fas fa-info-circle me-2"></i>Thông tin đặt vé</h4>
                            <div class="summary-content">
                                <div class="summary-item">
                                    <span class="summary-label"><i class="fas fa-film me-2"></i>Phim:</span>
                                    <span class="summary-value"><?php echo $showtime['title']; ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label"><i class="fas fa-clock me-2"></i>Suất chiếu:</span>
                                    <span class="summary-value"><?php echo date('d/m/Y H:i', strtotime($showtime['showtime'])); ?></span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label"><i class="fas fa-door-open me-2"></i>Phòng chiếu:</span>
                                    <span class="summary-value"><?php echo $showtime['room_name']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="summary-card">
                            <h4 class="summary-title"><i class="fas fa-ticket-alt me-2"></i>Ghế đã chọn</h4>
                            <div class="summary-content">
                                <div class="summary-item">
                                    <span class="summary-label"><i class="fas fa-chair me-2"></i>Vị trí:</span>
                                    <span class="summary-value" id="selectedSeats">Chưa chọn</span>
                                </div>
                                <div class="summary-item">
                                    <span class="summary-label"><i class="fas fa-money-bill-wave me-2"></i>Tổng tiền:</span>
                                    <span class="summary-value" id="totalPrice">0 VNĐ</span>
                                </div>
            </div>
            </div>
        </div>
    </div>
</div>

            <button class="btn btn-continue" id="continueBtn" disabled>
                Tiếp tục <i class="fas fa-arrow-right ms-2"></i>
            </button>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'?>

    <!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const seats = document.querySelectorAll('.seat:not(.booked)');
            const continueBtn = document.getElementById('continueBtn');
            const selectedSeatsElement = document.getElementById('selectedSeats');
            const totalPriceElement = document.getElementById('totalPrice');
            const pricePerSeat = <?php echo $showtime['price']; ?>;
            let selectedSeats = [];

        seats.forEach(seat => {
            seat.addEventListener('click', function() {
                    const seatNumber = this.dataset.seat;
                    const index = selectedSeats.indexOf(seatNumber);

                    if (index === -1) {
                        // Add seat
                        selectedSeats.push(seatNumber);
                        this.classList.add('selected');
                    } else {
                        // Remove seat
                        selectedSeats.splice(index, 1);
                    this.classList.remove('selected');
                }

                    // Update summary
                updateSummary();
            });
        });

        function updateSummary() {
                if (selectedSeats.length > 0) {
                    selectedSeatsElement.textContent = selectedSeats.join(', ');
                    const totalPrice = selectedSeats.length * pricePerSeat;
                    totalPriceElement.textContent = totalPrice.toLocaleString('vi-VN') + ' VNĐ';
                    continueBtn.disabled = false;
            } else {
                    selectedSeatsElement.textContent = 'Chưa chọn';
                    totalPriceElement.textContent = '0 VNĐ';
                    continueBtn.disabled = true;
                }
            }

            continueBtn.addEventListener('click', function() {
                if (selectedSeats.length > 0) {
                    const seatsParam = selectedSeats.join(',');
                    window.location.href = 'checkout.php?showtime_id=<?php echo $showtime_id; ?>&seats=' + seatsParam;
                }
            });
    });
</script>
</body>
</html>

<?php
$conn->close();
?>