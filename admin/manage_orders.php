<?php
require_once 'inc_header.php';

$message = '';

// --- XỬ LÝ CẬP NHẬT TRẠNG THÁI ĐƠN HÀNG ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $order_id);
    if ($stmt->execute()) {
        $message = "<p style='color: #5cb85c; padding: 10px; border: 1px solid #5cb85c;'>Cập nhật trạng thái đơn hàng #$order_id thành công!</p>";
    } else {
        $message = "<p style='color: #d9534f; padding: 10px; border: 1px solid #d9534f;'>Lỗi CSDL: " . $conn->error . "</p>";
    }
}

// --- XỬ LÝ BỘ LỌC VÀ SẮP XẾP (GET Request) ---
$where_clauses = [];
$params = [];
$types = "";

// Lọc theo khoảng thời gian
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : '';
if (!empty($from_date) && !empty($to_date)) {
    // Thêm 23:59:59 vào to_date để lấy hết ngày đó
    $where_clauses[] = "order_date BETWEEN ? AND ?";
    $params[] = $from_date . " 00:00:00";
    $params[] = $to_date . " 23:59:59";
    $types .= "ss";
}

// Lọc theo tình trạng
$filter_status = isset($_GET['filter_status']) ? $_GET['filter_status'] : '';
if (!empty($filter_status)) {
    $where_clauses[] = "status = ?";
    $params[] = $filter_status;
    $types .= "s";
}

// Ghép các điều kiện WHERE
$sql = "SELECT * FROM orders";
if (count($where_clauses) > 0) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Xử lý Sắp xếp theo Phường (Yêu cầu đặc biệt của đồ án)
$sort_ward = isset($_GET['sort_ward']) ? $_GET['sort_ward'] : '';
if ($sort_ward == 'yes') {
    $sql .= " ORDER BY shipping_ward ASC, order_date DESC";
} else {
    $sql .= " ORDER BY order_date DESC"; // Mặc định đơn mới nhất lên đầu
}

// Chuẩn bị truy vấn an toàn
$stmt = $conn->prepare($sql);
if ($types) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$orders = $stmt->get_result();

// Hàm hỗ trợ dịch trạng thái sang tiếng Việt và màu sắc
function getStatusLabel($status) {
    switch($status) {
        case 'pending': return ['Chưa xử lý', '#d9534f'];
        case 'confirmed': return ['Đã xác nhận', '#f0ad4e'];
        case 'successful': return ['Giao thành công', '#5cb85c'];
        case 'cancelled': return ['Đã huỷ', '#999999'];
        default: return ['Không xác định', '#000'];
    }
}
?>

<h2 style="margin-bottom: 20px;">Quản Lý Đơn Đặt Hàng</h2>

<?php if ($message) echo "<div style='margin-bottom: 20px;'>$message</div>"; ?>

<div style="background: #fff; padding: 20px; border: 1px solid #ddd; margin-bottom: 20px;">
    <form action="manage_orders.php" method="GET" style="display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap;">
        
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Từ ngày:</label>
            <input type="date" name="from_date" value="<?php echo htmlspecialchars($from_date); ?>" style="padding: 8px; border: 1px solid #ccc;">
        </div>
        
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Đến ngày:</label>
            <input type="date" name="to_date" value="<?php echo htmlspecialchars($to_date); ?>" style="padding: 8px; border: 1px solid #ccc;">
        </div>
        
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px;">Tình trạng:</label>
            <select name="filter_status" style="padding: 8px; border: 1px solid #ccc; width: 150px;">
                <option value="">-- Tất cả --</option>
                <option value="pending" <?php if($filter_status == 'pending') echo 'selected'; ?>>Chưa xử lý</option>
                <option value="confirmed" <?php if($filter_status == 'confirmed') echo 'selected'; ?>>Đã xác nhận</option>
                <option value="successful" <?php if($filter_status == 'successful') echo 'selected'; ?>>Giao thành công</option>
                <option value="cancelled" <?php if($filter_status == 'cancelled') echo 'selected'; ?>>Đã huỷ</option>
            </select>
        </div>
        
        <div>
            <label style="display: block; font-size: 12px; font-weight: bold; margin-bottom: 5px; cursor: pointer;">
                <input type="checkbox" name="sort_ward" value="yes" <?php if($sort_ward == 'yes') echo 'checked'; ?>> Sắp xếp theo Phường
            </label>
        </div>
        
        <div>
            <button type="submit" style="background: #000; color: #fff; border: none; padding: 9px 20px; text-transform: uppercase; cursor: pointer; font-family: 'Times New Roman', Times, serif;">Lọc & Sắp xếp</button>
            <a href="manage_orders.php" style="display: inline-block; padding: 9px 20px; background: #eee; color: #000; text-decoration: none; text-transform: uppercase; font-size: 13px; margin-left: 10px;">Xoá lọc</a>
        </div>
    </form>
</div>

<table>
    <thead>
        <tr>
            <th>Mã Đơn</th>
            <th>Ngày Đặt</th>
            <th>Người Nhận</th>
            <th>Phường/Xã (Giao Hàng)</th>
            <th>Tổng Tiền</th>
            <th>Trạng Thái</th>
            <th>Thao tác</th>
        </tr>
    </thead>
    <tbody>
        <?php if($orders->num_rows > 0): ?>
            <?php while($row = $orders->fetch_assoc()): 
                $statusInfo = getStatusLabel($row['status']);
            ?>
            <tr>
                <td style="font-weight: bold;">#<?php echo $row['id']; ?></td>
                <td style="font-size: 13px;"><?php echo date('d/m/Y H:i', strtotime($row['order_date'])); ?></td>
                <td>
                    <?php echo htmlspecialchars($row['shipping_name']); ?><br>
                    <span style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($row['shipping_phone']); ?></span>
                </td>
                <td style="font-weight: bold; color: #000; background: #fafafa;">
                    <?php echo htmlspecialchars($row['shipping_ward']); ?>
                </td>
                <td style="text-align: right; font-weight: bold; color: #d9534f;">
                    <?php echo number_format($row['total_amount'], 0, ',', '.'); ?>đ
                </td>
                
                <td style="text-align: center;">
                    <form action="manage_orders.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                        <select name="status" onchange="this.form.submit()" style="padding: 5px; font-size: 12px; font-weight: bold; color: <?php echo $statusInfo[1]; ?>; border: 1px solid <?php echo $statusInfo[1]; ?>; outline: none;">
                            <option value="pending" <?php if($row['status'] == 'pending') echo 'selected'; ?>>Chưa xử lý</option>
                            <option value="confirmed" <?php if($row['status'] == 'confirmed') echo 'selected'; ?>>Đã xác nhận</option>
                            <option value="successful" <?php if($row['status'] == 'successful') echo 'selected'; ?>>Giao thành công</option>
                            <option value="cancelled" <?php if($row['status'] == 'cancelled') echo 'selected'; ?>>Đã huỷ</option>
                        </select>
                    </form>
                </td>
                
                <td style="text-align: center;">
                    <a href="order_detail.php?id=<?php echo $row['id']; ?>" style="color: #000; font-size: 12px; text-decoration: underline; text-transform: uppercase;">Xem chi tiết</a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align: center; padding: 20px;">Không tìm thấy đơn hàng nào phù hợp.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require_once 'inc_footer.php'; ?>