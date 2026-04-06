<?php
require_once 'inc_header.php';

$message = '';

// 1. KIỂM TRA ID SẢN PHẨM TRUYỀN VÀO
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>window.location.href='manage_products.php';</script>";
    exit();
}
$product_id = (int)$_GET['id'];

// --- XỬ LÝ CẬP NHẬT SẢN PHẨM (KHI SUBMIT FORM) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_product'])) {
    $code = trim($_POST['code']);
    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $supplier_id = (int)$_POST['supplier_id'];
    $description = trim($_POST['description']);
    $unit = trim($_POST['unit']);
    $initial_quantity = (int)$_POST['initial_quantity'];
    $cost_price = (float)$_POST['cost_price'];
    $selling_price = $cost_price * (1 + ($profit_margin / 100)); // Tự động tính giá bán
    $profit_margin = (float)$_POST['profit_margin'];
    $status = $_POST['status']; // Đặc biệt chú ý cập nhật hiện trạng 
    $current_image = $_POST['current_image'];

    // Xử lý logic Hình ảnh (Gồm sửa & bỏ hình) 
    $image_path = $current_image; // Mặc định giữ nguyên ảnh cũ

    // Nếu người dùng tick vào ô "Bỏ hình" (Remove image)
    if (isset($_POST['remove_image']) && $_POST['remove_image'] == 'yes') {
        if (!empty($current_image) && file_exists("../" . $current_image)) {
            unlink("../" . $current_image); // Xoá file vật lý
        }
        $image_path = ''; // Cập nhật đường dẫn rỗng
    }

    // Nếu có upload ảnh mới (Sẽ ghi đè ảnh cũ kể cả khi có tick bỏ hình hay không)
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/products/";
        $file_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if (in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                // Xoá ảnh cũ đi nếu có ảnh mới tải lên để tránh rác server
                if (!empty($current_image) && file_exists("../" . $current_image) && $current_image != $image_path) {
                    unlink("../" . $current_image);
                }
                $image_path = "assets/images/products/" . $file_name;
            }
        } else {
            $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f;'>Chỉ chấp nhận file ảnh định dạng JPG, JPEG, PNG, GIF, WEBP.</p>";
        }
    }

    if (empty($message)) {
        // Kiểm tra trùng Mã sản phẩm (Loại trừ chính sản phẩm đang sửa)
        $check = $conn->query("SELECT id FROM products WHERE code = '$code' AND id != $product_id");
        if ($check->num_rows > 0) {
            $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f;'>Mã sản phẩm đã tồn tại ở sản phẩm khác!</p>";
        } else {
            // Cập nhật CSDL
            // Cập nhật CSDL
            $stmt = $conn->prepare("UPDATE products SET code=?, name=?, category_id=?, supplier_id=?, description=?, unit=?, initial_quantity=?, image=?, cost_price=?, selling_price=?, profit_margin=?, status=? WHERE id=?");
            $stmt->bind_param("ssiissisdddsi", $code, $name, $category_id, $supplier_id, $description, $unit, $initial_quantity, $image_path, $cost_price, $selling_price, $profit_margin, $status, $product_id);
            
            if ($stmt->execute()) {
                $message = "<p style='color: #5cb85c; background: #f4fdf4; padding: 10px; border: 1px solid #5cb85c;'>Cập nhật sản phẩm thành công!</p>";
            } else {
                $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f;'>Lỗi CSDL: " . $conn->error . "</p>";
            }
        }
    }
}

// 2. LẤY DỮ LIỆU SẢN PHẨM HIỆN TẠI ĐỂ ĐỔ VÀO FORM (Hiển thị đúng thông tin trước khi sửa) 
$product_query = $conn->query("SELECT * FROM products WHERE id = $product_id");
if ($product_query->num_rows == 0) {
    echo "<script>alert('Không tìm thấy sản phẩm!'); window.location.href='manage_products.php';</script>";
    exit();
}
$p = $product_query->fetch_assoc();

// Lấy danh mục và nhà cung cấp cho Dropdown
$categories = $conn->query("SELECT * FROM categories");
$suppliers = $conn->query("SELECT * FROM suppliers");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Sửa Sản Phẩm: <?php echo htmlspecialchars($p['name']); ?></h2>
    <a href="manage_products.php" style="color: #666; text-decoration: none; font-size: 14px;">&larr; Quay lại danh sách</a>
</div>

<?php if ($message) echo "<div style='margin-bottom: 20px;'>$message</div>"; ?>

