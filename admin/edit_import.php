<?php
require_once 'inc_header.php';

if (!isset($_GET['id'])) {
    echo "<script>window.location.href='manage_imports.php';</script>";
    exit();
}

$receipt_id = (int)$_GET['id'];
$message = '';

// Lấy thông tin phiếu nhập
$receipt_query = $conn->query("SELECT * FROM import_receipts WHERE id = $receipt_id");
if ($receipt_query->num_rows == 0) {
    echo "<script>alert('Không tìm thấy phiếu nhập!'); window.location.href='manage_imports.php';</script>";
    exit();
}
$receipt = $receipt_query->fetch_assoc();
$is_completed = ($receipt['status'] == 'completed');

// --- XỬ LÝ THÊM SẢN PHẨM VÀO PHIẾU (Chỉ khi chưa hoàn thành) ---
if (!$is_completed && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_batch'])) {
    $product_id = (int)$_POST['product_id'];
    $import_price = (float)$_POST['import_price'];
    $quantity = (int)$_POST['quantity'];
    
    // Số lượng còn lại (quantity_remaining) lúc mới nhập sẽ bằng đúng số lượng nhập (quantity)
    $stmt = $conn->prepare("INSERT INTO import_batches (receipt_id, product_id, import_price, quantity_imported, quantity_remaining) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iidii", $receipt_id, $product_id, $import_price, $quantity, $quantity);
    
    if ($stmt->execute()) {
        $message = "<p style='color: #5cb85c; padding: 10px; border: 1px solid #5cb85c;'>Đã thêm sản phẩm vào lô nhập!</p>";
    } else {
        $message = "<p style='color: #d9534f; padding: 10px; border: 1px solid #d9534f;'>Lỗi CSDL: " . $conn->error . "</p>";
    }
}

// --- XỬ LÝ XOÁ SẢN PHẨM KHỎI PHIẾU ---
if (!$is_completed && isset($_GET['delete_batch'])) {
    $batch_id = (int)$_GET['delete_batch'];
    $conn->query("DELETE FROM import_batches WHERE id = $batch_id AND receipt_id = $receipt_id");
    header("Location: edit_import.php?id=$receipt_id");
    exit();
}

