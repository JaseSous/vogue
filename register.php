<?php
require_once 'includes/header.php';

// Nếu đã đăng nhập thì chuyển hướng về trang chủ
if (isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='index.php';</script>";
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    
    // Nhận thêm dữ liệu địa chỉ từ form
    $address_line = trim($_POST['address_line']);
    $ward = trim($_POST['ward']);
    $district = trim($_POST['district']);
    $city = trim($_POST['city']);

    // Kiểm tra xem username hoặc email đã tồn tại chưa
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $username, $email);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f; margin-bottom: 20px; text-align: center;'>Tên đăng nhập hoặc Email đã được sử dụng!</p>";
    } elseif (!preg_match('/^0\d{9}$/', $phone)) {
        $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f; margin-bottom: 20px; text-align: center;'>Số điện thoại không hợp lệ! Yêu cầu 10 số bắt đầu bằng số 0.</p>";
    } else {
        // Mã hóa mật khẩu
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'customer';
        
        // Bắt đầu Transaction: Đảm bảo lưu thành công vào CẢ 2 BẢNG mới tính là đăng ký thành công
        $conn->begin_transaction();
        
        try {
            // Bước 1: Lưu thông tin tài khoản vào bảng users
            $stmt_user = $conn->prepare("INSERT INTO users (username, password, fullname, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_user->bind_param("ssssss", $username, $hashed_password, $fullname, $email, $phone, $role);
            $stmt_user->execute();
            
            $new_user_id = $conn->insert_id; // Lấy ID của user vừa được tạo
            
            // Bước 2: Lưu địa chỉ vào bảng addresses và set làm mặc định (is_default = 1)
            $stmt_addr = $conn->prepare("INSERT INTO addresses (user_id, receiver_name, receiver_phone, address_line, ward, district, city, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt_addr->bind_param("issssss", $new_user_id, $fullname, $phone, $address_line, $ward, $district, $city);
            $stmt_addr->execute();
            
            // Chốt giao dịch (Xác nhận lưu cả 2)
            $conn->commit();
            
            echo "<script>alert('Đăng ký thành công! Vui lòng đăng nhập.'); window.location.href='login.php';</script>";
            exit();
            
        } catch (Exception $e) {
            $conn->rollback(); // Nếu có bất kỳ lỗi nào, hoàn tác toàn bộ (không tạo user rác)
            $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f; margin-bottom: 20px; text-align: center;'>Lỗi hệ thống: " . $e->getMessage() . "</p>";
        }
    }
}
?>

<main style="padding: 60px 0; background-color: #f9f9f9; min-height: 70vh; display: flex; align-items: center; justify-content: center;">
    <div style="background: #fff; padding: 40px; width: 100%; max-width: 600px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        
        <h2 style="text-align: center; margin-bottom: 30px; letter-spacing: 2px; text-transform: uppercase;">Tạo Tài Khoản</h2>
        
        <?php if ($message) echo $message; ?>

        <form action="register.php" method="POST" id="form-register" style="display: flex; flex-direction: column; gap: 25px;">
            
            <div>
                <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 15px; color: #000; border-bottom: 1px solid #eee; padding-bottom: 8px; text-transform: uppercase;">1. Thông tin Tài khoản</h4>
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Tên đăng nhập *</label>
                    <input type="text" name="username" id="r_username" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                </div>

                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Mật khẩu *</label>
                        <input type="password" name="password" id="r_password" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Xác nhận mật khẩu *</label>
                        <input type="password" id="r_repassword" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                </div>
            </div>

            <div>
                <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 15px; color: #000; border-bottom: 1px solid #eee; padding-bottom: 8px; text-transform: uppercase;">2. Thông tin Cá nhân</h4>
                <div style="display: flex; gap: 15px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Họ và tên *</label>
                        <input type="text" name="fullname" id="r_fullname" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Số điện thoại *</label>
                        <input type="text" name="phone" id="r_phone" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                </div>
                <div>
                    <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Email *</label>
                    <input type="email" name="email" id="r_email" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                </div>
            </div>

            <div>
                <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 15px; color: #000; border-bottom: 1px solid #eee; padding-bottom: 8px; text-transform: uppercase;">3. Địa chỉ giao hàng</h4>
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Số nhà, Tên đường *</label>
                    <input type="text" name="address_line" id="r_address" placeholder="VD: Số 123 Đường ABC" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                </div>

                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Phường/Xã *</label>
                        <input type="text" name="ward" id="r_ward" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Quận/Huyện *</label>
                        <input type="text" name="district" id="r_district" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Tỉnh/Thành phố *</label>
                        <input type="text" name="city" id="r_city" style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                </div>
            </div>

            <p id="js-error-register" style="color: #d9534f; font-size: 13px; display: none; margin-top: 5px; text-align: center;"></p>

            <button type="submit" name="register" style="background: #000; color: #fff; border: none; padding: 15px; font-family: 'Times New Roman', Times, serif; font-size: 16px; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; margin-top: 10px; transition: 0.3s;">Đăng Ký Tài Khoản</button>
        </form>

        <div style="text-align: center; margin-top: 25px; font-size: 14px;">
            Đã có tài khoản? <a href="login.php" style="font-weight: bold; text-decoration: underline;">Đăng nhập tại đây</a>
        </div>
    </div>
</main>

<script>
document.getElementById('form-register').addEventListener('submit', function(e) {
    let fullname = document.getElementById('r_fullname').value.trim();
    let phone = document.getElementById('r_phone').value.trim();
    let email = document.getElementById('r_email').value.trim();
    
    // Lấy dữ liệu địa chỉ
    let address = document.getElementById('r_address').value.trim();
    let ward = document.getElementById('r_ward').value.trim();
    let district = document.getElementById('r_district').value.trim();
    let city = document.getElementById('r_city').value.trim();

    let username = document.getElementById('r_username').value.trim();
    let pass = document.getElementById('r_password').value;
    let repass = document.getElementById('r_repassword').value;
    let errorP = document.getElementById('js-error-register');
    
    // Kiểm tra rỗng (Cả phần địa chỉ)
    if (fullname === '' || phone === '' || email === '' || address === '' || ward === '' || district === '' || city === '' || username === '' || pass === '' || repass === '') {
        e.preventDefault();
        errorP.style.display = 'block';
        errorP.innerText = 'Vui lòng điền đầy đủ tất cả các trường!';
        return;
    }

    const phoneRegex = /^0\d{9}$/;
    if (!phoneRegex.test(phone)) {
        e.preventDefault();
        errorP.style.display = 'block';
        errorP.innerText = 'Số điện thoại không hợp lệ! Yêu cầu 10 số bắt đầu bằng số 0.';
        return;
    }
    
    // Kiểm tra độ dài mật khẩu
    if (pass.length < 6) {
        e.preventDefault();
        errorP.style.display = 'block';
        errorP.innerText = 'Mật khẩu phải có ít nhất 6 ký tự!';
        return;
    }

    // Kiểm tra khớp mật khẩu
    if (pass !== repass) {
        e.preventDefault();
        errorP.style.display = 'block';
        errorP.innerText = 'Mật khẩu xác nhận không khớp!';
        return;
    }

    errorP.style.display = 'none';
});
</script>

<?php require_once 'includes/footer.php'; ?>