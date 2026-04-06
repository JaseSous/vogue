<?php
require_once 'inc_header.php';

$message = '';

// --- XỬ LÝ CẬP NHẬT TỈ LỆ LỢI NHUẬN (VÀ TÍNH LẠI GIÁ BÁN) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_margin'])) {
    $product_id = (int)$_POST['product_id'];
    $new_margin = (float)$_POST['profit_margin'];
    $current_cost = (float)$_POST['cost_price'];
    
    if ($new_margin < 0) {
        $message = "<p style='color: #d9534f; padding: 10px; border: 1px solid #d9534f;'>Tỉ lệ lợi nhuận không được âm!</p>";
    } else {
        // Tự động tính lại Giá bán mới dựa trên Giá vốn hiện tại và Lợi nhuận mới
        $new_selling_price = $current_cost * (1 + ($new_margin / 100));

        $stmt = $conn->prepare("UPDATE products SET profit_margin = ?, selling_price = ? WHERE id = ?");
        $stmt->bind_param("ddi", $new_margin, $new_selling_price, $product_id);
        if ($stmt->execute()) {
            $message = "<p style='color: #5cb85c; padding: 10px; border: 1px solid #5cb85c;'>Đã cập nhật tỷ lệ lợi nhuận và Giá bán mới thành công!</p>";
        } else {
            $message = "<p style='color: #d9534f; padding: 10px; border: 1px solid #d9534f;'>Lỗi CSDL: " . $conn->error . "</p>";
        }
    }
}

// --- LẤY DANH SÁCH TẤT CẢ SẢN PHẨM HIỂN THỊ (BÌNH QUÂN GIA QUYỀN) ---
$sql = "
    SELECT 
        id as product_id,
        code, 
        name, 
        cost_price, 
        selling_price, 
        profit_margin,
        status
    FROM products
    WHERE status != 'deleted'
    ORDER BY id DESC
";
$products = $conn->query($sql);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Quản Lý Giá Bán</h2>
</div>

<?php if ($message) echo "<div style='margin-bottom: 20px;'>$message</div>"; ?>

<div style="background: #fff; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px; font-size: 14px; color: #555;">
    <strong>Quy tắc tính giá hiện tại:</strong><br>
    - Giá bán = Giá vốn * (100% + Tỷ lệ lợi nhuận).<br>
    - Khi Admin điều chỉnh Tỷ lệ lợi nhuận ở dưới, hệ thống sẽ tự động tính lại Giá bán tương ứng.
</div>

<table>
    <thead>
        <tr>
            <th>Mã SP</th>
            <th>Tên Sản Phẩm</th>
            <th>Trạng thái</th>
            <th>Giá Vốn (BQGQ)</th>
            <th>Giá Bán (Hệ thống tính)</th>
            <th style="width: 200px;">% Lợi Nhuận (Có thể sửa)</th>
        </tr>
    </thead>
    <tbody>
        <?php if($products->num_rows > 0): ?>
            <?php while($row = $products->fetch_assoc()): ?>
            <tr>
                <td style="font-weight: bold;"><?php echo $row['code']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td>
                    <?php if ($row['status'] == 'visible') echo '<span style="color: #5cb85c;">Đang bán</span>';
                    else echo '<span style="color: #999;">Đang ẩn</span>'; ?>
                </td>
                
                <td style="text-align: right; color: #888;">
                    <?php echo number_format($row['cost_price'], 0, ',', '.'); ?>đ
                </td>
                
                <td style="text-align: right; font-weight: bold; background: #f9f9f9; color: #d9534f; font-size: 15px;">
                    <?php echo number_format($row['selling_price'], 0, ',', '.'); ?>đ
                </td>
                
                <td style="text-align: center;">
                    <form action="manage_prices.php" method="POST" style="display: flex; justify-content: center; align-items: center; gap: 5px;">
                        <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                        <input type="hidden" name="cost_price" value="<?php echo $row['cost_price']; ?>">
                        
                        <input type="number" step="0.1" name="profit_margin" value="<?php echo $row['profit_margin']; ?>" style="width: 70px; padding: 5px; border: 1px solid #ccc; text-align: center;">
                        <span style="font-size: 12px; margin-right: 10px;">%</span>
                        
                        <button type="submit" name="update_margin" style="background: #000; color: #fff; border: none; padding: 5px 10px; font-size: 11px; text-transform: uppercase; cursor: pointer;">Cập nhật</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="6" style="text-align: center; padding: 20px;">Không có sản phẩm nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'inc_footer.php'; ?>