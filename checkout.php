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
    <title>Xác nhận Đặt Vé - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #1a1a1a;
            color: #fff;
            font-family: 'Arial', sans-serif;
            padding-bottom: 50px; /* Add padding to the bottom for the fixed summary */
        }
        .container {
            margin-top: 30px;
        }
        h2, h3, h4 {
            color: #ffc107; /* Warning color */
            margin-bottom: 20px;
        }
        .summary-card {
            background-color: #333;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .summary-card p {
            margin-bottom: 10px;
            font-size: 1.1em;
        }
         .summary-card p strong {
             color: #fff;
         }
        .btn-confirm {
            background-color: #28a745; /* Success color */
            border-color: #28a745;
            color: #fff;
            font-size: 1.2rem;
            padding: 10px 20px;
            width: 100%;
        }
         .btn-confirm:hover {
              background-color: #218838;
              border-color: #1e7e34;
              color: #fff;
          }
    </style>
</head>
<body>

<div class="container">
    <h2>Xác nhận Đặt Vé</h2>

    <?php echo $message; ?>

    <?php if ($showtime && !empty($selected_seats)): ?>
        <div class="summary-card">
            <h4>Thông tin đặt vé</h4>
            <p><strong>Phim:</strong> <?php echo htmlspecialchars($showtime['movie_title']); ?></p>
            <p><strong>Rạp:</strong> <?php echo htmlspecialchars($showtime['room_name']); ?></p>
            <p><strong>Thời gian:</strong> <?php echo date('d/m/Y H:i', strtotime($showtime['showtime'])); ?></p>
            <p><strong>Ghế đã chọn:</strong> <?php echo htmlspecialchars(implode(', ', $selected_seats)); ?></p>
            <p><strong>Số lượng ghế:</strong> <?php echo count($selected_seats); ?></p>
            <p><strong>Giá mỗi ghế:</strong> <?php echo number_format($showtime['price'], 0, ',', '.') . ' VNĐ'; ?></p>
            <p><strong>Tổng tiền:</strong> <?php echo number_format($total_amount, 0, ',', '.') . ' VNĐ'; ?></p>
        </div>

        <form method="POST" action="">
            <button type="submit" name="confirm_booking" class="btn btn-confirm">Xác nhận và Thanh toán</button>
        </form>

    <?php elseif (!$message): ?>
         <div class="alert alert-info">Vui lòng chọn phim, suất chiếu và ghế để đặt vé.</div>
    <?php endif; ?>

    <div class="mt-4">
        <a href="select_seats.php?showtime_id=<?php echo isset($_GET['showtime_id']) ? $_GET['showtime_id']: ''; ?>" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại chọn ghế</a>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?> 