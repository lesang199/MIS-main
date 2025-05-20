<?php
include 'db.php';
session_start();

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$movie_id = $_GET['id'];

// Lấy thông tin phim
$movie_sql = "SELECT * FROM movies WHERE id = ?";
$movie_stmt = $conn->prepare($movie_sql);
$movie_stmt->bind_param("i", $movie_id);
$movie_stmt->execute();
$movie_result = $movie_stmt->get_result();
$movie = $movie_result->fetch_assoc();

if (!$movie) {
    header("Location: index.php");
    exit();
}

// Lấy danh sách suất chiếu
$showtimes_sql = "SELECT s.*, r.name as room_name 
                 FROM showtimes s 
                 JOIN rooms r ON s.room_id = r.id 
                 WHERE s.movie_id = ? 
                 AND s.showtime >= CURDATE()
                 ORDER BY s.showtime ASC";
$showtimes_stmt = $conn->prepare($showtimes_sql);
$showtimes_stmt->bind_param("i", $movie_id);
$showtimes_stmt->execute();
$showtimes_result = $showtimes_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $movie['title']; ?> - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="footer.css">   
    <style>
        .movie-detail {
            padding: 40px 0;
        }
        .movie-poster {
            width: 100%;
            max-width: 300px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .movie-info {
            padding: 20px;
        }
        .movie-title {
            font-size: 2.5rem;
            margin-bottom: 20px;
        }
        .movie-meta {
            color: #666;
            margin-bottom: 20px;
        }
        .movie-description {
            margin-bottom: 30px;
            line-height: 1.6;
        }
        .showtime-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            transition: all 0.3s ease;
        }
        .showtime-card:hover {
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .showtime-date {
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }
        .showtime-time {
            display: inline-block;
            padding: 8px 15px;
            margin: 5px;
            border: 1px solid #007bff;
            border-radius: 5px;
            color: #007bff;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .showtime-time:hover {
            background-color: #007bff;
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="https://www.cgv.vn/skin/frontend/cgv/default/images/cgvlogo.png" alt="CGV Logo" height="30">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Phim đang chiếu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Phim sắp chiếu</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php
                    if(isset($_SESSION['user_id'])) {
                        echo '<li class="nav-item"><a class="nav-link" href="book_ticket.php">Đặt vé</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="logout.php">Đăng xuất</a></li>';
                    } else {
                        echo '<li class="nav-item"><a class="nav-link" href="login.php">Đăng nhập</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row">
            <!-- Thông tin phim -->
            <div class="col-md-4">
                <img src="<?php echo $movie['poster']; ?>" class="img-fluid rounded" alt="<?php echo $movie['title']; ?>">
            </div>
            <div class="col-md-8">
                <h1 class="mb-4"><?php echo $movie['title']; ?></h1>
                <div class="mb-3">
                    <span class="badge bg-warning text-dark"><?php echo $movie['status'] == 'now_showing' ? 'Đang chiếu' : 'Sắp chiếu'; ?></span>
                </div>
                <p class="mb-3"><strong>Thời lượng:</strong> <?php echo $movie['duration']; ?> phút</p>
                <p class="mb-3"><strong>Thể loại:</strong> <?php echo $movie['genre']; ?></p>
                <p class="mb-4"><?php echo $movie['description']; ?></p>
                <a href="book_ticket.php?id=<?php echo $movie['id']; ?>" class="btn btn-warning btn-lg">Đặt vé ngay</a>
            </div>
        </div>

        <!-- Danh sách suất chiếu -->
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="mb-4">Lịch chiếu</h2>
                <?php if ($showtimes_result->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Phòng chiếu</th>
                                    <th>Thời gian</th>
                                    <th>Giá vé</th>
                                    <th>Thao tác</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($showtime = $showtimes_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $showtime['room_name']; ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($showtime['showtime'])); ?></td>
                                        <td><?php echo number_format($showtime['price'], 0, ',', '.'); ?> VNĐ</td>
                                        <td>
                                            <?php if(isset($_SESSION['user_id'])): ?>
                                                <a href="book_ticket.php?showtime_id=<?php echo $showtime['id']; ?>" class="btn btn-warning btn-sm">Đặt vé</a>
                                            <?php else: ?>
                                                <a href="login.php" class="btn btn-warning btn-sm">Đăng nhập để đặt vé</a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="alert alert-info">
                        Hiện tại chưa có suất chiếu nào cho phim này.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>CGV Cinemas</h5>
                    <p>Hệ thống rạp chiếu phim hiện đại nhất Việt Nam</p>
                </div>
                <div class="col-md-4">
                    <h5>Liên kết</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-light">Về chúng tôi</a></li>
                        <li><a href="#" class="text-light">Điều khoản sử dụng</a></li>
                        <li><a href="#" class="text-light">Chính sách bảo mật</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Liên hệ</h5>
                    <p>Email: contact@cgv.vn</p>
                    <p>Hotline: 1900 6017</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
