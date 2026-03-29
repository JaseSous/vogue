<?php
require_once 'includes/header.php';

// Nếu đã đăng nhập thì về trang chủ
if (isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Kiểm tra mật khẩu
        if (password_verify($password, $user['password'])) {
            
            // Chỉ cho phép role 'customer' đăng nhập ở trang này
            if ($user['role'] !== 'customer') {
                $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f; text-align: center;'>Tài khoản quản trị vui lòng đăng nhập tại trang Admin.</p>";
            } 
            // Kiểm tra xem tài khoản có bị khoá không
            elseif ($user['status'] == 'locked') {
                $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f; text-align: center;'>Tài khoản của bạn đã bị khoá. Vui lòng liên hệ CSKH.</p>";
            } 
            // Đăng nhập thành công
            else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_fullname'] = $user['fullname'];
                
                // Trở lại trang chủ (hoặc giỏ hàng nếu đang mua dở)
                echo "<script>window.location.href='index.php';</script>";
                exit();
            }
        } else {
            $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f; text-align: center;'>Mật khẩu không chính xác!</p>";
        }
    } else {
        $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f; text-align: center;'>Tài khoản không tồn tại!</p>";
    }
}
?>

<main style="padding: 60px 0; background-color: #f9f9f9; min-height: 70vh; display: flex; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 40px; width: 100%; max-width: 450px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        
        <h2 style="text-align: center; margin-bottom: 30px; letter-spacing: 2px; text-transform: uppercase;">Đăng Nhập</h2>
        
        <?php if ($message) echo "<div style='margin-bottom: 20px;'>$message</div>"; ?>

        <form action="login.php" method="POST" id="form-login" style="display: flex; flex-direction: column; gap: 20px;">
            
            <div>
                <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Tên đăng nhập *</label>
                <input type="text" name="username" id="l_username" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
            </div>

            <div>
                <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Mật khẩu *</label>
                <input type="password" name="password" id="l_password" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
            </div>

            <p id="js-error-login" style="color: #d9534f; font-size: 13px; display: none; text-align: center; margin-top: -5px;"></p>

            <button type="submit" name="login" style="background: #000; color: #fff; border: none; padding: 15px; font-family: 'Times New Roman', Times, serif; font-size: 16px; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; transition: 0.3s;">Đăng Nhập</button>
        </form>

        <div style="text-align: center; margin-top: 25px; font-size: 14px;">
            Chưa có tài khoản? <a href="register.php" style="font-weight: bold; text-decoration: underline;">Đăng ký ngay</a>
        </div>
    </div>
</main>

<script>
document.getElementById('form-login').addEventListener('submit', function(e) {
    let user = document.getElementById('l_username').value.trim();
    let pass = document.getElementById('l_password').value;
    let errorP = document.getElementById('js-error-login');
    
    if (user === '' || pass === '') {
        e.preventDefault();
        errorP.style.display = 'block';
        errorP.innerText = 'Vui lòng nhập đầy đủ Tên đăng nhập và Mật khẩu!';
    } else {
        errorP.style.display = 'none';
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>