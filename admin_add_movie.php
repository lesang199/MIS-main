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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $genre = $_POST['genre'];
    $director = $_POST['director'];
    $actors = $_POST['actors'];
    $release_date = $_POST['release_date'];
    $status = $_POST['status'];
    $poster = $_POST['poster']; // Giả định nhập URL poster

    // Validate input (simple validation)
    if (empty($title) || empty($description) || empty($duration) || empty($genre) || empty($director) || empty($actors) || empty($release_date) || empty($status) || empty($poster)) {
        $error = 'Vui lòng điền đầy đủ thông tin phim.';
    } else {
        // Prepare and execute INSERT statement
        $sql = "INSERT INTO movies (title, description, duration, genre, director, actors, poster, release_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissssss", $title, $description, $duration, $genre, $director, $actors, $poster, $release_date, $status);

        if ($stmt->execute()) {
            $success = 'Thêm phim mới thành công!';
            // Clear form fields after successful submission (optional)
            // $_POST = array(); 
        } else {
            $error = 'Lỗi khi thêm phim: ' . $stmt->error;
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
    <title>Thêm Phim Mới - Admin - CGV Cinemas</title>
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
                        <a class="nav-link active" href="admin_movies.php">Quản lý Phim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_showtimes.php">Quản lý Suất chiếu</a>
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
        <h2 class="mb-4">Thêm Phim Mới</h2>
        <a href="admin_movies.php" class="btn btn-secondary mb-3">Quay lại danh sách Phim</a>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="title" class="form-label">Tiêu đề</label>
                <input type="text" class="form-control" id="title" name="title" required value="<?php echo $_POST['title'] ?? ''; ?>">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Mô tả</label>
                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo $_POST['description'] ?? ''; ?></textarea>
            </div>
             <div class="mb-3">
                <label for="duration" class="form-label">Thời lượng (phút)</label>
                <input type="number" class="form-control" id="duration" name="duration" required value="<?php echo $_POST['duration'] ?? ''; ?>">
            </div>
            <div class="mb-3">
                <label for="genre" class="form-label">Thể loại</label>
                <input type="text" class="form-control" id="genre" name="genre" required value="<?php echo $_POST['genre'] ?? ''; ?>">
            </div>
            <div class="mb-3">
                <label for="director" class="form-label">Đạo diễn</label>
                <input type="text" class="form-control" id="director" name="director" required value="<?php echo $_POST['director'] ?? ''; ?>">
            </div>
            <div class="mb-3">
                <label for="actors" class="form-label">Diễn viên</label>
                <input type="text" class="form-control" id="actors" name="actors" required value="<?php echo $_POST['actors'] ?? ''; ?>">
            </div>
            <div class="mb-3">
                <label for="poster" class="form-label">URL Poster</label>
                <input type="text" class="form-control" id="poster" name="poster" required value="<?php echo $_POST['poster'] ?? ''; ?>">
            </div>
             <div class="mb-3">
                <label for="release_date" class="form-label">Ngày phát hành</label>
                <input type="date" class="form-control" id="release_date" name="release_date" required value="<?php echo $_POST['release_date'] ?? ''; ?>">
            </div>
            <div class="mb-3">
                <label for="status" class="form-label">Trạng thái</label>
                <select class="form-select" id="status" name="status" required>
                    <option value="now_showing" <?php echo (isset($_POST['status']) && $_POST['status'] == 'now_showing') ? 'selected' : ''; ?>>Đang chiếu</option>
                    <option value="upcoming" <?php echo (isset($_POST['status']) && $_POST['status'] == 'upcoming') ? 'selected' : ''; ?>>Sắp chiếu</option>
                </select>
            </div>
            
            <button type="submit" class="btn btn-primary">Thêm Phim</button>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 