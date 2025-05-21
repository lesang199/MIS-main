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

// Kiểm tra có ID phim không
if (!isset($_GET['id'])) {
    header('Location: admin_movies.php');
    exit();
}

$movie_id = $_GET['id'];

// Lấy thông tin phim
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

// Xử lý cập nhật phim
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_movie'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $release_date = $_POST['release_date'];
    $status = $_POST['status'];
    
    // Xử lý upload poster mới nếu có
    if (!empty($_FILES['poster']['name'])) {
        $target_dir = "uploads/posters/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        // Tạo tên file duy nhất
        $file_extension = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        // Xóa poster cũ nếu tồn tại
        if (!empty($movie['poster']) && file_exists($target_dir . $movie['poster'])) {
            unlink($target_dir . $movie['poster']);
        }
        
        if (move_uploaded_file($_FILES["poster"]["tmp_name"], $target_file)) {
            $poster = $new_filename;
            
            $sql = "UPDATE movies SET title = ?, description = ?, duration = ?, release_date = ?, poster = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssisssi", $title, $description, $duration, $release_date, $poster, $status, $movie_id);
        } else {
            $error_message = "Lỗi khi upload poster.";
            $sql = "UPDATE movies SET title = ?, description = ?, duration = ?, release_date = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssissi", $title, $description, $duration, $release_date, $status, $movie_id);
        }
    } else {
        $sql = "UPDATE movies SET title = ?, description = ?, duration = ?, release_date = ?, status = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssissi", $title, $description, $duration, $release_date, $status, $movie_id);
    }
    
    if ($stmt->execute()) {
        $success_message = "Cập nhật phim thành công!";
        // Cập nhật lại thông tin phim
        $stmt = $conn->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $movie = $result->fetch_assoc();
    } else {
        $error_message = "Lỗi: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chỉnh sửa phim - CGV Cinemas</title>
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
                    <a href="admin_movies.php" class="active"><i class="fas fa-film"></i> Quản lý phim</a>
                    <a href="admin_showtimes.php"><i class="fas fa-clock"></i> Quản lý suất chiếu</a>
                    <a href="admin_bookings.php"><i class="fas fa-ticket-alt"></i> Quản lý đặt vé</a>
                    <a href="admin_users.php"><i class="fas fa-users"></i> Quản lý người dùng</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a>
                </nav>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 content">
                <div class="page-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2 class="page-title">Chỉnh sửa phim</h2>
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

                <!-- Edit Movie Form -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-edit me-2"></i>Chỉnh sửa thông tin phim</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tên phim</label>
                                    <input type="text" class="form-control" name="title" value="<?php echo htmlspecialchars($movie['title']); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Thời lượng (phút)</label>
                                    <input type="number" class="form-control" name="duration" value="<?php echo $movie['duration']; ?>" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" name="description" rows="3" required><?php echo htmlspecialchars($movie['description']); ?></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ngày khởi chiếu</label>
                                    <input type="date" class="form-control" name="release_date" value="<?php echo $movie['release_date']; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Poster</label>
                                    <input type="file" class="form-control" name="poster" accept="image/*">
                                    <small class="text-muted">Để trống nếu không muốn thay đổi poster</small>
                                    <?php if (!empty($movie['poster'])): ?>
                                    <div class="mt-2">
                                        <img src="<?php echo htmlspecialchars($target_dir . $movie['poster']); ?>" 
                                             alt="Current poster" 
                                             class="img-thumbnail" 
                                             style="max-width: 200px;">
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select class="form-select" name="status" required>
                                    <option value="now_showing" <?php echo $movie['status'] == 'now_showing' ? 'selected' : ''; ?>>Đang chiếu</option>
                                    <option value="coming_soon" <?php echo $movie['status'] == 'coming_soon' ? 'selected' : ''; ?>>Sắp chiếu</option>
                                </select>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" name="update_movie" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Lưu thay đổi
                                </button>
                                <a href="admin_movies.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Quay lại
                                </a>
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