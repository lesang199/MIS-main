<?php
include 'db.php';
session_start();

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$message = '';

// Xử lý form submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $movie_id = $_POST['movie_id'];
    $showtime = $_POST['showtime'];
    $room_id = $_POST['room_id'];
    $price = $_POST['price'];

    // Kiểm tra xem suất chiếu đã tồn tại chưa
    $check_sql = "SELECT * FROM showtimes WHERE movie_id = ? AND showtime = ? AND room_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("isi", $movie_id, $showtime, $room_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        $message = '<div class="alert alert-danger">Suất chiếu này đã tồn tại!</div>';
    } else {
        // Thêm suất chiếu mới
        $sql = "INSERT INTO showtimes (movie_id, showtime, room_id, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isid", $movie_id, $showtime, $room_id, $price);

        if ($stmt->execute()) {
            $message = '<div class="alert alert-success">Thêm suất chiếu thành công!</div>';
        } else {
            $message = '<div class="alert alert-danger">Lỗi: ' . $stmt->error . '</div>';
        }
    }
}

// Lấy danh sách phim
$movies_sql = "SELECT id, title FROM movies WHERE status = 'now_showing'";
$movies_result = $conn->query($movies_sql);

// Lấy danh sách phòng chiếu
$rooms_sql = "SELECT id, name FROM rooms";
$rooms_result = $conn->query($rooms_sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thêm Suất Chiếu - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>
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
        .movie-poster {
            max-width: 200px;
            height: auto;
        }
    </style>
<body>
   <!-- Sidebar -->
   <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-white text-center mb-4">CGV Admin</h3>
                <nav>
                    <a href="admin_dashboard.php"><i class="fas fa-home"></i> Trang chủ</a>
                    <a href="admin_movies.php"><i class="fas fa-film"></i> Quản lý phim</a>
                    <a href="admin_showtimes.php"><i class="fas fa-clock"></i> Quản lý suất chiếu</a>
                    <a href="admin_bookings.php"><i class="fas fa-ticket-alt"></i> Quản lý đặt vé</a>
                    <a href="admin_users.php"><i class="fas fa-users"></i> Quản lý người dùng</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </nav>
            </div>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Thêm Suất Chiếu Mới</h3>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="movie_id" class="form-label">Chọn Phim</label>
                                <select class="form-select" id="movie_id" name="movie_id" required>
                                    <option value="">Chọn phim...</option>
                                    <?php
                                    if ($movies_result->num_rows > 0) {
                                        while($movie = $movies_result->fetch_assoc()) {
                                            echo '<option value="' . $movie['id'] . '">' . $movie['title'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="showtime" class="form-label">Thời gian chiếu</label>
                                <input type="datetime-local" class="form-control" id="showtime" name="showtime" required>
                            </div>

                            <div class="mb-3">
                                <label for="room_id" class="form-label">Chọn Phòng Chiếu</label>
                                <select class="form-select" id="room_id" name="room_id" required>
                                    <option value="">Chọn phòng chiếu...</option>
                                    <?php
                                    if ($rooms_result->num_rows > 0) {
                                        while($room = $rooms_result->fetch_assoc()) {
                                            echo '<option value="' . $room['id'] . '">' . $room['name'] . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label">Giá vé (VNĐ)</label>
                                <input type="number" class="form-control" id="price" name="price" required min="0">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-warning">Thêm Suất Chiếu</button>
                                <a href="admin.php" class="btn btn-secondary">Quay lại</a>
                            </div>
                        </form>
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