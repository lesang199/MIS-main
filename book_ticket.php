<?php
include 'db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

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
    <title>Đặt vé - <?php echo $movie['title']; ?> - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="footer.css">
    <!-- Thêm Flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/dark.css">
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

        .btn-continue {
            background: #ffc107;
            color: #000;
            font-weight: 600;
            padding: 1rem 2rem;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
            margin-top: 2rem;
        }

        .btn-continue:hover {
            background: #ffca2c;
            transform: scale(1.05);
        }

        .btn-continue:disabled {
            background: #666;
            cursor: not-allowed;
            transform: none;
        }

        .filter-bar {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid rgba(255, 193, 7, 0.1);
        }

        .filter-group {
            margin-bottom: 1rem;
        }

        .filter-label {
            color: #ffc107;
            font-weight: 500;
            margin-bottom: 0.5rem;
            display: block;
        }

        .filter-select, .filter-input {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 193, 7, 0.2);
            color: #fff;
            border-radius: 5px;
            padding: 0.5rem;
            width: 100%;
            transition: all 0.3s ease;
        }

        .filter-select:focus, .filter-input:focus {
            background: rgba(255, 255, 255, 0.15);
            border-color: #ffc107;
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
            color: #fff;
        }

        .filter-select option {
            background: #000;
            color: #fff;
        }

        .btn-filter {
            background: #ffc107;
            color: #000;
            font-weight: 600;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            border: none;
            transition: all 0.3s ease;
            height: 38px;
        }

        .btn-filter:hover {
            background: #ffca2c;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 193, 7, 0.2);
        }

        .btn-filter:active {
            transform: translateY(0);
        }

        /* Style cho Flatpickr */
        .flatpickr-calendar {
            background: #1a1a1a;
            border: 1px solid rgba(255, 193, 7, 0.2);
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.5);
        }

        .flatpickr-day {
            color: #fff;
        }

        .flatpickr-day.selected {
            background: #ffc107;
            border-color: #ffc107;
            color: #000;
        }

        .flatpickr-day:hover {
            background: rgba(255, 193, 7, 0.2);
        }

        .flatpickr-day.disabled {
            color: rgba(255, 255, 255, 0.3);
        }

        .flatpickr-months .flatpickr-month {
            background: #000;
            color: #ffc107;
        }

        .flatpickr-current-month .flatpickr-monthDropdown-months {
            background: #000;
            color: #ffc107;
        }

        .flatpickr-weekdays {
            background: #000;
        }

        .flatpickr-weekday {
            color: #ffc107;
        }

        @media (max-width: 768px) {
            .filter-group {
                margin-bottom: 1rem;
            }
        }

        .table {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
        }

        .table thead th {
            background: rgba(255, 193, 7, 0.1);
            color: #ffc107;
            border-bottom: 2px solid rgba(255, 193, 7, 0.2);
            padding: 1rem;
        }

        .table tbody td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .table tbody tr:hover {
            background: rgba(255, 193, 7, 0.1);
        }

        .table-responsive {
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
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

    <!-- Booking Section -->
    <section class="booking-section">
        <div class="container">
            <div class="row">
                <!-- Left Column: Movie Info & Steps -->
                <div class="col-lg-4">
                    <!-- Movie Info -->
                    <div class="movie-info mb-4">
                        <img src="./uploads/posters/<?php echo htmlspecialchars($movie['poster']); ?>" 
                             alt="<?php echo htmlspecialchars($movie['title']); ?>" 
                             class="img-fluid movie-poster mb-3">
                        <h1 class="movie-title"><?php echo $movie['title']; ?></h1>
                        <div class="movie-meta">
                            <span><i class="fas fa-clock"></i><?php echo $movie['duration']; ?></span>
                            <span><i class="fas fa-film"></i><?php echo $movie['genre']; ?></span>
                        </div>
                    </div>

                    <!-- Booking Steps -->
                    <div class="booking-steps">
                        <div class="step-item mb-4">
                            <div class="step-number">1</div>
                            <div class="step-title">Chọn suất chiếu</div>
                            <div class="step-description">Chọn ngày và giờ chiếu phù hợp</div>
                        </div>
                        <div class="step-item mb-4">
                            <div class="step-number">2</div>
                            <div class="step-title">Chọn ghế</div>
                            <div class="step-description">Chọn vị trí ghế mong muốn</div>
                        </div>
                        <div class="step-item">
                            <div class="step-number">3</div>
                            <div class="step-title">Thanh toán</div>
                            <div class="step-description">Hoàn tất đặt vé và thanh toán</div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Showtimes -->
                <div class="col-lg-8">
                    <h3 class="text-warning mb-4">Chọn suất chiếu</h3>

                    <!-- Filter Bar -->
                    <div class="filter-bar mb-4">
                        <form method="GET" action="" class="row align-items-center">
                            <input type="hidden" name="id" value="<?php echo $movie_id; ?>">
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label class="filter-label"><i class="fas fa-calendar me-2"></i>Ngày</label>
                                    <input type="text" class="filter-input" id="datePicker" name="date" 
                                           value="<?php echo isset($_GET['date']) ? $_GET['date'] : ''; ?>"
                                           placeholder="Chọn ngày">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label class="filter-label"><i class="fas fa-clock me-2"></i>Khung giờ</label>
                                    <select class="form-select filter-select" name="time_slot">
                                        <option value="">Tất cả giờ</option>
                                        <option value="morning" <?php echo (isset($_GET['time_slot']) && $_GET['time_slot'] == 'morning') ? 'selected' : ''; ?>>
                                            Sáng (6:00 - 11:59)
                                        </option>
                                        <option value="afternoon" <?php echo (isset($_GET['time_slot']) && $_GET['time_slot'] == 'afternoon') ? 'selected' : ''; ?>>
                                            Trưa (12:00 - 17:59)
                                        </option>
                                        <option value="evening" <?php echo (isset($_GET['time_slot']) && $_GET['time_slot'] == 'evening') ? 'selected' : ''; ?>>
                                            Tối (18:00 - 22:59)
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label class="filter-label"><i class="fas fa-door-open me-2"></i>Phòng chiếu</label>
                                    <select class="form-select filter-select" name="room">
                                        <option value="">Tất cả phòng</option>
                                        <?php
                                        $rooms_sql = "SELECT DISTINCT r.name as room_name 
                                                    FROM showtimes s
                                                    JOIN rooms r ON s.room_id = r.id
                                                    WHERE s.movie_id = ? AND s.showtime >= CURDATE()
                                                    ORDER BY r.name ASC";
                                        $rooms_stmt = $conn->prepare($rooms_sql);
                                        $rooms_stmt->bind_param("i", $movie_id);
                                        $rooms_stmt->execute();
                                        $rooms_result = $rooms_stmt->get_result();
                                        
                                        while ($room = $rooms_result->fetch_assoc()) {
                                            $selected = (isset($_GET['room']) && $_GET['room'] == $room['room_name']) ? 'selected' : '';
                                            echo '<option value="' . $room['room_name'] . '" ' . $selected . '>' . 
                                                 $room['room_name'] . '</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="filter-group">
                                    <label class="filter-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-filter w-100">
                                        <i class="fas fa-filter me-2"></i>Lọc
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <?php
                    // Xây dựng câu truy vấn dựa trên bộ lọc
                    $showtimes_sql = "SELECT s.*, r.name as room_name 
                                    FROM showtimes s
                                    JOIN rooms r ON s.room_id = r.id
                                    WHERE s.movie_id = ? AND s.showtime >= CURDATE()";
                    
                    $params = array($movie_id);
                    $types = "i";

                    if (!empty($_GET['date'])) {
                        $showtimes_sql .= " AND DATE(s.showtime) = ?";
                        $params[] = $_GET['date'];
                        $types .= "s";
                    }

                    if (!empty($_GET['time_slot'])) {
                        switch ($_GET['time_slot']) {
                            case 'morning':
                                $showtimes_sql .= " AND HOUR(s.showtime) BETWEEN 6 AND 11";
                                break;
                            case 'afternoon':
                                $showtimes_sql .= " AND HOUR(s.showtime) BETWEEN 12 AND 17";
                                break;
                            case 'evening':
                                $showtimes_sql .= " AND HOUR(s.showtime) BETWEEN 18 AND 22";
                                break;
                        }
                    }

                    if (!empty($_GET['room'])) {
                        $showtimes_sql .= " AND r.name = ?";
                        $params[] = $_GET['room'];
                        $types .= "s";
                    }

                    $showtimes_sql .= " ORDER BY s.showtime ASC";
                    
                    $showtimes_stmt = $conn->prepare($showtimes_sql);
                    $showtimes_stmt->bind_param($types, ...$params);
                    $showtimes_stmt->execute();
                    $showtimes_result = $showtimes_stmt->get_result();

                    if ($showtimes_result->num_rows > 0) {
                        $current_date = '';
                        while ($showtime = $showtimes_result->fetch_assoc()) {
                            $showtime_date = date('Y-m-d', strtotime($showtime['showtime']));
                            $formatted_date = date('d/m/Y', strtotime($showtime['showtime']));
                            $formatted_time = date('H:i', strtotime($showtime['showtime']));
                            
                            if ($current_date != $showtime_date) {
                                if ($current_date != '') {
                                    echo '</div>'; // Close previous showtime-list
                                    echo '</div>'; // Close previous showtime-card
                                }
                                echo '<div class="showtime-card mb-4">';
                                echo '<div class="showtime-date"><i class="fas fa-calendar-day"></i>' . $formatted_date . '</div>';
                                echo '<div class="showtime-list">';
                                $current_date = $showtime_date;
                            }
                            
                            echo '<div class="showtime-item" data-showtime-id="' . $showtime['id'] . '">';
                            echo $formatted_time;
                            echo '<div class="room-info"><i class="fas fa-door-open"></i> ' . $showtime['room_name'] . '</div>';
                            echo '<div class="price-info"><i class="fas fa-ticket-alt"></i> ' . number_format($showtime['price'], 0, ',', '.') . ' VNĐ</div>';
                            echo '</div>';
                        }
                        echo '</div>'; // Close last showtime-list
                        echo '</div>'; // Close last showtime-card
                    } else {
                        echo '<div class="alert alert-warning">Không tìm thấy suất chiếu phù hợp với bộ lọc của bạn.</div>';
                    }
                    ?>

                    <!-- Continue Button -->
                    <div class="text-center mt-4">
                        <button class="btn btn-continue" id="continueBtn" disabled>
                            Tiếp tục <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                    </div>

                    <!-- Thêm Flatpickr JS -->
                    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            // Lấy danh sách ngày có suất chiếu
                            <?php
                            $dates_sql = "SELECT DISTINCT DATE(showtime) as show_date 
                                        FROM showtimes 
                                        WHERE movie_id = ? AND showtime >= CURDATE()
                                        ORDER BY show_date ASC";
                            $dates_stmt = $conn->prepare($dates_sql);
                            $dates_stmt->bind_param("i", $movie_id);
                            $dates_stmt->execute();
                            $dates_result = $dates_stmt->get_result();
                            
                            $available_dates = array();
                            while ($date = $dates_result->fetch_assoc()) {
                                $available_dates[] = $date['show_date'];
                            }
                            ?>

                            // Khởi tạo Flatpickr
                            flatpickr("#datePicker", {
                                dateFormat: "Y-m-d",
                                minDate: "today",
                                enable: <?php echo json_encode($available_dates); ?>,
                                disableMobile: "true",
                                theme: "dark",
                                locale: {
                                    firstDayOfWeek: 1,
                                    weekdays: {
                                        shorthand: ["CN", "T2", "T3", "T4", "T5", "T6", "T7"],
                                        longhand: ["Chủ nhật", "Thứ hai", "Thứ ba", "Thứ tư", "Thứ năm", "Thứ sáu", "Thứ bảy"]
                                    },
                                    months: {
                                        shorthand: ["T1", "T2", "T3", "T4", "T5", "T6", "T7", "T8", "T9", "T10", "T11", "T12"],
                                        longhand: ["Tháng 1", "Tháng 2", "Tháng 3", "Tháng 4", "Tháng 5", "Tháng 6", "Tháng 7", "Tháng 8", "Tháng 9", "Tháng 10", "Tháng 11", "Tháng 12"]
                                    }
                                }
                            });

                            const showtimeItems = document.querySelectorAll('.showtime-item');
                            const continueBtn = document.getElementById('continueBtn');
                            let selectedShowtimeId = null;

                            showtimeItems.forEach(item => {
                                item.addEventListener('click', function() {
                                    // Remove selected class from all items
                                    showtimeItems.forEach(i => i.classList.remove('selected'));
                                    // Add selected class to clicked item
                                    this.classList.add('selected');
                                    // Enable continue button
                                    continueBtn.disabled = false;
                                    // Store selected showtime ID
                                    selectedShowtimeId = this.dataset.showtimeId;
                                });
                            });

                            continueBtn.addEventListener('click', function() {
                                if (selectedShowtimeId) {
                                    window.location.href = 'select_seats.php?showtime_id=' + selectedShowtimeId;
                                }
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <?php include 'footer.php'?>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
$conn->close();
?>
