<?php
session_start();
require_once 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Lấy thông tin admin
$admin_id = $_SESSION['admin_id'];
$admin_username = $_SESSION['admin_username'];

// Xử lý thêm suất chiếu mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_showtime'])) {
    $movie_id = $_POST['movie_id'];
    $room_id = $_POST['room_id'];
    $showtime = $_POST['showtime'];
    $price = $_POST['price'];
    
    $sql = "INSERT INTO showtimes (movie_id, room_id, showtime, price) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iisd", $movie_id, $room_id, $showtime, $price);
    
    if ($stmt->execute()) {
        $success_message = "Thêm suất chiếu thành công!";
    } else {
        $error_message = "Lỗi: " . $conn->error;
    }
}

// Lấy danh sách phim
$movies = $conn->query("SELECT id, title FROM movies WHERE status = 'now_showing'");

// Lấy danh sách phòng chiếu
$rooms = $conn->query("SELECT id, name FROM rooms");

// Lấy danh sách suất chiếu
$showtimes = $conn->query("
    SELECT s.*, m.title as movie_title, r.name as room_name 
    FROM showtimes s 
    JOIN movies m ON s.movie_id = m.id 
    JOIN rooms r ON s.room_id = r.id 
    ORDER BY s.showtime DESC
");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý suất chiếu - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="admin_style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">CGV Admin</h3>
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
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="page-title">Quản lý suất chiếu</h2>
                        <div class="admin-info">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php echo htmlspecialchars($admin_username); ?>
                        </div>
                    </div>
                </div>

                <?php if (isset($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                </div>
                <?php endif; ?>

                <!-- Add Showtime Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Thêm suất chiếu mới</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phim</label>
                                    <select class="form-select" name="movie_id" required>
                                        <option value="">Chọn phim</option>
                                        <?php while ($movie = $movies->fetch_assoc()): ?>
                                        <option value="<?php echo $movie['id']; ?>">
                                            <?php echo htmlspecialchars($movie['title']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Phòng chiếu</label>
                                    <select class="form-select" name="room_id" required>
                                        <option value="">Chọn phòng</option>
                                        <?php while ($room = $rooms->fetch_assoc()): ?>
                                        <option value="<?php echo $room['id']; ?>">
                                            <?php echo htmlspecialchars($room['name']); ?>
                                        </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Thời gian chiếu</label>
                                    <input type="datetime-local" class="form-control" name="showtime" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Giá vé</label>
                                    <input type="number" class="form-control" name="price" required>
                                </div>
                            </div>
                            <button type="submit" name="add_showtime" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Thêm suất chiếu
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Showtimes List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách suất chiếu</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Phim</th>
                                        <th>Phòng chiếu</th>
                                        <th>Thời gian chiếu</th>
                                        <th>Giá vé</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($showtime = $showtimes->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($showtime['movie_title']); ?></td>
                                        <td><?php echo htmlspecialchars($showtime['room_name']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($showtime['showtime'])); ?></td>
                                        <td><?php echo number_format($showtime['price'], 0, ',', '.'); ?> VNĐ</td>
                                        <td>
                                            <a href="admin_edit_showtime.php?id=<?php echo $showtime['id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="admin_delete_showtime.php?id=<?php echo $showtime['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa suất chiếu này?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 