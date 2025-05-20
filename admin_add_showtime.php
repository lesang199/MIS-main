<?php
session_start();
require_once 'db.php';

// Kiểm tra xem admin đã đăng nhập chưa
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$error = '';
$success = '';

// Lấy danh sách phim để hiển thị trong dropdown
$sql_movies = "SELECT id, title FROM movies ORDER BY title ASC";
$result_movies = $conn->query($sql_movies);
$movies = $result_movies->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $movie_id = $_POST['movie_id'];
    $show_date = $_POST['show_date'];
    $show_time = $_POST['show_time'];

    // Validate input
    if (empty($movie_id) || empty($show_date) || empty($show_time)) {
        $error = 'Vui lòng điền đầy đủ thông tin suất chiếu.';
    } else {
        // Prepare and execute INSERT statement
        $sql = "INSERT INTO showtimes (movie_id, show_date, show_time) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iss", $movie_id, $show_date, $show_time);

        if ($stmt->execute()) {
            $success = 'Thêm suất chiếu mới thành công!';
            // Clear form fields (optional)
            // $_POST = array();
        } else {
            $error = 'Lỗi khi thêm suất chiếu: ' . $stmt->error;
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
    <title>Thêm Suất chiếu Mới - Admin - CGV Cinemas</title>
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
        <h2 class="mb-4">Thêm Suất chiếu Mới</h2>
        <a href="admin_showtimes.php" class="btn btn-secondary mb-3">Quay lại danh sách Suất chiếu</a>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="movie_id" class="form-label">Phim</label>
                <select class="form-select" id="movie_id" name="movie_id" required>
                    <option value="">-- Chọn phim --</option>
                    <?php foreach ($movies as $movie): ?>
                        <option value="<?php echo $movie['id']; ?>" <?php echo (isset($_POST['movie_id']) && $_POST['movie_id'] == $movie['id']) ? 'selected' : ''; ?>><?php echo $movie['title']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="show_date" class="form-label">Ngày chiếu</label>
                <input type="date" class="form-control" id="show_date" name="show_date" required value="<?php echo $_POST['show_date'] ?? ''; ?>">
            </div>
            <div class="mb-3">
                <label for="show_time" class="form-label">Giờ chiếu</label>
                <input type="time" class="form-control" id="show_time" name="show_time" required value="<?php echo $_POST['show_time'] ?? ''; ?>">
            </div>
            
            <button type="submit" class="btn btn-primary">Thêm Suất chiếu</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 