// --- XỬ LÝ CHỐT/HOÀN THÀNH PHIẾU & TÍNH BÌNH QUÂN GIA QUYỀN ---
if (!$is_completed && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_receipt'])) {
    
    // 1. Lấy danh sách các lô hàng có trong phiếu nhập này
    $batches = $conn->query("SELECT product_id, quantity_imported, import_price FROM import_batches WHERE receipt_id = $receipt_id");
    
    while ($b = $batches->fetch_assoc()) {
        $pid = $b['product_id'];
        $qty_new = (int)$b['quantity_imported'];
        $price_new = (float)$b['import_price'];
        
        // 2. Tính TỒN KHO HIỆN TẠI của sản phẩm (trước khi duyệt phiếu này)
        // Công thức: Tồn = SL ban đầu + Tổng Nhập (đã duyệt) - Tổng Xuất (đơn hàng thành công)
        $stock_query = $conn->query("
            SELECT 
                p.initial_quantity,
                p.profit_margin,
                p.cost_price,
                (SELECT COALESCE(SUM(quantity_imported), 0) FROM import_batches ib JOIN import_receipts ir ON ib.receipt_id = ir.id WHERE ir.status = 'completed' AND ib.product_id = p.id) as total_imported,
                (SELECT COALESCE(SUM(quantity), 0) FROM order_details od JOIN orders o ON od.order_id = o.id WHERE o.status != 'cancelled' AND od.product_id = p.id) as total_sold
            FROM products p 
            WHERE p.id = $pid
        ");
        
        if ($stock_query->num_rows > 0) {
            $p_data = $stock_query->fetch_assoc();
            
            // Lấy Tồn kho và Giá vốn cũ
            $current_stock = $p_data['initial_quantity'] + $p_data['total_imported'] - $p_data['total_sold'];
            if ($current_stock < 0) $current_stock = 0; // Đảm bảo không âm
            $current_cost = (float)$p_data['cost_price'];
            
            // 3. ÁP DỤNG CÔNG THỨC BÌNH QUÂN GIA QUYỀN TỪ BIÊN BẢN
            // Giá vốn mới = (Tồn * Giá vốn cũ + Nhập mới * Giá nhập mới) / (Tồn + Nhập mới)
            $total_qty_after = $current_stock + $qty_new;
            $new_cost_price = (($current_stock * $current_cost) + ($qty_new * $price_new)) / $total_qty_after;
            
            // 4. CẬP NHẬT GIÁ BÁN MỚI
            // Giá bán = Giá vốn mới * (100% + % Lợi nhuận)
            $profit_margin = (float)$p_data['profit_margin'];
            $new_selling_price = $new_cost_price * (1 + ($profit_margin / 100));
            
            // 5. Lưu Giá vốn và Giá bán mới vào bảng products
            $stmt_update = $conn->prepare("UPDATE products SET cost_price = ?, selling_price = ? WHERE id = ?");
            $stmt_update->bind_param("ddi", $new_cost_price, $new_selling_price, $pid);
            $stmt_update->execute();
        }
    }
    
    // 6. Cuối cùng, cập nhật trạng thái phiếu nhập thành 'completed'
    $conn->query("UPDATE import_receipts SET status = 'completed' WHERE id = $receipt_id");
    header("Location: edit_import.php?id=$receipt_id");
    exit();
}

// Lấy danh sách lô hàng đã thêm vào phiếu này
$batches = $conn->query("
    SELECT b.*, p.code, p.name 
    FROM import_batches b 
    JOIN products p ON b.product_id = p.id 
    WHERE b.receipt_id = $receipt_id
    ORDER BY b.id DESC
");

// Lấy danh sách sản phẩm để đưa vào Dropdown có tích hợp thanh tìm kiếm JS
$products = $conn->query("SELECT id, code, name FROM products WHERE status != 'deleted'");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Chi tiết Phiếu Nhập: PN-<?php echo str_pad($receipt_id, 5, '0', STR_PAD_LEFT); ?></h2>
    <a href="manage_imports.php" style="color: #666; text-decoration: none; font-size: 14px;">&larr; Quay lại danh sách</a>
</div>

<?php if ($message) echo "<div style='margin-bottom: 20px;'>$message</div>"; ?>

<?php if (!$is_completed): ?>
<div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 30px;">
    <h3 style="margin-bottom: 15px;">Thêm sản phẩm vào phiếu nhập</h3>
    <form action="edit_import.php?id=<?php echo $receipt_id; ?>" method="POST" id="form-add-batch" style="display: flex; gap: 15px; align-items: flex-end;">
        
        <div style="flex: 2; position: relative;">
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Chọn sản phẩm (Gõ để tìm nhanh) *</label>
            <input type="text" id="product_search" placeholder="Gõ tên hoặc mã SP..." style="width: 100%; padding: 8px; border: 1px solid #ccc; margin-bottom: 5px;" autocomplete="off">
            <select name="product_id" id="product_select" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                <option value="">-- Chọn sản phẩm --</option>
                <?php while($p = $products->fetch_assoc()): ?>
                    <option value="<?php echo $p['id']; ?>">[<?php echo $p['code']; ?>] <?php echo htmlspecialchars($p['name']); ?></option>
                <?php endwhile; ?>
            </select>
        </div>
        
        <div style="flex: 1;">
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Số lượng nhập *</label>
            <input type="number" name="quantity" id="b_qty" min="1" value="1" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>
        
        <div style="flex: 1;">
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Giá nhập/SP (VNĐ) *</label>
            <input type="number" name="import_price" id="b_price" min="0" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
        </div>
        
        <div>
            <button type="submit" name="add_batch" style="background: #000; color: #fff; border: none; padding: 9px 20px; text-transform: uppercase; cursor: pointer; font-family: 'Times New Roman', Times, serif;">Thêm</button>
        </div>
    </form>
    <p id="js-error-batch" style="color: #d9534f; font-size: 13px; display: none; margin-top: 10px;"></p>
</div>

<script>
    // 1. Chức năng tìm kiếm sản phẩm nhanh 
    document.getElementById('product_search').addEventListener('keyup', function() {
        let filter = this.value.toLowerCase();
        let options = document.getElementById('product_select').options;
        for (let i = 1; i < options.length; i++) {
            let text = options[i].text.toLowerCase();
            options[i].style.display = text.includes(filter) ? '' : 'none';
        }
    });

    // 2. Validation form 
    document.getElementById('form-add-batch').addEventListener('submit', function(e) {
        let product = document.getElementById('product_select').value;
        let qty = parseInt(document.getElementById('b_qty').value);
        let price = parseFloat(document.getElementById('b_price').value);
        let errorP = document.getElementById('js-error-batch');
        
        if (product === '') {
            e.preventDefault();
            errorP.style.display = 'block';
            errorP.innerText = 'Vui lòng chọn 1 sản phẩm!';
            return;
        }
        if (isNaN(qty) || qty <= 0) {
            e.preventDefault();
            errorP.style.display = 'block';
            errorP.innerText = 'Số lượng nhập phải lớn hơn 0!';
            return;
        }
        if (isNaN(price) || price < 0) {
            e.preventDefault();
            errorP.style.display = 'block';
            errorP.innerText = 'Giá nhập không được nhỏ hơn 0!';
            return;
        }
        errorP.style.display = 'none';
    });
</script>
<?php endif; ?>

<h3 style="margin-bottom: 15px;">Danh sách sản phẩm nhập</h3>
<table>
    <thead>
        <tr>
            <th>Mã SP</th>
            <th>Tên Sản Phẩm</th>
            <th>Số Lượng</th>
            <th>Giá Nhập (VNĐ)</th>
            <th>Thành Tiền</th>
            <?php if (!$is_completed): ?><th>Thao tác</th><?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php 
        $total_receipt_value = 0;
        if($batches->num_rows > 0): 
            while($row = $batches->fetch_assoc()): 
                $subtotal = $row['quantity_imported'] * $row['import_price'];
                $total_receipt_value += $subtotal;
        ?>
            <tr>
                <td style="font-weight: bold;"><?php echo $row['code']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td style="text-align: right;"><?php echo $row['quantity_imported']; ?></td>
                <td style="text-align: right;"><?php echo number_format($row['import_price'], 0, ',', '.'); ?>đ</td>
                <td style="text-align: right; font-weight: bold;"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</td>
                <?php if (!$is_completed): ?>
                <td style="text-align: center;">
                    <a href="edit_import.php?id=<?php echo $receipt_id; ?>&delete_batch=<?php echo $row['id']; ?>" style="color: #d9534f; font-size: 12px; text-decoration: underline;" onclick="return confirm('Xoá sản phẩm này khỏi phiếu nhập?');">Xoá</a>
                </td>
                <?php endif; ?>
            </tr>
            <?php endwhile; ?>
            
            <tr style="background-color: #f9f9f9; font-weight: bold;">
                <td colspan="4" style="text-align: right; text-transform: uppercase;">Tổng Giá Trị Phiếu Nhập:</td>
                <td style="text-align: right; color: #d9534f; font-size: 16px;"><?php echo number_format($total_receipt_value, 0, ',', '.'); ?>đ</td>
                <?php if (!$is_completed): ?><td></td><?php endif; ?>
            </tr>
        <?php else: ?>
            <tr><td colspan="6" style="text-align: center; padding: 20px;">Phiếu nhập đang trống. Vui lòng thêm sản phẩm!</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if (!$is_completed && $batches->num_rows > 0): ?>
<div style="margin-top: 30px; text-align: right; border-top: 1px solid #ddd; padding-top: 20px;">
    <form action="edit_import.php?id=<?php echo $receipt_id; ?>" method="POST" onsubmit="return confirm('CẢNH BÁO: Sau khi hoàn thành, bạn sẽ KHÔNG THỂ sửa phiếu nhập này nữa. Dữ liệu tồn kho sẽ được cập nhật. Bạn có chắc chắn?');">
        <button type="submit" name="complete_receipt" style="background: #5cb85c; color: #fff; border: none; padding: 12px 25px; text-transform: uppercase; cursor: pointer; font-family: 'Times New Roman', Times, serif; font-size: 16px; font-weight: bold;">Hoàn Thành Phiếu Nhập</button>
    </form>
</div>
<?php endif; ?>

<?php require_once 'inc_footer.php'; ?>