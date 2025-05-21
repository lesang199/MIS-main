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

// Xử lý thêm phim mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_movie'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $release_date = $_POST['release_date'];
    $poster = $_FILES['poster']['name'];
    $status = $_POST['status'];
    
    // Upload poster
    $target_dir = "uploads/posters/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Tạo tên file duy nhất
    $file_extension = strtolower(pathinfo($_FILES['poster']['name'], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($_FILES["poster"]["tmp_name"], $target_file)) {
        $poster = $new_filename;
        
        $sql = "INSERT INTO movies (title, description, duration, release_date, poster, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssisss", $title, $description, $duration, $release_date, $poster, $status);
        
        if ($stmt->execute()) {
            $success_message = "Thêm phim thành công!";
        } else {
            $error_message = "Lỗi: " . $conn->error;
            // Xóa file đã upload nếu insert thất bại
            unlink($target_file);
        }
    } else {
        $error_message = "Lỗi khi upload poster.";
    }
}

// Xử lý xóa phim
if (isset($_GET['delete'])) {
    $movie_id = $_GET['delete'];
    
    // Bắt đầu transaction
    $conn->begin_transaction();
    
    try {
        // Xóa các suất chiếu liên quan trước
        $sql = "DELETE FROM showtimes WHERE movie_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        
        // Xóa poster nếu có
        $sql = "SELECT poster FROM movies WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($movie = $result->fetch_assoc()) {
            if (!empty($movie['poster']) && file_exists($movie['poster'])) {
                unlink($movie['poster']);
            }
        }
        
        // Sau đó xóa phim
        $sql = "DELETE FROM movies WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        
        // Commit transaction nếu mọi thứ OK
        $conn->commit();
        $success_message = "Xóa phim thành công!";
        
    } catch (Exception $e) {
        // Rollback nếu có lỗi
        $conn->rollback();
        $error_message = "Lỗi khi xóa phim: " . $e->getMessage();
    }
    
    $stmt->close();
}

// Lấy danh sách phim
$movies = $conn->query("SELECT * FROM movies ORDER BY release_date DESC");
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý phim - CGV Cinemas</title>
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
                        <h2 class="page-title">Quản lý phim</h2>
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

                <!-- Add Movie Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Thêm phim mới</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="POST" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Tên phim</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Thời lượng (phút)</label>
                                    <input type="number" class="form-control" name="duration" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mô tả</label>
                                <textarea class="form-control" name="description" rows="3" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Ngày khởi chiếu</label>
                                    <input type="date" class="form-control" name="release_date" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Poster</label>
                                    <input type="file" class="form-control" name="poster" accept="image/*" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Trạng thái</label>
                                <select class="form-select" name="status" required>
                                    <option value="now_showing">Đang chiếu</option>
                                    <option value="coming_soon">Sắp chiếu</option>
                                </select>
                            </div>
                            <button type="submit" name="add_movie" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Thêm phim
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Movies List -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Danh sách phim</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Poster</th>
                                        <th>Tên phim</th>
                                        <th>Thời lượng</th>
                                        <th>Ngày khởi chiếu</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($movie = $movies->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($movie['poster'])): ?>
                                            <img src="./uploads/posters/<?php echo htmlspecialchars($movie['poster']); ?>" 
                                                 alt="<?php echo htmlspecialchars($movie['title']); ?>"
                                                 class="img-thumbnail" 
                                                 style="width: 100px;">
                                            <?php else: ?>
                                            <span class="text-muted">Không có poster</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($movie['title']); ?></td>
                                        <td><?php echo $movie['duration']; ?> phút</td>
                                        <td><?php echo date('d/m/Y', strtotime($movie['release_date'])); ?></td>
                                        <td>
                                            <a href="admin_edit_movie.php?id=<?php echo $movie['id']; ?>" 
                                               class="btn btn-primary btn-sm">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="admin_delete_movie.php?id=<?php echo $movie['id']; ?>" 
                                               class="btn btn-danger btn-sm"
                                               onclick="return confirm('Bạn có chắc chắn muốn xóa phim này?')">
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