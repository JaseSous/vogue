<?php
require_once 'inc_header.php';

$message = '';

// --- LẤY DỮ LIỆU DANH MỤC & NHÀ CUNG CẤP CHO DROPDOWN ---
$categories = $conn->query("SELECT * FROM categories");
$suppliers = $conn->query("SELECT * FROM suppliers");

// --- XỬ LÝ THÊM SẢN PHẨM ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $code = trim($_POST['code']);
    $name = trim($_POST['name']);
    $category_id = (int)$_POST['category_id'];
    $supplier_id = (int)$_POST['supplier_id'];
    $description = trim($_POST['description']);
    $unit = trim($_POST['unit']);
    $initial_quantity = (int)$_POST['initial_quantity'];
    
    // Lấy Giá vốn, Lợi nhuận và tự động tính Giá bán
    $cost_price = (float)$_POST['cost_price'];
    $profit_margin = (float)$_POST['profit_margin'];
    $selling_price = $cost_price * (1 + ($profit_margin / 100));
    
    $status = $_POST['status'];

    // Xử lý Upload Ảnh
    $image_path = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/images/products/";
        $file_name = time() . '_' . basename($_FILES["image"]["name"]);
        $target_file = $target_dir . $file_name;
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        if(in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif', 'webp'])) {
            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                $image_path = "assets/images/products/" . $file_name;
            }
        } else {
            $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f;'>Chỉ chấp nhận file ảnh định dạng JPG, JPEG, PNG, GIF, WEBP.</p>";
        }
    }

    if(empty($message)) {
        // Kiểm tra trùng Mã sản phẩm
        $check = $conn->query("SELECT id FROM products WHERE code = '$code'");
        if ($check->num_rows > 0) {
            $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f;'>Mã sản phẩm đã tồn tại!</p>";
        } else {
            // Cập nhật CSDL với Giá vốn và Giá bán
            $stmt = $conn->prepare("INSERT INTO products (code, name, category_id, supplier_id, description, unit, initial_quantity, cost_price, selling_price, profit_margin, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            // ssiissidddss = 2 string, 2 int, 2 string, 1 int, 3 double, 2 string
            $stmt->bind_param("ssiissidddss", $code, $name, $category_id, $supplier_id, $description, $unit, $initial_quantity, $cost_price, $selling_price, $profit_margin, $image_path, $status);
            
            if ($stmt->execute()) {
                $message = "<p style='color: #5cb85c; background: #f4fdf4; padding: 10px; border: 1px solid #5cb85c;'>Thêm sản phẩm thành công!</p>";
            } else {
                $message = "<p style='color: #d9534f; background: #fdf7f7; padding: 10px; border: 1px solid #d9534f;'>Lỗi CSDL: " . $conn->error . "</p>";
            }
        }
    }
}

// --- LẤY DANH SÁCH SẢN PHẨM HIỂN THỊ ---
// 1. Xử lý từ khóa tìm kiếm
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_clause = "";

if ($search !== '') {
    $search_escaped = $conn->real_escape_string($search);
    // Điều kiện tìm theo Tên, Mã SP hoặc ID
    $search_clause = " AND (p.name LIKE '%$search_escaped%' OR p.code LIKE '%$search_escaped%' OR p.id = '$search_escaped') ";
}

// 2. JOIN với bảng categories và nối thêm điều kiện tìm kiếm ($search_clause)
$products = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.status != 'deleted' $search_clause ORDER BY p.id DESC");
?>

<h2 style="margin-bottom: 20px;">Quản Lý Sản Phẩm</h2>
<?php if ($message)
    echo "<div style='margin-bottom: 20px;'>$message</div>"; ?>

