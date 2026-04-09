<?php
require_once 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];
$message = '';

// --- XỬ LÝ CẬP NHẬT THÔNG TIN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    
    $address_line = trim($_POST['address_line']);
    $ward = trim($_POST['ward']);
    $district = trim($_POST['district']);
    $city = trim($_POST['city']);
    
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    $conn->begin_transaction();
    
    try {
        if (!preg_match('/^0\d{9}$/', $phone)) {
            throw new Exception("Số điện thoại không hợp lệ! Yêu cầu 10 số bắt đầu bằng số 0.");
        }

        // 1. Cập nhật Bảng users (Tên, SĐT, Mật khẩu nếu có nhập)
        if (!empty($new_password)) {
            if ($new_password !== $confirm_password) {
                throw new Exception("Mật khẩu xác nhận không khớp!");
            }
            if (strlen($new_password) < 6) {
                throw new Exception("Mật khẩu mới phải có ít nhất 6 ký tự!");
            }
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt_u = $conn->prepare("UPDATE users SET fullname = ?, phone = ?, password = ? WHERE id = ?");
            $stmt_u->bind_param("sssi", $fullname, $phone, $hashed_password, $user_id);
        } else {
            // Không đổi mật khẩu
            $stmt_u = $conn->prepare("UPDATE users SET fullname = ?, phone = ? WHERE id = ?");
            $stmt_u->bind_param("ssi", $fullname, $phone, $user_id);
        }
        $stmt_u->execute();

        // 2. Cập nhật Bảng addresses
        $check_addr = $conn->query("SELECT id FROM addresses WHERE user_id = $user_id AND is_default = 1");
        if ($check_addr->num_rows > 0) {
            $stmt_a = $conn->prepare("UPDATE addresses SET receiver_name = ?, receiver_phone = ?, address_line = ?, ward = ?, district = ?, city = ? WHERE user_id = ? AND is_default = 1");
            $stmt_a->bind_param("ssssssi", $fullname, $phone, $address_line, $ward, $district, $city, $user_id);
            $stmt_a->execute();
        } else {
            // Nếu tài khoản cũ chưa có địa chỉ thì thêm mới
            $stmt_a = $conn->prepare("INSERT INTO addresses (user_id, receiver_name, receiver_phone, address_line, ward, district, city, is_default) VALUES (?, ?, ?, ?, ?, ?, ?, 1)");
            $stmt_a->bind_param("issssss", $user_id, $fullname, $phone, $address_line, $ward, $district, $city);
            $stmt_a->execute();
        }

        $conn->commit();
        $message = "<p style='color: #5cb85c; background: #f4fdf4; padding: 10px; border: 1px solid #5cb85c; margin-bottom: 20px; text-align: center;'>Đã cập nhật hồ sơ thành công!</p>";

    } catch (Exception $e) {
        $conn->rollback();
        $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f; margin-bottom: 20px; text-align: center;'>Lỗi: " . $e->getMessage() . "</p>";
    }
}

// --- LẤY DỮ LIỆU ĐỂ HIỂN THỊ LÊN FORM ---
$sql_info = "SELECT u.username, u.email, u.fullname, u.phone, a.address_line, a.ward, a.district, a.city 
             FROM users u 
             LEFT JOIN addresses a ON u.id = a.user_id AND a.is_default = 1 
             WHERE u.id = $user_id";
$user_info = $conn->query($sql_info)->fetch_assoc();
?>

