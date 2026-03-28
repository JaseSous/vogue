<?php
require_once 'inc_header.php';

$message = '';

// --- XỬ LÝ CẬP NHẬT TỈ LỆ LỢI NHUẬN ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_margin'])) {
    $product_id = (int)$_POST['product_id'];
    $new_margin = (float)$_POST['profit_margin'];
    
    if ($new_margin < 0) {
        $message = "<p style='color: #d9534f; padding: 10px; border: 1px solid #d9534f;'>Tỉ lệ lợi nhuận không được âm!</p>";
    } else {
        $stmt = $conn->prepare("UPDATE products SET profit_margin = ? WHERE id = ?");
        $stmt->bind_param("di", $new_margin, $product_id);
        if ($stmt->execute()) {
            $message = "<p style='color: #5cb85c; padding: 10px; border: 1px solid #5cb85c;'>Đã cập nhật tỉ lệ lợi nhuận thành công!</p>";
        } else {
            $message = "<p style='color: #d9534f; padding: 10px; border: 1px solid #d9534f;'>Lỗi CSDL: " . $conn->error . "</p>";
        }
    }
}

// --- LẤY DANH SÁCH LÔ HÀNG ĐANG TỒN KHO VÀ TÍNH GIÁ ---
// Chỉ lấy những lô hàng đã được "Hoàn thành" phiếu nhập và số lượng còn lại > 0
$sql = "
    SELECT 
        b.id as batch_id, 
        b.quantity_remaining, 
        b.import_price, 
        p.id as product_id,
        p.code, 
        p.name, 
        p.suggested_price, 
        p.profit_margin,
        r.import_date
    FROM import_batches b
    JOIN products p ON b.product_id = p.id
    JOIN import_receipts r ON b.receipt_id = r.id
    WHERE r.status = 'completed' AND b.quantity_remaining > 0
    ORDER BY p.id DESC, r.import_date ASC
";
$batches = $conn->query($sql);
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Quản Lý Giá Bán & Tồn Kho (Theo Lô)</h2>
</div>

<?php if ($message) echo "<div style='margin-bottom: 20px;'>$message</div>"; ?>

<div style="background: #fff; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px; font-size: 14px; color: #555;">
    <strong>Quy tắc tính giá:</strong><br>
    - Giá bán dự tính = Giá nhập * (100% + Tỷ lệ lợi nhuận).<br>
    - Giá niêm yết = Max(Giá bán dự tính, Giá bán đề xuất). Lô nào nhập trước sẽ được ưu tiên xuất bán trước.
</div>

<table>
    <thead>
        <tr>
            <th>Mã SP</th>
            <th>Tên Sản Phẩm</th>
            <th>Ngày Nhập Lô</th>
            <th style="color: #d9534f;">Tồn Kho (Lô)</th>
            <th>Giá Vốn (Nhập)</th>
            <th>Giá Đề Xuất</th>
            <th>% Lợi Nhuận</th>
            <th>Giá Bán Thực Tế</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if($batches->num_rows > 0): ?>
            <?php while($row = $batches->fetch_assoc()): 
                
                // THỰC HIỆN CÔNG THỨC TÍNH GIÁ CỦA ĐỒ ÁN
                // 1. Tính giá bán dựa trên lợi nhuận: Giá bán = giá nhập * (100% + tỷ lệ lợi nhuận)
                $calculated_price = $row['import_price'] * (1 + ($row['profit_margin'] / 100));
                
                // 2. Giá đăng bán là Max (Giá tính toán, Giá bán đề xuất)
                $final_selling_price = max($calculated_price, $row['suggested_price']);
            ?>
            <tr>
                <td style="font-weight: bold;"><?php echo $row['code']; ?></td>
                <td><?php echo htmlspecialchars($row['name']); ?></td>
                <td style="font-size: 12px; color: #666;"><?php echo date('d/m/Y', strtotime($row['import_date'])); ?></td>
                
                <td style="text-align: right; font-weight: bold; font-size: 16px; color: #d9534f;">
                    <?php echo $row['quantity_remaining']; ?>
                </td>
                
                <td style="text-align: right;"><?php echo number_format($row['import_price'], 0, ',', '.'); ?>đ</td>
                <td style="text-align: right; color: #888;"><?php echo number_format($row['suggested_price'], 0, ',', '.'); ?>đ</td>
                
                <td style="text-align: center;">
                    <form action="manage_prices.php" method="POST" style="display: flex; justify-content: center; align-items: center; gap: 5px;">
                        <input type="hidden" name="product_id" value="<?php echo $row['product_id']; ?>">
                        <input type="number" step="0.1" name="profit_margin" value="<?php echo $row['profit_margin']; ?>" style="width: 60px; padding: 5px; border: 1px solid #ccc; text-align: center;">
                        <span style="font-size: 12px;">%</span>
                </td>
                
                <td style="text-align: right; font-weight: bold; background: #f9f9f9; color: #000; font-size: 15px;">
                    <?php echo number_format($final_selling_price, 0, ',', '.'); ?>đ
                </td>
                
                <td style="text-align: center;">
                        <button type="submit" name="update_margin" style="background: #000; color: #fff; border: none; padding: 5px 10px; font-size: 11px; text-transform: uppercase; cursor: pointer;">Lưu</button>
                    </form>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="9" style="text-align: center; padding: 20px;">Không có sản phẩm nào tồn kho hoặc chưa có phiếu nhập nào được hoàn thành.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'inc_footer.php'; ?>