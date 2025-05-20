<?php
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
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="footer.css">   
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
                        <a class="nav-link active" href="index.php">Trang chủ</a>
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
                    session_start();
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

    <!-- Hero Section -->
    <div class="hero-section">
        <div id="heroCarouselTop" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img src="images/anhnen.jpg" class="d-block w-100" alt="Ảnh nền">
                    <!-- Optional: Add a very basic caption if needed, or leave empty -->
                    <div class="carousel-caption d-none d-md-block">
                         <h2>WELCOME TO CGV CINEMAS</h2>
                         <p>Book your tickets now!</p>
                     </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Now Showing Movies - Transformed to Carousel -->
    <div class="now-showing-full-width">
        <h2 class="title">PHIM ĐANG CHIẾU</h2>
        <div id="nowShowingCarousel" class="carousel slide" data-bs-ride="carousel">
             <div class="carousel-indicators">
                <?php
                require_once 'db.php';
                $sql = "SELECT id FROM movies WHERE status = 'now_showing'";
                $result_indicator = $conn->query($sql);
                if ($result_indicator->num_rows > 0) {
                    for ($i = 0; $i < $result_indicator->num_rows; $i++) {
                        echo '<button type="button" data-bs-target="#nowShowingCarousel" data-bs-slide-to="' . $i . '"' . ($i == 0 ? ' class="active" aria-current="true"' : '') . ' aria-label="Slide ' . ($i + 1) . '"></button>';
                    }
                }
                // Note: $conn is closed at the end of the file, so reuse it here.
                $sql = "SELECT * FROM movies WHERE status = 'now_showing'";
                $result = $conn->query($sql);
                ?>
            </div>
            <div class="carousel-inner">
                <?php
                if ($result->num_rows > 0) {
                    $first_item = true;
                    while($row = $result->fetch_assoc()) {
                        echo '<div class="carousel-item' . ($first_item ? ' active' : '') . '">';
                        echo '<img src="' . $row['poster'] . '" class="d-block w-100" alt="' . $row['title'] . '">';
                        echo '<div class="carousel-caption text-start">
                                <span class="badge bg-warning text-dark">NOW SHOWING</span>
                                <h1 class="display-4">' . $row['title'] . '</h1>
                                <p class="hero-metadata">Thời lượng: ' . $row['duration'] . '  Thể loại: ' . $row['genre'] . '</p>
                                <p class="hero-description">' . substr($row['description'], 0, 150) . '... <a href="movie_detail.php?id=' . $row['id'] . '" class="text-warning">See more</a></p>
                                
                                <a href="movie_detail.php?id=' . $row['id'] . '" class="btn btn-warning">Đặt vé ngay</a>
                              </div>';
                        echo '</div>';
                        $first_item = false;
                    }
                }
                ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#nowShowingCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#nowShowingCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>

    <!-- Footer --> 
    <?php include 'footer.php'?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
  const imgAll = document.querySelectorAll(".carousel-list .caurousel-item");
  const list = document.querySelector(".carousel-list");
  const iconLeft = document.querySelector(".arrow-icon-left");
  const iconRight = document.querySelector(".arrow-icon-right");
  const imgLength = imgAll.length;
  let currentIdx = 0;

  function updateTransform() {
    const width = imgAll[0].offsetWidth;
    list.style.transform = `translateX(${-width * currentIdx}px)`;
    list.style.transition = '0.5s ease';
  }

  function slideShow() {
    currentIdx = (currentIdx + 1) % imgLength;
    updateTransform();
  }

  let interval = setInterval(slideShow,3000);

  iconLeft.addEventListener("click", () => {
    clearInterval(interval);
    currentIdx = (currentIdx === 0) ? imgLength - 1 : currentIdx - 1;
    updateTransform();
    interval = setInterval(slideShow, 3000);
  });

  iconRight.addEventListener("click", () => {
    clearInterval(interval);
    currentIdx = (currentIdx === imgLength - 1) ? 0 : currentIdx + 1;
    updateTransform();
    interval = setInterval(slideShow, 3000);
  });

  // Resize support
  window.addEventListener('resize', updateTransform);
</script>
   
</body>
</html>

<?php
$conn->close();
?>
