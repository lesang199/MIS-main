<?php
session_start();
require_once 'db.php';

// Debug: Kiểm tra kết nối database
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Chỉ chuyển hướng nếu đã đăng nhập VÀ đang ở trang login
if (isset($_SESSION['admin_id']) && basename($_SERVER['PHP_SELF']) == 'admin_login.php') {
    header('Location: admin_dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Debug: In ra thông tin đăng nhập
    error_log("Login attempt - Username: " . $username . ", Password: " . $password);

    if (empty($username) || empty($password)) {
        $error = 'Vui lòng nhập đầy đủ thông tin đăng nhập';
    } else {
        // Debug: Kiểm tra bảng admins
        $check_table = "SHOW TABLES LIKE 'admins'";
        $table_result = $conn->query($check_table);
        if ($table_result->num_rows == 0) {
            die("Bảng admins không tồn tại!");
        }

        $sql = "SELECT * FROM admins WHERE username = ? AND password = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Lỗi prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $username, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        // Debug: In ra số lượng kết quả
        error_log("Number of results: " . $result->num_rows);

        if ($result->num_rows == 1) {
            $admin = $result->fetch_assoc();
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_username'] = $admin['username'];
            header('Location: admin_dashboard.php');
            exit();
        } else {
            $error = 'Tên đăng nhập hoặc mật khẩu không đúng';
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng nhập Admin - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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

        .login-container {
            min-height: calc(100vh - 200px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 0;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 193, 7, 0.1);
            width: 100%;
            max-width: 400px;
            box-shadow: 0 0 20px rgba(255, 193, 7, 0.1);
        }

        .login-title {
            color: #ffc107;
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 2rem;
            text-align: center;
        }

        .form-control {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 193, 7, 0.2);
            color: #fff;
            padding: 0.8rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background: rgba(255, 255, 255, 0.1);
            border-color: #ffc107;
            box-shadow: 0 0 0 0.25rem rgba(255, 193, 7, 0.25);
            color: #fff;
        }

        .form-control::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        .btn-login {
            background: #ffc107;
            color: #000;
            font-weight: 600;
            padding: 0.8rem 2rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            width: 100%;
            margin-top: 1rem;
        }

        .btn-login:hover {
            background: #ffca2c;
            transform: scale(1.02);
            color: #000;
        }

        .back-link {
            text-align: center;
            margin-top: 1.5rem;
            color: rgba(255, 255, 255, 0.7);
        }

        .back-link a {
            color: #ffc107;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #ffca2c;
        }

        .form-floating label {
            color: rgba(255, 255, 255, 0.7);
        }

        .form-floating>.form-control:focus~label,
        .form-floating>.form-control:not(:placeholder-shown)~label {
            color: #ffc107;
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
                        <a class="nav-link" href="#"><i class="fas fa-film me-1"></i>Phim đang chiếu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="fas fa-calendar me-1"></i>Phim sắp chiếu</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Login Form -->
    <div class="login-container">
        <div class="login-card">
            <h2 class="login-title">Đăng nhập Admin</h2>
            <?php if($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            <form action="admin_login.php" method="POST">
                <div class="form-floating mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="Tên đăng nhập" required>
                    <label for="username">Tên đăng nhập</label>
                </div>
                <div class="form-floating mb-3">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Mật khẩu" required>
                    <label for="password">Mật khẩu</label>
                </div>
                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Đăng nhập
                </button>
            </form>
            <div class="back-link">
                <a href="index.php"><i class="fas fa-arrow-left me-2"></i>Quay lại trang chủ</a>
            </div>
        </div>
    </div>

  

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 