<div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 30px;">
    <h3 style="margin-bottom: 15px;">Thêm Sản Phẩm Mới</h3>
    <form action="manage_products.php" method="POST" enctype="multipart/form-data" id="form-add-product"
        style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">

        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Mã SP *</label>
            <input type="text" name="code" id="p_code" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>
        <div style="grid-column: span 2;">
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Tên sản phẩm
                *</label>
            <input type="text" name="name" id="p_name" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>

        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Danh mục *</label>
            <select name="category_id" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                <?php while ($c = $categories->fetch_assoc()): ?>
                    <option value="<?php echo $c['id']; ?>"><?php echo $c['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Nhà cung cấp
                *</label>
            <select name="supplier_id" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                <?php while ($s = $suppliers->fetch_assoc()): ?>
                    <option value="<?php echo $s['id']; ?>"><?php echo $s['name']; ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Đơn vị tính *</label>
            <input type="text" name="unit" id="p_unit" placeholder="Cái, Chiếc, Bộ..."
                style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>

        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Số lượng ban đầu
                *</label>
            <input type="number" name="initial_quantity" id="p_qty" min="0" value="0"
                style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Giá vốn (VNĐ)
                *</label>
            <input type="number" name="cost_price" id="p_cost" min="0"
                style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Tỉ lệ lợi nhuận (%)
                *</label>
            <input type="number" step="0.1" name="profit_margin" id="p_margin" min="0" placeholder="VD: 20"
                style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>

        <div style="grid-column: span 2;">
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Mô tả chi
                tiết</label>
            <textarea name="description" rows="3"
                style="width: 100%; padding: 8px; border: 1px solid #ccc; resize: vertical;"></textarea>
        </div>
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Hình ảnh đại
                diện</label>
            <input type="file" name="image" accept="image/*" style="width: 100%; padding: 5px; border: 1px solid #ccc;">

            <label
                style="display: block; font-size: 12px; font-weight: bold; margin-top: 10px; margin-bottom: 5px;">Trạng
                thái</label>
            <select name="status" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                <option value="visible">Hiển thị (Đang bán)</option>
                <option value="hidden">Ẩn (Không bán)</option>
            </select>
        </div>

        <div style="grid-column: span 3;">
            <p id="js-error-product" style="color: #d9534f; font-size: 13px; display: none; margin-bottom: 10px;"></p>
            <button type="submit" name="add_product"
                style="background: #000; color: #fff; border: none; padding: 10px 20px; font-family: 'Times New Roman', Times, serif; text-transform: uppercase; cursor: pointer;">Thêm
                Sản Phẩm</button>
        </div>
    </form>
</div>

<div
    style="margin-bottom: 20px; background: #fff; padding: 15px; border: 1px solid #ddd; display: flex; align-items: center;">
    <form action="manage_products.php" method="GET"
        style="display: flex; gap: 10px; margin: 0; width: 100%; max-width: 600px;">
        <input type="text" name="search" placeholder="Nhập ID, Mã SP hoặc Tên sản phẩm..."
            value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
            style="flex: 1; padding: 10px; border: 1px solid #ccc; outline: none; font-size: 14px;">

        <button type="submit"
            style="background: #000; color: #fff; border: none; padding: 0 20px; cursor: pointer; text-transform: uppercase;">
            Tìm kiếm
        </button>

        <?php if (isset($_GET['search']) && trim($_GET['search']) !== ''): ?>
            <a href="manage_products.php"
                style="background: #d9534f; color: #fff; text-decoration: none; padding: 10px 15px; display: flex; align-items: center; font-size: 14px;">Hủy</a>
        <?php endif; ?>
    </form>
</div>

<div style="overflow-x: auto;">
    <table style="min-width: 1000px;">
        <thead>
            <tr>
                <th>Ảnh</th>
                <th>Mã</th>
                <th>Tên Sản Phẩm</th>
                <th>Danh Mục</th>
                <th>SL Đầu</th>
                <th>Giá Bán</th>
                <th>Trạng thái</th>
                <th>Thao tác</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($products->num_rows > 0): ?>
                <?php while ($row = $products->fetch_assoc()): ?>
                    <tr>
                        <td style="text-align: center;">
                            <?php if ($row['image']): ?>
                                <img src="../<?php echo $row['image']; ?>" alt="Ảnh"
                                    style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #ddd;">
                            <?php else: ?>
                                <div
                                    style="width: 50px; height: 50px; background: #eee; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #999;">
                                    No IMG</div>
                            <?php endif; ?>
                        </td>
                        <td style="font-weight: bold;"><?php echo $row['code']; ?></td>
                        <td><?php echo htmlspecialchars($row['name']); ?></td>
                        <td><?php echo htmlspecialchars($row['category_name']); ?></td>
                        <td style="text-align: right;"><?php echo $row['initial_quantity']; ?>         <?php echo $row['unit']; ?></td>
                        <td style="text-align: right; font-weight: bold; color: #d9534f;">
                            <?php echo number_format($row['selling_price'], 0, ',', '.'); ?>đ
                        </td>
                        <td>
                            <?php if ($row['status'] == 'visible')
                                echo '<span style="color: #5cb85c; font-weight: bold;">Đang bán</span>';
                            else
                                echo '<span style="color: #999;">Đang ẩn</span>'; ?>
                        </td>
                        <td>
                            <a href="edit_product.php?id=<?php echo $row['id']; ?>"
                                style="color: #000; font-size: 12px; text-decoration: underline; margin-right: 10px;">Sửa</a>
                            <a href="delete_product.php?id=<?php echo $row['id']; ?>"
                                style="color: #d9534f; font-size: 12px; text-decoration: underline;"
                                onclick="return confirm('Bạn có chắc muốn xoá sản phẩm này?');">Xoá</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 20px;">Chưa có sản phẩm nào.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
    document.getElementById('form-add-product').addEventListener('submit', function (e) {
        let code = document.getElementById('p_code').value.trim();
        let name = document.getElementById('p_name').value.trim();
        let unit = document.getElementById('p_unit').value.trim();
        let price = document.getElementById('p_price').value.trim();
        let margin = document.getElementById('p_margin').value.trim();
        let errorP = document.getElementById('js-error-product');

        if (code === '' || name === '' || unit === '' || price === '' || margin === '') {
            e.preventDefault();
            errorP.style.display = 'block';
            errorP.innerText = 'Vui lòng điền đầy đủ các thông tin có dấu * !';
            return;
        }

        if (parseFloat(price) < 0 || parseFloat(margin) < 0) {
            e.preventDefault();
            errorP.style.display = 'block';
            errorP.innerText = 'Giá vốn và Tỉ lệ lợi nhuận không được là số âm!';
            return;
        }

        errorP.style.display = 'none';
    });
</script>

<?php require_once 'inc_footer.php'; ?>