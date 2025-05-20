<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id']) || !isset($_GET['booking_id'])) {
    header('Location: index.php');
    exit;
}

$booking_id = $_GET['booking_id'];
$user_id = $_SESSION['user_id'];

// Get booking details
$sql = "SELECT b.*, m.title as movie_title, s.showtime,u.full_name as user_name,r.name as room_name
        FROM bookings b
        JOIN showtimes s ON b.showtime_id = s.id
        JOIN movies m ON s.movie_id = m.id
        JOIN users u ON b.user_id = u.id
        JOiN rooms r ON s.room_id = r.id
        WHERE b.id = ? AND b.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $booking_id, $user_id);
$stmt->execute();
$booking = $stmt->get_result()->fetch_assoc();

if (!$booking) {
    header('Location: index.php');
    exit;
}

// Get booked seats
$sql = "SELECT seat_number FROM booked_seats WHERE booking_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $booking_id);
$stmt->execute();
$seats_result = $stmt->get_result();
$booked_seats = [];
while ($row = $seats_result->fetch_assoc()) {
    $booked_seats[] = $row['seat_number'];
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt vé thành công - CGV Cinemas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="footer.css">
    <style>
     .qr-code {
         display: flex;
         justify-content: center;
         align-items: center;
        }
    h3{
        text-align: center;
        margin-top: 20px;
    }
    </style>

</head>
<body>
    <!-- Navigation -->
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
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Đăng xuất</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Success Message -->
    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle text-success" style="font-size: 4rem;"></i>
                        <h2 class="mt-3">Đặt vé thành công!</h2>
                        <p class="lead">Cảm ơn bạn đã đặt vé tại CGV Cinemas</p>
                    </div>
                </div>

                <!-- Booking Details -->
                <!-- <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Chi tiết đặt vé</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Mã đặt vé:</strong> #<?php echo str_pad($booking_id, 6, '0', STR_PAD_LEFT); ?></p>
                                <p><strong>Phim:</strong> <?php echo $booking['movie_title']; ?></p>
                                <p><strong>Suất chiếu:</strong> <?php echo date('d/m/Y', strtotime($booking['showtime'])); ?> - 
                                                             <?php echo date('H:i', strtotime($booking['showtime'])); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Ghế:</strong> <?php echo implode(', ', $booked_seats); ?></p>
                                <p><strong>Tổng tiền:</strong> <?php echo number_format($booking['total_amount'], 0, ',', '.'); ?> VNĐ</p>
                                <p><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($booking['booking_date'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div> -->
                <!-- QR Code -->
   <div class="card mt-4">
        <h3>🎟️ Mã QR của bạn:</h3>
       <div class="container text-center mb-5 qr-code">
        <?php
         require_once './QR/phpqrcode/qrlib.php'; // Nhúng thư viện QR
    
        // Tạo mã vé (code) từ booking_id
        $code = str_pad($booking_id, 6, '0', STR_PAD_LEFT);
    
        // Lấy tên phòng chiếu (nếu có trong truy vấn SQL, bạn cần JOIN với bảng rooms)
        $room = isset($booking['room_name']) ? $booking['room_name'] : '';
    
        // Lấy tên người đặt
        $name = isset($booking['user_name']) ? $booking['user_name'] : '';
    
       // Nội dung mã QR (dưới dạng JSON)
        $qrContent = json_encode([
        'Mã vé'    => $code,
        'Tên phim' => $booking['movie_title'],
        'Phòng'    => $room,
        'Thời gian'=> date('d/m/Y H:i', strtotime($booking['showtime'])),
        'Ghế'      => implode(', ', $booked_seats),
        'Người đặt'=> $name
       ], JSON_UNESCAPED_UNICODE); // Dùng để hiện tiếng Việt
    
        // Tạo thư mục nếu chưa có
        $qrDir = './QR/qr_codes/';
        if (!file_exists($qrDir)) {
        mkdir($qrDir, 0777, true);
       }
    
       // Tạo file ảnh QR
       $qrFile = $qrDir . 'QR_' . $code . '.png';
       QRcode::png($qrContent, $qrFile, QR_ECLEVEL_L, 5); // Kích thước = 5
       ?>
    
       <!-- Hiển thị mã QR -->
       <img src="<?php echo $qrFile; ?>" alt="Mã QR vé" class="img-fluid mt-3">
       <!-- Hiển thị thông tin vé -->
       <div class="mt-4 text-start d-inline-block text-left">
        <p><strong>👤 Người đặt:</strong> <?= $name ?></p>
        <p><strong>🎬 Tên phim:</strong> <?= $booking['movie_title'] ?></p>
        <p><strong>🪑 Ghế:</strong> <?= implode(', ', $booked_seats) ?></p>
        <p><strong>🕒 Thời gian:</strong> <?= date('d/m/Y H:i', strtotime($booking['showtime'])) ?></p>
        <p><strong>🏢 Phòng chiếu:</strong> <?= $room ?></p>
        <p><strong>🔖 Mã vé:</strong> <?= $code ?></p>
        </div>
      </div>
   </div>

                <!-- Instructions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0">Hướng dẫn</h5>
                    </div>
                    <div class="card-body">
                        <ol>
                            <li>Vui lòng đến rạp trước giờ chiếu ít nhất 30 phút</li>
                            <li>Mang theo mã đặt vé và giấy tờ tùy thân khi đến rạp</li>
                            <li>Vé đã đặt không thể hủy hoặc hoàn tiền</li>
                        </ol>
                    </div>
                </div>
                 
                <div class="text-center mt-4">
                    <a href="index.php" class="btn btn-primary">Về trang chủ</a>
                </div>
            </div>
        </div>
    </div>
   
    <!-- Footer -->
    <?php include 'footer.php'?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 