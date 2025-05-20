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
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h2 {
            color: #333;
            margin-bottom: 10px;
        }
        .form-control:focus {
            border-color: #80bdff;
            box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
        }
        .btn-login {
            background-color: #e31837;
            border-color: #e31837;
            width: 100%;
            padding: 10px;
            font-size: 16px;
            font-weight: 500;
        }
        .btn-login:hover {
            background-color: #c41530;
            border-color: #c41530;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h2>Đăng nhập Admin</h2>
                <p class="text-muted">CGV Cinemas</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <input type="text" class="form-control" id="username" name="username" required>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Mật khẩu</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <button type="submit" class="btn btn-primary btn-login">Đăng nhập</button>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 