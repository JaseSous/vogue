<?php
require_once 'inc_header.php';

// Biến lưu thông báo để hiển thị ra màn hình
$message = '';

// --- 1. XỬ LÝ THÊM NGƯỜI DÙNG ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    $username = trim($_POST['username']);
    $password = password_hash(trim($_POST['password']), PASSWORD_DEFAULT); // Mã hóa mật khẩu cho bảo mật
    $fullname = trim($_POST['fullname']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $role = $_POST['role'];

    // Kiểm tra xem username đã tồn tại trong CSDL chưa
    $check = $conn->query("SELECT id FROM users WHERE username = '$username'");
    if ($check->num_rows > 0) {
        $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f;'>Tên đăng nhập đã tồn tại! Vui lòng chọn tên khác.</p>";
    } else {
        $stmt = $conn->prepare("INSERT INTO users (username, password, fullname, email, phone, role) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $username, $password, $fullname, $email, $phone, $role);
        if ($stmt->execute()) {
            $message = "<p style='color: #5cb85c; background: #f4fdf4; padding: 10px; border: 1px solid #5cb85c;'>Thêm người dùng thành công!</p>";
        } else {
            $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f;'>Lỗi CSDL: " . $conn->error . "</p>";
        }
    }
}

// --- 2. XỬ LÝ KHOÁ TÀI KHOẢN & KHỞI TẠO MẬT KHẨU ---
if (isset($_GET['action']) && isset($_GET['id'])) {
    $target_id = (int)$_GET['id'];
    $action = $_GET['action'];

    // Bảo vệ: Admin không thể tự khoá hoặc tự reset tài khoản của chính mình khi đang đăng nhập
    if ($target_id === $_SESSION['admin_id']) {
         $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f;'>Bạn không thể thực hiện thao tác này trên chính tài khoản của mình!</p>";
    } else {
        if ($action == 'toggle_status') {
            // Đảo ngược trạng thái: Nếu active thì đổi thành locked và ngược lại
            $conn->query("UPDATE users SET status = IF(status='active', 'locked', 'active') WHERE id = $target_id");
            $message = "<p style='color: #5cb85c; background: #f4fdf4; padding: 10px; border: 1px solid #5cb85c;'>Đã cập nhật trạng thái tài khoản!</p>";
        } elseif ($action == 'reset_pass') {
            // Khởi tạo lại mật khẩu mặc định là '123456'
            $new_pass = password_hash('123456', PASSWORD_DEFAULT);
            $conn->query("UPDATE users SET password = '$new_pass' WHERE id = $target_id");
            $message = "<p style='color: #5cb85c; background: #f4fdf4; padding: 10px; border: 1px solid #5cb85c;'>Đã khởi tạo lại mật khẩu thành '123456'!</p>";
        }
    }
}

// Lấy danh sách tất cả người dùng (Hiển thị người mới nhất lên đầu)
$users = $conn->query("SELECT * FROM users ORDER BY id DESC");
?>

<h2 style="margin-bottom: 20px;">Quản Lý Người Dùng</h2>

<?php if ($message) echo "<div style='margin-bottom: 20px;'>$message</div>"; ?>

<div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 30px;">
    <h3 style="margin-bottom: 15px;">Thêm Tài Khoản Mới</h3>
    
    <form action="manage_users.php" method="POST" id="form-add-user" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
        <div>
            <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Tên đăng nhập *</label>
            <input type="text" name="username" id="u_username" style="width: 100%; padding: 8px; border: 1px solid #ccc; outline: none;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Mật khẩu *</label>
            <input type="password" name="password" id="u_password" style="width: 100%; padding: 8px; border: 1px solid #ccc; outline: none;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Họ và Tên *</label>
            <input type="text" name="fullname" id="u_fullname" style="width: 100%; padding: 8px; border: 1px solid #ccc; outline: none;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Email *</label>
            <input type="email" name="email" id="u_email" style="width: 100%; padding: 8px; border: 1px solid #ccc; outline: none;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Số điện thoại *</label>
            <input type="text" name="phone" id="u_phone" style="width: 100%; padding: 8px; border: 1px solid #ccc; outline: none;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Phân quyền</label>
            <select name="role" style="width: 100%; padding: 8px; border: 1px solid #ccc; outline: none;">
                <option value="customer">Khách hàng (Customer)</option>
                <option value="admin">Quản trị viên (Admin)</option>
            </select>
        </div>
        
        <div style="grid-column: span 2;">
            <p id="js-error-user" style="color: #d9534f; font-size: 13px; display: none; margin-bottom: 10px;"></p>
            <button type="submit" name="add_user" style="background: #000; color: #fff; border: none; padding: 10px 20px; font-family: 'Times New Roman', Times, serif; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.3s;">Thêm tài khoản</button>
        </div>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Tên đăng nhập</th>
            <th>Họ tên</th>
            <th>Liên hệ</th>
            <th>Vai trò</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php while($row = $users->fetch_assoc()): ?>
        <tr>
            <td><?php echo $row['id']; ?></td>
            <td><?php echo htmlspecialchars($row['username']); ?></td>
            <td><?php echo htmlspecialchars($row['fullname']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?><br><span style="color: #666; font-size: 12px;"><?php echo htmlspecialchars($row['phone']); ?></span></td>
            <td>
                <?php if($row['role'] == 'admin') echo '<strong>Admin</strong>'; else echo 'Khách hàng'; ?>
            </td>
            <td>
                <?php if($row['status'] == 'active'): ?>
                    <span style="color: #5cb85c; font-weight: bold;">Hoạt động</span>
                <?php else: ?>
                    <span style="color: #d9534f; font-weight: bold;">Đã khoá</span>
                <?php endif; ?>
            </td>
            <td>
                <?php if ($row['id'] !== $_SESSION['admin_id']): ?>
                    <a href="manage_users.php?action=toggle_status&id=<?php echo $row['id']; ?>" style="color: <?php echo $row['status'] == 'active' ? '#d9534f' : '#5cb85c'; ?>; font-size: 12px; text-transform: uppercase; text-decoration: underline; margin-right: 15px;">
                        <?php echo $row['status'] == 'active' ? 'Khoá tài khoản' : 'Mở khoá'; ?>
                    </a>
                    <a href="manage_users.php?action=reset_pass&id=<?php echo $row['id']; ?>" onclick="return confirm('Bạn có chắc chắn muốn khởi tạo lại mật khẩu tài khoản này về 123456?');" style="color: #000; font-size: 12px; text-transform: uppercase; text-decoration: underline;">
                        Reset Pass
                    </a>
                <?php else: ?>
                    <span style="font-size: 12px; color: #999;">Bạn (Đang đăng nhập)</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<script>
document.getElementById('form-add-user').addEventListener('submit', function(e) {
    let user = document.getElementById('u_username').value.trim();
    let pass = document.getElementById('u_password').value.trim();
    let name = document.getElementById('u_fullname').value.trim();
    let email = document.getElementById('u_email').value.trim();
    let phone = document.getElementById('u_phone').value.trim();
    let errorP = document.getElementById('js-error-user');
    
    // Kiểm tra rỗng
    if (user === '' || pass === '' || name === '' || email === '' || phone === '') {
        e.preventDefault(); // Ngăn chặn gửi dữ liệu về máy chủ
        errorP.style.display = 'block';
        errorP.innerText = 'Vui lòng điền đầy đủ tất cả các trường có dấu * !';
        return;
    } 
    
    // Kiểm tra độ dài mật khẩu
    if (pass.length < 6) {
        e.preventDefault();
        errorP.style.display = 'block';
        errorP.innerText = 'Mật khẩu phải có ít nhất 6 ký tự để đảm bảo an toàn!';
        return;
    }

    // Nếu mọi thứ OK, ẩn lỗi đi và cho form submit bình thường
    errorP.style.display = 'none';
});
</script>

<?php require_once 'inc_footer.php'; ?>