<main style="padding: 60px 0; background-color: #f9f9f9; min-height: 70vh; display: flex; justify-content: center;">
    <div style="background: #fff; padding: 40px; width: 100%; max-width: 600px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        
        <h2 style="text-align: center; margin-bottom: 30px; letter-spacing: 2px; text-transform: uppercase;">Hồ Sơ Của Tôi</h2>
        
        <?php if ($message) echo $message; ?>

        <form action="profile.php" method="POST" id="form-profile" style="display: flex; flex-direction: column; gap: 25px;">
            
            <div>
                <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 15px; color: #000; border-bottom: 1px solid #eee; padding-bottom: 8px; text-transform: uppercase;">1. Thông tin Đăng nhập</h4>
                <div style="display: flex; gap: 15px; margin-bottom: 12px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #666;">Tên đăng nhập</label>
                        <input type="text" value="<?php echo htmlspecialchars($user_info['username']); ?>" disabled style="width: 100%; padding: 12px; border: 1px solid #ccc; background: #f5f5f5; color: #888;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px; color: #666;">Email</label>
                        <input type="text" value="<?php echo htmlspecialchars($user_info['email']); ?>" disabled style="width: 100%; padding: 12px; border: 1px solid #ccc; background: #f5f5f5; color: #888;">
                    </div>
                </div>
            </div>

            <div>
                <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 15px; color: #000; border-bottom: 1px solid #eee; padding-bottom: 8px; text-transform: uppercase;">2. Thông tin Cá nhân</h4>
                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Họ và tên *</label>
                        <input type="text" name="fullname" value="<?php echo htmlspecialchars($user_info['fullname']); ?>" required style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Số điện thoại *</label>
                        <input type="text" name="phone" id="p_phone" value="<?php echo htmlspecialchars($user_info['phone']); ?>" required style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                </div>
            </div>

            <div>
                <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 15px; color: #000; border-bottom: 1px solid #eee; padding-bottom: 8px; text-transform: uppercase;">3. Địa chỉ giao nhận</h4>
                <div style="margin-bottom: 12px;">
                    <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Số nhà, Tên đường *</label>
                    <input type="text" name="address_line" value="<?php echo htmlspecialchars($user_info['address_line'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                </div>
                <div style="display: flex; gap: 10px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Phường/Xã *</label>
                        <input type="text" name="ward" value="<?php echo htmlspecialchars($user_info['ward'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Quận/Huyện *</label>
                        <input type="text" name="district" value="<?php echo htmlspecialchars($user_info['district'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Tỉnh/TP *</label>
                        <input type="text" name="city" value="<?php echo htmlspecialchars($user_info['city'] ?? ''); ?>" required style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                </div>
            </div>

            <div>
                <h4 style="margin-top: 0; margin-bottom: 15px; font-size: 15px; color: #000; border-bottom: 1px solid #eee; padding-bottom: 8px; text-transform: uppercase;">4. Đổi mật khẩu</h4>
                <div style="display: flex; gap: 15px;">
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Mật khẩu mới</label>
                        <input type="password" name="new_password" placeholder="Nhập mật khẩu mới..." style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                    <div style="flex: 1;">
                        <label style="display: block; font-size: 13px; font-weight: bold; margin-bottom: 5px;">Xác nhận mật khẩu mới</label>
                        <input type="password" name="confirm_password" placeholder="Nhập lại mật khẩu..." style="width: 100%; padding: 12px; border: 1px solid #ccc; outline: none; font-size: 14px;">
                    </div>
                </div>
            </div>

            <p id="js-error-profile" style="color: #d9534f; font-size: 13px; display: none; margin-top: 5px; text-align: center;"></p>
            <button type="submit" name="update_profile" style="background: #000; color: #fff; border: none; padding: 15px; font-family: 'Times New Roman', Times, serif; font-size: 16px; text-transform: uppercase; letter-spacing: 2px; cursor: pointer; margin-top: 10px; transition: 0.3s;">Lưu Thay Đổi</button>
        </form>
    </div>
</main>

<script>
document.getElementById('form-profile').addEventListener('submit', function(e) {
    let phone = document.getElementById('p_phone').value.trim();
    let errorP = document.getElementById('js-error-profile');
    
    const phoneRegex = /^0\d{9}$/;
    if (!phoneRegex.test(phone)) {
        e.preventDefault();
        errorP.style.display = 'block';
        errorP.innerText = 'Số điện thoại không hợp lệ! Yêu cầu 10 số bắt đầu bằng số 0.';
        return;
    }
    
    errorP.style.display = 'none';
});
</script>

<?php require_once 'includes/footer.php'; ?>