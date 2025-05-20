<?php
session_start();
require_once 'db.php';

// Kiểm tra xem admin đã đăng nhập chưa
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$showtime = null;
$movies = []; // Danh sách phim cho dropdown
$error = '';
$success = '';

// Lấy ID suất chiếu từ URL
if (isset($_GET['id'])) {
    $showtime_id = $_GET['id'];

    // Lấy thông tin suất chiếu từ database
    $sql = "SELECT * FROM showtimes WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $showtime_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $showtime = $result->fetch_assoc();

    // Nếu không tìm thấy suất chiếu, chuyển hướng về trang quản lý suất chiếu
    if (!$showtime) {
        header('Location: admin_showtimes.php');
        exit;
    }

    // Lấy danh sách phim để hiển thị trong dropdown
    $sql_movies = "SELECT id, title FROM movies ORDER BY title ASC";
    $result_movies = $conn->query($sql_movies);
    $movies = $result_movies->fetch_all(MYSQLI_ASSOC);

} else {
    // Nếu không có ID suất chiếu trên URL, chuyển hướng về trang quản lý suất chiếu
    header('Location: admin_showtimes.php');
    exit;
}

// Xử lý khi form được gửi đi (cập nhật thông tin suất chiếu)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $movie_id = $_POST['movie_id'];
    $show_date = $_POST['show_date'];
    $show_time = $_POST['show_time'];

    // Validate input
    if (empty($movie_id) || empty($show_date) || empty($show_time)) {
        $error = 'Vui lòng điền đầy đủ thông tin suất chiếu.';
    } else {
        // Prepare and execute UPDATE statement
        $sql = "UPDATE showtimes SET movie_id = ?, show_date = ?, show_time = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $movie_id, $show_date, $show_time, $showtime_id);

        if ($stmt->execute()) {
            $success = 'Cập nhật thông tin suất chiếu thành công!';
            // Cập nhật lại biến $showtime để hiển thị thông tin mới nhất trên form
            $sql = "SELECT * FROM showtimes WHERE id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $showtime_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $showtime = $result->fetch_assoc();

        } else {
            $error = 'Lỗi khi cập nhật thông tin suất chiếu: ' . $stmt->error;
        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sửa Suất chiếu - Admin - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_movies.php">Quản lý Phim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_showtimes.php">Quản lý Suất chiếu</a>
                    </li>
                    <!-- Thêm các liên kết quản lý khác tại đây -->
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_logout.php">Đăng xuất</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container admin-container">
        <h2 class="mb-4">Sửa Suất chiếu</h2>
        <a href="admin_showtimes.php" class="btn btn-secondary mb-3">Quay lại danh sách Suất chiếu</a>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($showtime): ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="movie_id" class="form-label">Phim</label>
                    <select class="form-select" id="movie_id" name="movie_id" required>
                        <option value="">-- Chọn phim --</option>
                        <?php foreach ($movies as $movie): ?>
                            <option value="<?php echo $movie['id']; ?>" <?php echo ($showtime['movie_id'] == $movie['id']) ? 'selected' : ''; ?>><?php echo $movie['title']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="show_date" class="form-label">Ngày chiếu</label>
                    <input type="date" class="form-control" id="show_date" name="show_date" value="<?php echo htmlspecialchars($showtime['show_date']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="show_time" class="form-label">Giờ chiếu</label>
                    <input type="time" class="form-control" id="show_time" name="show_time" value="<?php echo htmlspecialchars($showtime['show_time']); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Cập nhật Suất chiếu</button>
            </form>
        <?php else: ?>
            <p>Không tìm thấy thông tin suất chiếu.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 