<div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 30px;">
    <form action="edit_product.php?id=<?php echo $product_id; ?>" method="POST" enctype="multipart/form-data" id="form-edit-product" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
        
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Mã SP *</label>
            <input type="text" name="code" id="e_code" value="<?php echo htmlspecialchars($p['code']); ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>
        <div style="grid-column: span 2;">
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Tên sản phẩm *</label>
            <input type="text" name="name" id="e_name" value="<?php echo htmlspecialchars($p['name']); ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>
        
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Danh mục *</label>
            <select name="category_id" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                <?php while($c = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>" <?php echo ($c['id'] == $p['category_id']) ? 'selected' : ''; ?>><?php echo $c['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Nhà cung cấp *</label>
            <select name="supplier_id" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                <?php while($s = $suppliers->fetch_assoc()): ?>
                    <option value="<?php echo $s['id']; ?>" <?php echo ($s['id'] == $p['supplier_id']) ? 'selected' : ''; ?>><?php echo $s['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Đơn vị tính *</label>
            <input type="text" name="unit" id="e_unit" value="<?php echo htmlspecialchars($p['unit']); ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>

        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Số lượng ban đầu *</label>
            <input type="number" name="initial_quantity" id="e_qty" min="0" value="<?php echo $p['initial_quantity']; ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc;" readonly title="Số lượng ban đầu thường không nên sửa sau khi khởi tạo. Cần nhập hàng thì dùng tính năng Phiếu Nhập Kho.">
        </div>
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Giá vốn (VNĐ) *</label>
            <input type="number" name="cost_price" id="e_cost" min="0" value="<?php echo isset($p['cost_price']) ? $p['cost_price'] : 0; ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Tỉ lệ lợi nhuận (%) *</label>
            <input type="number" step="0.1" name="profit_margin" id="e_margin" min="0" value="<?php echo $p['profit_margin']; ?>" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>

        <div style="grid-column: span 2;">
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Mô tả chi tiết</label>
            <textarea name="description" rows="5" style="width: 100%; padding: 8px; border: 1px solid #ccc; resize: vertical;"><?php echo htmlspecialchars($p['description']); ?></textarea>
        </div>
        
        <div style="border: 1px dashed #ccc; padding: 10px;">
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Hình ảnh sản phẩm</label>
            
            <?php if (!empty($p['image'])): ?>
                <div style="margin-bottom: 10px;">
                    <img src="../<?php echo $p['image']; ?>" alt="Ảnh hiện tại" style="width: 80px; height: 80px; object-fit: cover; border: 1px solid #ddd;">
                    <br>
                    <label style="font-size: 12px; color: #d9534f; cursor: pointer;">
                        <input type="checkbox" name="remove_image" value="yes"> Bỏ hình ảnh này (Xoá ảnh)
                    </label>
                </div>
            <?php else: ?>
                <p style="font-size: 12px; color: #666; margin-bottom: 10px;">Chưa có ảnh.</p>
            <?php endif; ?>
            
            <input type="hidden" name="current_image" value="<?php echo $p['image']; ?>">
            
            <label style="display: block; font-size: 12px; margin-bottom: 5px;">Chọn ảnh mới để thay thế:</label>
            <input type="file" name="image" accept="image/*" style="width: 100%; font-size: 12px;">
        </div>

        <div style="grid-column: span 3; background: #f9f9f9; padding: 15px; border: 1px solid #eee;">
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; color: #000;">Hiện trạng sản phẩm *</label>
            <select name="status" style="width: 300px; padding: 8px; border: 1px solid #ccc;">
                <option value="visible" <?php echo ($p['status'] == 'visible') ? 'selected' : ''; ?>>Hiển thị (Đang bán)</option>
                <option value="hidden" <?php echo ($p['status'] == 'hidden') ? 'selected' : ''; ?>>Ẩn (Không bán)</option>
                <option value="deleted" <?php echo ($p['status'] == 'deleted') ? 'selected' : ''; ?> disabled>Đã xoá (Chỉ xem)</option>
            </select>
            <span style="font-size: 12px; color: #666; margin-left: 10px;">Lưu ý: Nếu chọn "Ẩn", sản phẩm sẽ không xuất hiện trên cửa hàng của khách.</span>
        </div>

        <div style="grid-column: span 3; margin-top: 10px;">
            <p id="js-error-edit" style="color: #d9534f; font-size: 13px; display: none; margin-bottom: 10px;"></p>
            <button type="submit" name="edit_product" style="background: #000; color: #fff; border: none; padding: 10px 20px; font-family: 'Times New Roman', Times, serif; text-transform: uppercase; cursor: pointer;">Lưu Thay Đổi</button>
        </div>
    </form>
</div>

<script>
document.getElementById('form-edit-product').addEventListener('submit', function(e) {
    let code = document.getElementById('e_code').value.trim();
    let name = document.getElementById('e_name').value.trim();
    let unit = document.getElementById('e_unit').value.trim();
    let cost = document.getElementById('e_cost').value.trim();
    let margin = document.getElementById('e_margin').value.trim();
    let errorP = document.getElementById('js-error-edit');
    
    if (code === '' || name === '' || unit === '' || cost === '' || margin === '') {
        e.preventDefault(); 
        errorP.style.display = 'block';
        errorP.innerText = 'Vui lòng điền đầy đủ các thông tin bắt buộc (*)!';
        return;
    }
    
    if (parseFloat(cost) < 0 || parseFloat(margin) < 0) {
        e.preventDefault();
        errorP.style.display = 'block';
        errorP.innerText = 'Giá vốn và Tỉ lệ lợi nhuận không được là số âm!';
        return;
    }
    
    errorP.style.display = 'none';
});
</script>

<?php require_once 'inc_footer.php'; ?>