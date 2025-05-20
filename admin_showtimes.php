<?php
include 'db.php';
session_start();

// Kiểm tra đăng nhập admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit();
}

$message = '';

// Xử lý thông báo từ việc xóa suất chiếu
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'deleted':
            $message = '<div class="alert alert-success">Xóa suất chiếu thành công!</div>';
            break;
        case 'not_found':
            $message = '<div class="alert alert-danger">Không tìm thấy suất chiếu!</div>';
            break;
        case 'has_bookings':
            $message = '<div class="alert alert-danger">Không thể xóa suất chiếu này vì đã có người đặt vé!</div>';
            break;
        case 'error':
            $error_msg = isset($_GET['error_msg']) ? $_GET['error_msg'] : 'Có lỗi xảy ra!';
            $message = '<div class="alert alert-danger">' . htmlspecialchars($error_msg) . '</div>';
            break;
    }
}

// Xử lý form submit
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['movie_id']) && isset($_POST['room_id']) && isset($_POST['showtime']) && isset($_POST['price'])) {
    $movie_id = $_POST['movie_id'];
    $room_id = $_POST['room_id'];
    $showtime = $_POST['showtime'];
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
        $sql = "INSERT INTO showtimes (movie_id, room_id, showtime, price) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisd", $movie_id, $room_id, $showtime, $price);

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

// Lấy danh sách suất chiếu
$showtimes_sql = "SELECT s.*, m.title as movie_title, r.name as room_name 
                 FROM showtimes s 
                 JOIN movies m ON s.movie_id = m.id 
                 JOIN rooms r ON s.room_id = r.id 
                 ORDER BY s.showtime DESC";
$showtimes_result = $conn->query($showtimes_sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Suất Chiếu - CGV Cinemas</title>
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
                    <a href="admin_showtimes.php" class="active"><i class="fas fa-clock"></i> Quản lý suất chiếu</a>
                    <a href="admin_bookings.php"><i class="fas fa-ticket-alt"></i> Quản lý đặt vé</a>
                    <a href="admin_users.php"><i class="fas fa-users"></i> Quản lý người dùng</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Quản lý Suất Chiếu</h2>
                </div>

                <?php echo $message; ?>

                <div class="row">
                    <!-- Form thêm suất chiếu -->
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="mb-0">Thêm Suất Chiếu</h3>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                                    <div class="mb-3">
                                        <label for="movie_id" class="form-label">Chọn Phim</label>
                                        <select class="form-select" id="movie_id" name="movie_id" required>
                                            <option value="">Chọn phim...</option>
                                            <?php
                                            if ($movies_result && $movies_result->num_rows > 0) {
                                                while($movie = $movies_result->fetch_assoc()) {
                                                    echo '<option value="' . $movie['id'] . '">' . $movie['title'] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="room_id" class="form-label">Chọn Phòng Chiếu</label>
                                        <select class="form-select" id="room_id" name="room_id" required>
                                            <option value="">Chọn phòng chiếu...</option>
                                            <?php
                                            if ($rooms_result && $rooms_result->num_rows > 0) {
                                                while($room = $rooms_result->fetch_assoc()) {
                                                    echo '<option value="' . $room['id'] . '">' . $room['name'] . '</option>';
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
                                        <label for="price" class="form-label">Giá vé (VNĐ)</label>
                                        <input type="number" class="form-control" id="price" name="price" required min="0">
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-warning">Thêm Suất Chiếu</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Danh sách suất chiếu -->
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="mb-0">Danh Sách Suất Chiếu</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Phim</th>
                                                <th>Phòng</th>
                                                <th>Thời gian</th>
                                                <th>Giá vé</th>
                                                <th>Thao tác</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($showtimes_result && $showtimes_result->num_rows > 0) {
                                                while($showtime = $showtimes_result->fetch_assoc()) {
                                                    echo '<tr>';
                                                    echo '<td>' . $showtime['movie_title'] . '</td>';
                                                    echo '<td>' . $showtime['room_name'] . '</td>';
                                                    echo '<td>' . date('d/m/Y H:i', strtotime($showtime['showtime'])) . '</td>';
                                                    echo '<td>' . number_format($showtime['price'], 0, ',', '.') . ' VNĐ</td>';
                                                    echo '<td>
                                                            <a href="admin_edit_showtime.php?id=' . $showtime['id'] . '" class="btn btn-sm btn-primary">
                                                                <i class="fas fa-edit"></i>
                                                            </a>
                                                            <a href="admin_delete_showtime.php?id=' . $showtime['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Bạn có chắc chắn muốn xóa suất chiếu này?\')">
                                                                <i class="fas fa-trash"></i>
                                                            </a>
                                                          </td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr><td colspan="5" class="text-center">Không có suất chiếu nào</td></tr>';
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
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?> 