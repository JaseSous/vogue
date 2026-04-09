<?php
require_once 'inc_header.php';

$message = '';

// --- XỬ LÝ THÊM DANH MỤC ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = trim($_POST['name']);

    // Kiểm tra xem tên danh mục đã tồn tại chưa để tránh trùng lặp
    $check = $conn->prepare("SELECT id FROM categories WHERE name = ?");
    $check->bind_param("s", $name);
    $check->execute();
    
    if ($check->get_result()->num_rows > 0) {
        $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f;'>Tên danh mục này đã tồn tại!</p>";
    } else {
        // Đã xóa cột description khỏi câu lệnh INSERT
        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $name);
        
        if ($stmt->execute()) {
            $message = "<p style='color: #5cb85c; background: #f4fdf4; padding: 10px; border: 1px solid #5cb85c;'>Thêm danh mục thành công!</p>";
        } else {
            $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f;'>Lỗi hệ thống: " . $conn->error . "</p>";
        }
    }
}

// --- LẤY DANH SÁCH DANH MỤC ---
$categories = $conn->query("SELECT * FROM categories ORDER BY id DESC");
?>

<h2 style="margin-bottom: 20px;">Quản Lý Danh Mục Sản Phẩm</h2>

<?php if ($message) echo "<div style='margin-bottom: 20px;'>$message</div>"; ?>

<div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 30px; max-width: 600px;">
    <h3 style="margin-bottom: 15px;">Thêm Danh Mục Mới</h3>
    
    <form action="manage_categories.php" method="POST" id="form-add-category" style="display: flex; flex-direction: column; gap: 15px;">
        <div>
            <label style="display: block; font-size: 12px; text-transform: uppercase; font-weight: bold; margin-bottom: 5px;">Tên danh mục *</label>
            <input type="text" name="name" id="c_name" placeholder="VD: Áo Sơ Mi Nam, Quần Tây Nữ..." style="width: 100%; padding: 8px; border: 1px solid #ccc; outline: none;">
        </div>
        
        <div>
            <p id="js-error-category" style="color: #d9534f; font-size: 13px; display: none; margin-bottom: 10px;"></p>
            <button type="submit" name="add_category" style="background: #000; color: #fff; border: none; padding: 10px 20px; font-family: 'Times New Roman', Times, serif; text-transform: uppercase; letter-spacing: 1px; cursor: pointer; transition: 0.3s;">Thêm danh mục</button>
        </div>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th style="width: 50px;">ID</th>
            <th>Tên Danh Mục</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($categories->num_rows > 0): ?>
            <?php while($row = $categories->fetch_assoc()): ?>
            <tr>
                <td style="text-align: center;"><?php echo $row['id']; ?></td>
                <td style="font-weight: bold;"><?php echo htmlspecialchars($row['name']); ?></td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="2" style="text-align: center; padding: 20px; color: #666;">Chưa có danh mục nào. Hãy thêm danh mục đầu tiên!</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<script>
document.getElementById('form-add-category').addEventListener('submit', function(e) {
    let name = document.getElementById('c_name').value.trim();
    let errorP = document.getElementById('js-error-category');
    
    // Yêu cầu tên danh mục không được để trống
    if (name === '') {
        e.preventDefault(); // Ngăn chặn gửi request về máy chủ
        errorP.style.display = 'block';
        errorP.innerText = 'Vui lòng nhập tên danh mục!';
    } else {
        errorP.style.display = 'none';
    }
});
</script>

<?php require_once 'inc_footer.php'; ?>