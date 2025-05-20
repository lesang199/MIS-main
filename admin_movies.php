<?php
session_start();
require_once 'db.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit();
}

// Xử lý thêm phim mới
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_movie'])) {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $duration = $_POST['duration'];
    $release_date = $_POST['release_date'];
    $status = $_POST['status'];
    
    // Xử lý upload poster
    $poster = '';
    if (isset($_FILES['poster']) && $_FILES['poster']['error'] == 0) {
        $target_dir = "uploads/posters/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = strtolower(pathinfo($_FILES["poster"]["name"], PATHINFO_EXTENSION));
        $new_filename = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $new_filename;
        
        if (move_uploaded_file($_FILES["poster"]["tmp_name"], $target_file)) {
            $poster = $target_file;
            // Debug: Log successful upload and path
            error_log("File uploaded successfully: " . $target_file);
        } else {
            // Debug: Log upload failure
            error_log("File upload failed for: " . $_FILES["poster"]["name"] . ", error code: " . $_FILES["poster"]["error"]);
            $error_message .= " Lỗi tải file poster.";
        }
    }
    // Debug: Log the final poster path before insert
    error_log("Poster path to be inserted: " . $poster);

    $sql = "INSERT INTO movies (title, description, duration, release_date, poster, status) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssisss", $title, $description, $duration, $release_date, $poster, $status);
    
    if ($stmt->execute()) {
        $success_message = "Thêm phim thành công!";
    } else {
        $error_message = "Lỗi khi thêm phim: " . $stmt->error;
    }
    $stmt->close();
}

// Xử lý xóa phim
if (isset($_GET['delete'])) {
    $movie_id = $_GET['delete'];
    
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
    
    // Xóa các suất chiếu liên quan trước
    $sql = "DELETE FROM showtimes WHERE movie_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    
    // Sau đó xóa phim
    $sql = "DELETE FROM movies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $movie_id);
    
    if ($stmt->execute()) {
        $success_message = "Xóa phim thành công!";
    } else {
        $error_message = "Lỗi khi xóa phim: " . $stmt->error;
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
    <title>Quản lý Phim - CGV Cinemas</title>
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
            width: 100px;
            height: 150px;
            object-fit: cover;
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
                    <h2>Quản lý Phim</h2>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="alert alert-success"><?php echo $success_message; ?></div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="alert alert-danger"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Form thêm phim -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Thêm phim mới</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Tên phim</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="duration" class="form-label">Thời lượng (phút)</label>
                                    <input type="number" class="form-control" id="duration" name="duration" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="release_date" class="form-label">Ngày khởi chiếu</label>
                                    <input type="date" class="form-control" id="release_date" name="release_date" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Trạng thái</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="coming_soon">Sắp chiếu</option>
                                        <option value="now_showing">Đang chiếu</option>
                                        <option value="ended">Đã kết thúc</option>
                                    </select>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="description" class="form-label">Mô tả</label>
                                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                                </div>
                                <div class="col-md-12 mb-3">
                                    <label for="poster" class="form-label">Poster</label>
                                    <input type="file" class="form-control" id="poster" name="poster" accept="image/*">
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="add_movie" class="btn btn-primary">Thêm phim</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Danh sách phim -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Danh sách phim</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Poster</th>
                                        <th>Tên phim</th>
                                        <th>Thời lượng</th>
                                        <th>Ngày khởi chiếu</th>
                                        <th>Trạng thái</th>
                                        <th>Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($movie = $movies->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo $movie['id']; ?></td>
                                            <td>
                                                <?php if (!empty($movie['poster'])): ?>
                                                    <img src="<?php echo htmlspecialchars($movie['poster']); ?>" 
                                                         class="movie-poster" alt="Poster">
                                                <?php else: ?>
                                                    <div class="movie-poster bg-secondary"></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($movie['title']); ?></td>
                                            <td><?php echo $movie['duration']; ?> phút</td>
                                            <td><?php echo date('d/m/Y', strtotime($movie['release_date'])); ?></td>
                                            <td>
                                                <?php
                                                $status_labels = [
                                                    'coming_soon' => 'Sắp chiếu',
                                                    'now_showing' => 'Đang chiếu',
                                                    'ended' => 'Đã kết thúc'
                                                ];
                                                echo $status_labels[$movie['status']] ?? $movie['status'];
                                                ?>
                                            </td>
                                            <td>
                                                <a href="?delete=<?php echo $movie['id']; ?>" 
                                                   class="btn btn-danger btn-sm"
                                                   onclick="return confirm('Bạn có chắc chắn muốn xóa phim này?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                                <a href="admin_edit_movie.php?id=<?php echo $movie['id']; ?>" 
                                                   class="btn btn-primary btn-sm">
                                                    <i class="fas fa-edit"></i>
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