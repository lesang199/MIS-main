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
        body {
            background-color: #000 !important;
            color: #fff;
        }

        .navbar {
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(255, 193, 7, 0.1);
            background-color: #000 !important;
            border-bottom: 1px solid rgba(255, 193, 7, 0.1);
        }
        
        .navbar-brand img {
            transition: transform 0.3s ease;
        }
        
        .navbar-brand:hover img {
            transform: scale(1.05);
        }
        
        .nav-link {
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s ease;
            color: #fff !important;
        }
        
        .nav-link:hover {
            color: #ffc107 !important;
        }

        .nav-link.active {
            color: #ffc107 !important;
        }

        .navbar-toggler {
            border-color: rgba(255, 193, 7, 0.5);
        }

        .navbar-toggler:focus {
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
        }

        .navbar-toggler-icon {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 30 30'%3e%3cpath stroke='rgba%28255, 193, 7, 0.75%29' stroke-linecap='round' stroke-miterlimit='10' stroke-width='2' d='M4 7h22M4 15h22M4 23h22'/%3e%3c/svg%3e");
        }

        .movie-hero {
            position: relative;
            height: 70vh;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('./uploads/posters/<?php echo htmlspecialchars($movie['poster']); ?>');
            background-size: cover;
            background-position: center;
            display: flex;
            align-items: center;
            margin-bottom: 4rem;
        }

        .movie-info {
            padding: 0 0 4rem 0;
        }

        .movie-poster {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(255, 193, 7, 0.2);
            transition: transform 0.3s ease;
            width: 100%;
            height: auto;
        }

        .movie-poster:hover {
            transform: scale(1.02);
        }

        .movie-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #ffc107;
        }

        .movie-meta {
            margin-bottom: 2rem;
        }

        .movie-meta span {
            display: inline-block;
            margin-right: 1.5rem;
            color: #ffc107;
            font-size: 1.1rem;
        }

        .movie-meta i {
            margin-right: 0.5rem;
        }

        .movie-description {
            font-size: 1.1rem;
            line-height: 1.8;
            margin-bottom: 2rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .btn-book {
            background: #ffc107;
            color: #000;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-book:hover {
            background: #ffca2c;
            transform: scale(1.05);
            color: #000;
        }

        .movie-details {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 2rem;
            margin-top: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 193, 7, 0.1);
            height: 100%;
        }

        .detail-item {
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.03);
            border-radius: 8px;
            transition: transform 0.3s ease;
        }

        .detail-item:hover {
            transform: translateX(10px);
            background: rgba(255, 255, 255, 0.05);
        }

        .detail-label {
            color: #ffc107;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
            display: flex;
            align-items: center;
        }

        .detail-label i {
            margin-right: 0.5rem;
            width: 20px;
            text-align: center;
        }

        .detail-value {
            color: #fff;
            font-size: 1.05rem;
            padding-left: 1.5rem;
        }

        .section-title {
            position: relative;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            color: #ffc107;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100px;
            height: 3px;
            background: #ffc107;
        }

        @media (max-width: 768px) {
            .movie-hero {
                height: 50vh;
                margin-bottom: 2rem;
            }

            .movie-title {
                font-size: 2rem;
            }

            .movie-meta span {
                display: block;
                margin-bottom: 0.5rem;
            }

            .movie-details {
                margin-top: 1rem;
            }

            .detail-item {
                margin-bottom: 1rem;
            }
        }

        .trailer-section {
            background: rgba(255, 255, 255, 0.02);
            padding: 4rem 0;
            margin-top: 2rem;
        }

        .trailer-container {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            box-shadow: 0 0 30px rgba(255, 193, 7, 0.1);
            transition: transform 0.3s ease;
        }

        .trailer-container:hover {
            transform: scale(1.02);
        }

        .trailer-container iframe {
            border: none;
            width: 100%;
            height: 100%;
        }

        .booking-section {
            padding: 4rem 0;
        }

        .movie-info {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255, 193, 7, 0.1);
        }

        .movie-poster {
            width: 100%;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(255, 193, 7, 0.2);
        }

        .movie-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #ffc107;
        }

        .movie-meta {
            margin-bottom: 1.5rem;
        }

        .movie-meta span {
            display: inline-block;
            margin-right: 1.5rem;
            color: #ffc107;
        }

        .movie-meta i {
            margin-right: 0.5rem;
        }

        .booking-steps {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 2rem;
            border: 1px solid rgba(255, 193, 7, 0.1);
        }

        .step-item {
            position: relative;
            padding-left: 3rem;
        }

        .step-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 1.5rem;
            top: 3rem;
            bottom: -1rem;
            width: 2px;
            background: rgba(255, 193, 7, 0.3);
        }

        .step-number {
            width: 3rem;
            height: 3rem;
            background: #ffc107;
            color: #000;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            position: absolute;
            left: 0;
            top: 0;
        }

        .step-title {
            color: #ffc107;
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 1.1rem;
        }

        .step-description {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .showtime-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 193, 7, 0.1);
        }

        .showtime-date {
            color: #ffc107;
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .showtime-list {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .showtime-item {
            background: rgba(255, 255, 255, 0.1);
            padding: 1rem;
            border-radius: 8px;
            min-width: 150px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        .showtime-item:hover {
            background: rgba(255, 193, 7, 0.1);
            transform: translateY(-3px);
        }

        .showtime-item.selected {
            background: rgba(255, 193, 7, 0.2);
            border-color: #ffc107;
        }

        .showtime-item .time {
            font-size: 1.2rem;
            font-weight: 600;
            color: #ffc107;
            margin-bottom: 0.5rem;
        }

        .showtime-item .room-info,
        .showtime-item .price-info {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 0.3rem;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-warning sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="CGV Logo" height="60">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php"><i class="fas fa-home me-1"></i>Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="#"><i class="fas fa-film me-1"></i>Phim đang chiếu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-calendar me-1"></i>Phim sắp chiếu</a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php
                    if(isset($_SESSION['user_id'])) {
                        echo '<li class="nav-item"><a class="nav-link" href="book_ticket.php"><i class="fas fa-ticket-alt me-1"></i>Đặt vé</a></li>';
                        echo '<li class="nav-item"><a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i>Đăng xuất</a></li>';
                    } else {
                        echo '<li class="nav-item"><a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i>Đăng nhập</a></li>';
                    }
                    ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Movie Hero Section -->
    <div class="movie-hero">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="movie-title"><?php echo $movie['title']; ?></h1>
                    <div class="movie-meta">
                        <span><i class="fas fa-clock"></i><?php echo $movie['duration']; ?></span>
                        <span><i class="fas fa-film"></i><?php echo $movie['genre']; ?></span>
                    </div>
                    <p class="movie-description"><?php echo $movie['description']; ?></p>
                    <a href="book_ticket.php?id=<?php echo $movie['id']; ?>" class="btn btn-book">
                        <i class="fas fa-ticket-alt me-2"></i>Đặt vé ngay
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Movie Details Section -->
    <section class="movie-info">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <img src="./uploads/posters/<?php echo htmlspecialchars($movie['poster']); ?>" 
                         alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                         class="img-fluid movie-poster">
                </div>
                <div class="col-md-8">
                    <div class="movie-details">
                        <h3 class="section-title">Thông tin chi tiết</h3>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-user-tie"></i>Đạo diễn
                                    </div>
                                    <div class="detail-value"><?php echo $movie['director']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-calendar-alt"></i>Ngày khởi chiếu
                                    </div>
                                    <div class="detail-value"><?php echo $movie['release_date']; ?></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="detail-item">
                                    <div class="detail-label">
                                        <i class="fas fa-users"></i>Diễn viên
                                    </div>
                                    <div class="detail-value"><?php echo $movie['actor']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

   

    <?php 
    if(isset($movie['trailer']) && !empty($movie['trailer'])) {
        // Hàm lấy video ID từ URL YouTube
        function getYoutubeVideoId($url) {
            $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
            if (preg_match($pattern, $url, $match)) {
                return $match[1];
            }
            return $url; // Nếu không phải URL, trả về giá trị gốc
        }
        
        $video_id = getYoutubeVideoId($movie['trailer']);
    ?>
    <!-- Trailer Section -->
    <section class="trailer-section py-5">
        <div class="container">
            <h3 class="section-title">Trailer</h3>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="trailer-container">
                        <div class="ratio ratio-16x9">
                            <iframe src="https://www.youtube.com/embed/<?php echo htmlspecialchars($video_id); ?>" 
                                    title="YouTube video" 
                                    allowfullscreen
                                    class="rounded shadow-lg"
                                    style="border: 2px solid rgba(255, 193, 7, 0.2);">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php } ?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
