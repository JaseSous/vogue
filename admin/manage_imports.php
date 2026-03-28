<?php
require_once 'inc_header.php';

// --- XỬ LÝ TẠO PHIẾU NHẬP MỚI ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_receipt'])) {
    $admin_id = $_SESSION['admin_id'];
    
    // Tạo 1 phiếu nhập rỗng với trạng thái 'pending'
    $stmt = $conn->prepare("INSERT INTO import_receipts (created_by, status) VALUES (?, 'pending')");
    $stmt->bind_param("i", $admin_id);
    
    if ($stmt->execute()) {
        $new_id = $conn->insert_id;
        // Chuyển hướng sang trang chi tiết (edit_import.php) để thêm sản phẩm
        echo "<script>window.location.href='edit_import.php?id=$new_id';</script>";
        exit();
    } else {
        $message = "<p style='color: #d9534f; padding: 10px; border: 1px solid #d9534f;'>Lỗi tạo phiếu: " . $conn->error . "</p>";
    }
}

// Lấy danh sách phiếu nhập
$receipts = $conn->query("
    SELECT r.*, u.fullname, 
           (SELECT SUM(quantity_imported) FROM import_batches WHERE receipt_id = r.id) as total_qty,
           (SELECT SUM(quantity_imported * import_price) FROM import_batches WHERE receipt_id = r.id) as total_value
    FROM import_receipts r 
    JOIN users u ON r.created_by = u.id 
    ORDER BY r.id DESC
");
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Quản Lý Nhập Hàng</h2>
    <form action="manage_imports.php" method="POST">
        <button type="submit" name="create_receipt" style="background: #000; color: #fff; border: none; padding: 10px 20px; font-family: 'Times New Roman', Times, serif; text-transform: uppercase; cursor: pointer;">+ Tạo phiếu nhập mới</button>
    </form>
</div>

<?php if (isset($message)) echo "<div style='margin-bottom: 20px;'>$message</div>"; ?>

<table>
    <thead>
        <tr>
            <th>Mã Phiếu</th>
            <th>Ngày Lập</th>
            <th>Người Lập</th>
            <th>Tổng SP</th>
            <th>Tổng Tiền</th>
            <th>Trạng Thái</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if($receipts->num_rows > 0): ?>
            <?php while($row = $receipts->fetch_assoc()): ?>
            <tr>
                <td style="font-weight: bold;">PN-<?php echo str_pad($row['id'], 5, '0', STR_PAD_LEFT); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($row['import_date'])); ?></td>
                <td><?php echo htmlspecialchars($row['fullname']); ?></td>
                <td style="text-align: right;"><?php echo $row['total_qty'] ? $row['total_qty'] : 0; ?></td>
                <td style="text-align: right; font-weight: bold; color: #d9534f;">
                    <?php echo $row['total_value'] ? number_format($row['total_value'], 0, ',', '.') . 'đ' : '0đ'; ?>
                </td>
                <td>
                    <?php if($row['status'] == 'completed'): ?>
                        <span style="color: #5cb85c; font-weight: bold;">Đã hoàn thành</span>
                    <?php else: ?>
                        <span style="color: #f0ad4e; font-weight: bold;">Đang xử lý</span>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="edit_import.php?id=<?php echo $row['id']; ?>" style="color: #000; font-size: 12px; text-decoration: underline;">
                        <?php echo $row['status'] == 'pending' ? 'Sửa / Nhập hàng' : 'Xem chi tiết'; ?>
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align: center; padding: 20px;">Chưa có phiếu nhập nào.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'inc_footer.php'; ?>