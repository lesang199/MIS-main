<?php
session_start();
require_once 'db.php';

// Kiểm tra xem admin đã đăng nhập chưa
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$user = null;
$error = '';
$success = '';

// Lấy ID người dùng từ URL
if (isset($_GET['id'])) {
    $user_id = $_GET['id'];

    // Lấy thông tin người dùng từ database
    $sql = "SELECT id, username, email, full_name, phone_number FROM users WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Nếu không tìm thấy người dùng, chuyển hướng
    if (!$user) {
        header('Location: admin_users.php');
        exit;
    }
} else {
    // Nếu không có ID người dùng, chuyển hướng
    header('Location: admin_users.php');
    exit;
}

// Xử lý khi form được gửi đi (cập nhật thông tin người dùng)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $full_name = $_POST['full_name'];
    $phone_number = $_POST['phone_number'];

    // Validate input (simple)
    if (empty($username) || empty($email) || empty($full_name) || empty($phone_number)) {
        $error = 'Vui lòng điền đầy đủ thông tin.';
    } else {
        // Chuẩn bị và thực thi câu lệnh UPDATE
        // Lưu ý: Không sửa mật khẩu ở đây. Cần một trang riêng để admin reset mật khẩu nếu cần.
        $sql = "UPDATE users SET username = ?, email = ?, full_name = ?, phone_number = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $username, $email, $full_name, $phone_number, $user_id);

        if ($stmt->execute()) {
            $success = 'Cập nhật thông tin người dùng thành công!';
            // Cập nhật lại biến $user để hiển thị thông tin mới nhất
            $sql = "SELECT id, username, email, full_name, phone_number FROM users WHERE id = ? LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();

        } else {
            $error = 'Lỗi khi cập nhật thông tin người dùng: ' . $stmt->error;
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
    <title>Sửa Người dùng - Admin - CGV Cinemas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-container {
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">Admin Panel</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_movies.php">Quản lý Phim</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_showtimes.php">Quản lý Suất chiếu</a>
                    </li>
                     <li class="nav-item">
                        <a class="nav-link active" href="admin_users.php">Quản lý Người dùng</a>
                    </li>
                    <!-- Thêm các liên kết quản lý khác tại đây -->
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_logout.php">Đăng xuất</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container admin-container">
        <h2 class="mb-4">Sửa Người dùng: <?php echo htmlspecialchars($user['username']); ?></h2>
        <a href="admin_users.php" class="btn btn-secondary mb-3">Quay lại danh sách Người dùng</a>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($user): ?>
            <form method="POST" action="">
                <div class="mb-3">
                    <label for="username" class="form-label">Tên đăng nhập</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>
                 <div class="mb-3">
                    <label for="full_name" class="form-label">Họ tên</label>
                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="phone_number" class="form-label">Số điện thoại</label>
                    <input type="text" class="form-control" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                </div>
                
                <button type="submit" class="btn btn-primary">Cập nhật Người dùng</button>
            </form>
        <?php else: ?>
            <p>Không tìm thấy thông tin người dùng.</p>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 