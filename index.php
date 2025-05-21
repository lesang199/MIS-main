<?php
session_start();
include 'db.php';

$sql = "SELECT * FROM movies";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CGV Cinemas - Đặt vé xem phim</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Swiper CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">
    <link rel="stylesheet" href="footer.css">   
    <style>
        body {
            background-color: #000 !important;
            color: #fff;
        }
        
        .navbar {
            padding: 1rem 0;
            box-shadow: 0 2px 10px rgba(255, 193, 7, 0.1);
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
        }
        
        .nav-link:hover {
            color: #ffc107 !important;
        }
        
        .hero-section {
            position: relative;
            margin-bottom: 3rem;
        }
        
        .carousel-item {
            position: relative;
        }
        
        .carousel-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 30%;
            background: linear-gradient(to bottom, rgba(0,0,0,0.9), rgba(0,0,0,0));
            z-index: 1;
            pointer-events: none;
        }
        
        .carousel-item::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30%;
            background: linear-gradient(to top, rgba(0,0,0,0.9), rgba(0,0,0,0));
            z-index: 1;
            pointer-events: none;
        }
        
        .carousel-item img {
            width: 100%;
            height: 600px;
            object-fit: cover;
            filter: brightness(0.8);
        }
        
        .carousel-caption {
            z-index: 2;
            bottom: 20%;
        }
        
        .movie-section {
            padding: 4rem 0;
            background: linear-gradient(to bottom, #000, #111);
        }
        
        .section-title {
            position: relative;
            margin-bottom: 3rem;
            text-align: center;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 3px;
            background: #ffc107;
        }
        
        .movie-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease;
            margin-bottom: 2rem;
        }
        
        .movie-card:hover {
            transform: translateY(-10px);
        }
        
        .movie-card img {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        
        .movie-info {
            padding: 1.5rem;
        }
        
        .movie-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .movie-meta {
            color: #ffc107;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .btn-book {
            background: #ffc107;
            color: #000;
            font-weight: 600;
            padding: 0.75rem 1.5rem;
            border-radius: 5px;
            transition: all 0.3s ease;
        }
        
        .btn-book:hover {
            background: #ffca2c;
            transform: scale(1.05);
        }
        
        .carousel-control-prev,
        .carousel-control-next {
            width: 5%;
            opacity: 0.8;
        }
        
        .carousel-indicators {
            margin-bottom: 2rem;
        }
        
        .carousel-indicators button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            margin: 0 5px;
            background-color: #ffc107;
        }

        /* Swiper Styles */
        .swiper {
            width: 100%;
            padding: 50px 0 100px;
            position: relative;
        }

        .swiper-slide {
            width: 300px;
            height: 500px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: visible;
            transition: transform 0.3s ease;
            position: relative;
        }

        .swiper-slide:hover {
            transform: translateY(-10px);
        }

        .swiper-slide img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .swiper-slide .movie-info {
            padding: 1.5rem;
            position: relative;
            z-index: 2;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 0 0 10px 10px;
        }

        .swiper-slide .movie-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #ffc107;
        }

        .swiper-slide .movie-meta {
            color: #ffc107;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .swiper-button-next,
        .swiper-button-prev {
            color: #ffc107;
            background: rgba(0, 0, 0, 0.5);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
        }

        .swiper-button-next {
            right: 0;
        }

        .swiper-button-prev {
            left: 0;
        }

        .swiper-button-next:after,
        .swiper-button-prev:after {
            font-size: 1.2rem;
        }

        .swiper-button-next:hover,
        .swiper-button-prev:hover {
            background: rgba(255, 193, 7, 0.2);
        }

        .swiper-pagination {
            bottom: 30px;
            z-index: 1;
        }

        .swiper-pagination-bullet {
            background: #ffc107;
            opacity: 0.5;
            width: 8px;
            height: 8px;
        }

        .swiper-pagination-bullet-active {
            background: #ffc107;
            opacity: 1;
        }

        /* Container padding for navigation buttons */
        .movie-section .container {
            position: relative;
            padding: 0 50px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .movie-section .container {
                padding: 0 40px;
            }
            
            .swiper-button-next,
            .swiper-button-prev {
                width: 30px;
                height: 30px;
            }
            
            .swiper-button-next:after,
            .swiper-button-prev:after {
                font-size: 1rem;
            }

            .swiper {
                padding: 50px 0 80px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-black border-bottom border-warning sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="images/logo.png" alt="Logo" height="60">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php"><i class="fas fa-home me-1"></i>Trang chủ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-film me-1"></i>Phim đang chiếu</a>
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

    <!-- Hero Section -->
    <div class="hero-section">
        <div id="heroCarouselTop" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="images/anhnen.jpg" class="d-block w-100" alt="Ảnh nền">
                    <div class="carousel-caption text-center">
                        <h1 class="display-4 fw-bold mb-4">CHÀO MỪNG ĐẾN VỚI CineBooker<h1
                        <p class="lead mb-4">Trải nghiệm điện ảnh tuyệt vời với những bộ phim bom tấn mới nhất</p>
                        <a href="#nowShowing" class="btn btn-warning btn-lg px-5 py-3 fw-bold">Đặt vé ngay</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Now Showing Movies -->
    <section id="nowShowing" class="movie-section">
        <div class="container">
            <h2 class="section-title text-warning text-uppercase fw-bold">PHIM ĐANG CHIẾU</h2>
            <div class="swiper nowShowingSwiper">
                <div class="swiper-wrapper">
                    <?php
                    $sql = "SELECT * FROM movies WHERE status = 'now_showing'";
                    $result = $conn->query($sql);
                    
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo '<div class="swiper-slide">';
                            echo '<img src="./uploads/posters/' . htmlspecialchars($row['poster']) . '" alt="' . htmlspecialchars($row['title']) . '">';
                            echo '<div class="movie-info">';
                            echo '<h3 class="movie-title">' . htmlspecialchars($row['title']) . '</h3>';
                            echo '<div class="movie-meta">';
                            echo '<span><i class="fas fa-clock me-2"></i>' . htmlspecialchars($row['duration']) . '</span>';
                            echo '</div>';
                            echo '<a href="movie_detail.php?id=' . $row['id'] . '" class="btn btn-book w-100">Đặt vé</a>';
                            echo '</div>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
                <div class="swiper-button-next"></div>
                <div class="swiper-button-prev"></div>
                <div class="swiper-pagination"></div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <script>
        // Initialize Swiper
        const swiper = new Swiper('.nowShowingSwiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                640: {
                    slidesPerView: 2,
                },
                1024: {
                    slidesPerView: 3,
                },
            },
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>