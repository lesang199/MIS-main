<?php
session_start();
require_once 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

$movie = null;
$error_message = '';
$success_message = '';

// Lấy thông tin phim cần chỉnh sửa
if (isset($_GET['id'])) {
    $movie_id = $_GET['id'];
    $sql = "SELECT * FROM movies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $movie = $result->fetch_assoc();
    
    if (!$movie) {
        header('Location: admin_movies.php');
        exit();
    }
} else {
    header('Location: admin_movies.php');
    exit();
}

// Xử lý cập nhật thông tin phim
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_movie'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $release_date = $_POST['release_date'];
    $status = $_POST['status'];
    
    // Xử lý upload poster mới nếu có
    $poster = $movie['poster']; // Giữ poster cũ nếu không upload mới
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
        $target_dir = "uploads/posters/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Xóa poster cũ nếu tồn tại
        if (!empty($movie['poster']) && file_exists($movie['poster'])) {
            unlink($movie['poster']);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["poster"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["poster"]["tmp_name"], $target_file)) {
            $poster = $target_file;
        } else {
            $error_message .= " Lỗi tải file poster.";
        }
    }

    $sql = "UPDATE movies SET title = ?, description = ?, duration = ?, release_date = ?, poster = ?, status = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisssi", $title, $description, $duration, $release_date, $poster, $status, $movie_id);
    
    if ($stmt->execute()) {
        $success_message = "Cập nhật thông tin phim thành công!";
        // Cập nhật lại thông tin phim để hiển thị
        $sql = "SELECT * FROM movies WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $movie = $result->fetch_assoc();
    } else {
        $error_message = "Lỗi khi cập nhật thông tin phim: " . $stmt->error;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa Phim - CGV Cinemas</title>
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
        .movie-poster {
            max-width: 200px;
            height: auto;
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
                    <a href="admin_showtimes.php"><i class="fas fa-clock"></i> Quản lý suất chiếu</a>
                    <a href="admin_bookings.php"><i class="fas fa-ticket-alt"></i> Quản lý đặt vé</a>
                    <a href="admin_users.php"><i class="fas fa-users"></i> Quản lý người dùng</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Chỉnh sửa Phim</h2>
                    <a href="admin_movies.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Tên phim</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo htmlspecialchars($movie['title']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="duration" class="form-label">Thời lượng (phút)</label>
                                    <input type="number" class="form-control" id="duration" name="duration" 
                                           value="<?php echo $movie['duration']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="release_date" class="form-label">Ngày khởi chiếu</label>
                                    <input type="date" class="form-control" id="release_date" name="release_date" 
                                           value="<?php echo $movie['release_date']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Trạng thái</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="coming_soon" <?php echo $movie['status'] == 'coming_soon' ? 'selected' : ''; ?>>Sắp chiếu</option>
                                        <option value="now_showing" <?php echo $movie['status'] == 'now_showing' ? 'selected' : ''; ?>>Đang chiếu</option>
                                        <option value="ended" <?php echo $movie['status'] == 'ended' ? 'selected' : ''; ?>>Đã kết thúc</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">Mô tả</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($movie['description']); ?></textarea>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="poster" class="form-label">Poster</label>
                                    <?php if (!empty($movie['poster'])): ?>
                                        <div class="mb-2">
                                            <img src="<?php echo htmlspecialchars($movie['poster']); ?>" alt="Current Poster" class="movie-poster">
                                        </div>
                                    <?php endif; ?>
                                    <input type="file" class="form-control" id="poster" name="poster" accept="image/*">
                                    <small class="text-muted">Để trống nếu không muốn thay đổi poster</small>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="update_movie" class="btn btn-primary">Cập nhật phim</button>
                                </div>
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