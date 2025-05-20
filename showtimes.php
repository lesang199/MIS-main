<?php
session_start();
require_once 'db.php';

// Lấy danh sách phim đang chiếu
$sql = "SELECT * FROM movies WHERE status = 'now_showing' ORDER BY release_date DESC";
$result = $conn->query($sql);
$movies = $result->fetch_all(MYSQLI_ASSOC);

// Lấy suất chiếu nếu có movie_id được chọn
$selected_movie = null;
$showtimes = array();
if (isset($_GET['movie_id'])) {
    $movie_id = $_GET['movie_id'];
    
    // Lấy thông tin phim
    $sql = "SELECT * FROM movies WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $movie_id);
    $stmt->execute();
    $selected_movie = $stmt->get_result()->fetch_assoc();

    if ($selected_movie) {
        // Lấy suất chiếu của phim
        $sql = "SELECT s.*, 
                (SELECT COUNT(*) FROM booked_seats bs 
                 JOIN bookings b ON bs.booking_id = b.id 
                 WHERE b.showtime_id = s.id AND b.status != 'cancelled') as booked_seats
                FROM showtimes s 
                WHERE s.movie_id = ? AND s.show_date >= CURDATE() 
                ORDER BY s.show_date, s.show_time";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $movie_id);
        $stmt->execute();
        $showtimes = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suất chiếu - CGV Cinemas</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
                        <a class="nav-link active" href="showtimes.php">Suất chiếu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Phim sắp chiếu</a>
                    </li>
                </ul>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Đăng xuất</a>
                        </li>
                    </ul>
                <?php else: ?>
                    <ul class="navbar-nav">
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Đăng nhập</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Đăng ký</a>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Showtimes Section -->
    <div class="container my-5">
        <h2 class="text-center mb-4">Suất chiếu</h2>

        <!-- Movie Selection -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Chọn phim</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($movies as $movie): ?>
                                <div class="col-md-3 mb-3">
                                    <div class="movie-card <?php echo (isset($_GET['movie_id']) && $_GET['movie_id'] == $movie['id']) ? 'selected' : ''; ?>">
                                        <a href="?movie_id=<?php echo $movie['id']; ?>" class="text-decoration-none">
                                            <img src="<?php echo $movie['poster']; ?>" alt="<?php echo $movie['title']; ?>" class="img-fluid mb-2">
                                            <h6 class="text-dark"><?php echo $movie['title']; ?></h6>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($selected_movie): ?>
            <!-- Showtimes for Selected Movie -->
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="mb-0"><?php echo $selected_movie['title']; ?></h5>
                                </div>
                                <div class="col-md-4">
                                    <img src="<?php echo $selected_movie['poster']; ?>" alt="<?php echo $selected_movie['title']; ?>" class="img-fluid" style="max-height: 100px;">
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <?php
                            $current_date = null;
                            foreach ($showtimes as $showtime):
                                $show_date = date('Y-m-d', strtotime($showtime['show_date']));
                                if ($current_date != $show_date):
                                    if ($current_date !== null) echo '</div>'; // Close previous date group
                                    $current_date = $show_date;
                            ?>
                                <h6 class="mt-3 mb-2"><?php echo date('d/m/Y', strtotime($show_date)); ?></h6>
                                <div class="showtime-group mb-3">
                            <?php endif; ?>
                                <a href="book_ticket.php?movie_id=<?php echo $selected_movie['id']; ?>&showtime_id=<?php echo $showtime['id']; ?>" 
                                   class="btn btn-outline-primary me-2 mb-2">
                                    <?php echo date('H:i', strtotime($showtime['show_time'])); ?>
                                    <small class="d-block text-muted">
                                        <?php echo $showtime['booked_seats']; ?>/50 ghế
                                    </small>
                                </a>
                            <?php endforeach; ?>
                            <?php if ($current_date !== null) echo '</div>'; // Close last date group ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-light py-4">
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

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 