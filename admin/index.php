<?php
session_start();
// Sử dụng đường dẫn tương đối lùi 1 cấp để gọi file db.php
require_once '../includes/db.php'; 

// Nếu Admin đã đăng nhập thì tự động chuyển hướng vào Dashboard
if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // Xử lý đăng nhập phía Server
    $stmt = $conn->prepare("SELECT id, password, fullname, status FROM users WHERE username = ? AND role = 'admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $admin = $result->fetch_assoc();
        if ($admin['status'] == 'locked') {
            $error = "Tài khoản của bạn đã bị khoá.";
        } else {
            // Kiểm tra mật khẩu (Hỗ trợ cả mã hóa MD5/Bcrypt hoặc text thường để bạn dễ test)
            if (password_verify($password, $admin['password']) || $password === $admin['password']) {
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_name'] = $admin['fullname'];
                header("Location: dashboard.php");
                exit();
            } else {
                $error = "Sai mật khẩu.";
            }
        }
    } else {
        $error = "Tên đăng nhập không tồn tại hoặc không có quyền quản trị.";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGUE - Admin Login</title>
    <style>
        /* CSS nội bộ tối giản dành riêng cho Admin Login */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        body { background-color: #f5f5f5; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .login-box { background: #fff; padding: 40px; width: 100%; max-width: 400px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); text-align: center; }
        .logo { font-family: 'Times New Roman', Times, serif; font-size: 32px; font-weight: 700; letter-spacing: 4px; text-transform: uppercase; margin-bottom: 30px; }
        .form-group { margin-bottom: 20px; text-align: left; }
        .form-group label { display: block; font-size: 14px; font-weight: bold; margin-bottom: 5px; text-transform: uppercase; letter-spacing: 1px;}
        .form-group input { width: 100%; padding: 10px; border: 1px solid #ccc; font-size: 14px; outline: none; transition: border 0.3s; }
        .form-group input:focus { border-color: #000; }
        .error-msg { color: #d9534f; font-size: 13px; margin-bottom: 15px; display: none; text-align: left; }
        .error-php { color: #d9534f; font-size: 13px; margin-bottom: 15px; text-align: left; }
        .btn-login { background: #000; color: #fff; border: none; padding: 12px; width: 100%; font-size: 16px; font-weight: bold; text-transform: uppercase; cursor: pointer; transition: opacity 0.3s; font-family: 'Times New Roman', Times, serif; letter-spacing: 1px; }
        .btn-login:hover { opacity: 0.8; }
        .back-link { display: inline-block; margin-top: 20px; font-size: 12px; color: #666; text-decoration: none; }
        .back-link:hover { color: #000; }
    </style>
</head>
<body>

<div class="login-box">
    <div class="logo">VOGUE<br><span style="font-size: 12px; letter-spacing: 2px; font-family: sans-serif; font-weight: normal;">Workspace</span></div>
    
    <?php if ($error): ?>
        <div class="error-php"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="error-msg" id="js-error"></div>

    <form action="index.php" method="POST" id="admin-login-form">
        <div class="form-group">
            <label for="username">Tên đăng nhập</label>
            <input type="text" name="username" id="username" autocomplete="off">
        </div>
        <div class="form-group">
            <label for="password">Mật khẩu</label>
            <input type="password" name="password" id="password">
        </div>
        <button type="submit" class="btn-login">Đăng nhập</button>
    </form>

    <a href="../index.php" class="back-link">&larr; Quay lại cửa hàng</a>
</div>

<script>
    // Kiểm tra dữ liệu (Validation) phía Client bằng JS trước khi gửi request
    document.getElementById('admin-login-form').addEventListener('submit', function(e) {
        let user = document.getElementById('username').value.trim();
        let pass = document.getElementById('password').value.trim();
        let errorDiv = document.getElementById('js-error');
        
        if (user === '' || pass === '') {
            e.preventDefault(); // Chặn không cho form gửi đi
            errorDiv.style.display = 'block';
            errorDiv.innerText = 'Vui lòng điền đầy đủ Tên đăng nhập và Mật khẩu!';
        } else {
            errorDiv.style.display = 'none';
        }
    });
</script>

</body>
</html>