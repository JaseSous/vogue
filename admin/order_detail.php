<?php
require_once 'inc_header.php';

if (!isset($_GET['id'])) {
    echo "<script>window.location.href='manage_orders.php';</script>";
    exit();
}

$order_id = (int)$_GET['id'];

// Lấy thông tin chung của đơn hàng
$order_query = $conn->query("SELECT * FROM orders WHERE id = $order_id");
if ($order_query->num_rows == 0) {
    echo "<script>alert('Đơn hàng không tồn tại!'); window.location.href='manage_orders.php';</script>";
    exit();
}
$order = $order_query->fetch_assoc();

// Lấy chi tiết các sản phẩm trong đơn hàng (Join với products để lấy tên/mã/ảnh)
$details_query = $conn->query("
    SELECT od.*, p.code, p.name, p.image 
    FROM order_details od 
    JOIN products p ON od.product_id = p.id 
    WHERE od.order_id = $order_id
");

// Hàm dịch phương thức thanh toán
function getPaymentMethod($method) {
    if ($method == 'cash') return 'Tiền mặt khi nhận hàng (COD)';
    if ($method == 'transfer') return 'Chuyển khoản ngân hàng';
    if ($method == 'online') return 'Thanh toán trực tuyến (VNPAY/MOMO)';
    return $method;
}
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h2>Chi Tiết Đơn Hàng #<?php echo $order['id']; ?></h2>
    <a href="manage_orders.php" style="color: #666; text-decoration: none; font-size: 14px;">&larr; Quay lại danh sách đơn hàng</a>
</div>

<div style="display: flex; gap: 20px; margin-bottom: 20px;">
    <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd;">
        <h3 style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Thông tin giao hàng</h3>
        <p style="margin-bottom: 8px;"><strong>Họ tên người nhận:</strong> <?php echo htmlspecialchars($order['shipping_name']); ?></p>
        <p style="margin-bottom: 8px;"><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($order['shipping_phone']); ?></p>
        <p style="margin-bottom: 8px;"><strong>Địa chỉ cụ thể:</strong> <?php echo htmlspecialchars($order['shipping_address']); ?></p>
        <p style="margin-bottom: 8px;"><strong>Phường/Xã:</strong> <?php echo htmlspecialchars($order['shipping_ward']); ?></p>
        <p style="margin-bottom: 8px;"><strong>Quận/Huyện:</strong> <?php echo htmlspecialchars($order['shipping_district']); ?></p>
        <p style="margin-bottom: 8px;"><strong>Tỉnh/Thành phố:</strong> <?php echo htmlspecialchars($order['shipping_city']); ?></p>
    </div>

    <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd;">
        <h3 style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">Thông tin đơn hàng</h3>
        <p style="margin-bottom: 8px;"><strong>Ngày đặt hàng:</strong> <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></p>
        <p style="margin-bottom: 8px;"><strong>Phương thức thanh toán:</strong> <?php echo getPaymentMethod($order['payment_method']); ?></p>
        <p style="margin-bottom: 8px;"><strong>Tình trạng:</strong> 
            <span style="font-weight: bold; text-transform: uppercase;">
                <?php 
                    if($order['status'] == 'pending') echo '<span style="color: #d9534f;">Chưa xử lý</span>';
                    elseif($order['status'] == 'confirmed') echo '<span style="color: #f0ad4e;">Đã xác nhận</span>';
                    elseif($order['status'] == 'successful') echo '<span style="color: #5cb85c;">Giao thành công</span>';
                    else echo '<span style="color: #999;">Đã huỷ</span>';
                ?>
            </span>
        </p>
        <p style="margin-bottom: 8px; font-size: 18px;"><strong>Tổng giá trị:</strong> <span style="color: #d9534f; font-weight: bold;"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</span></p>
    </div>
</div>

<h3 style="margin-bottom: 15px;">Sản phẩm trong đơn hàng</h3>
<table>
    <thead>
        <tr>
            <th>Hình ảnh</th>
            <th>Mã SP</th>
            <th>Tên Sản Phẩm</th>
            <th>Số Lượng</th>
            <th>Đơn Giá</th>
            <th>Thành Tiền</th>
        </tr>
    </thead>
    <tbody>
        <?php while($item = $details_query->fetch_assoc()): 
            $subtotal = $item['quantity'] * $item['selling_price'];
        ?>
        <tr>
            <td style="text-align: center;">
                <?php if($item['image']): ?>
                    <img src="../<?php echo $item['image']; ?>" alt="Ảnh" style="width: 50px; height: 50px; object-fit: cover; border: 1px solid #ddd;">
                <?php else: ?>
                    <div style="width: 50px; height: 50px; background: #eee; border: 1px solid #ddd; display: flex; align-items: center; justify-content: center; font-size: 10px; color: #999;">No IMG</div>
                <?php endif; ?>
            </td>
            <td style="font-weight: bold;"><?php echo $item['code']; ?></td>
            <td><?php echo htmlspecialchars($item['name']); ?></td>
            <td style="text-align: right;"><?php echo $item['quantity']; ?></td>
            <td style="text-align: right;"><?php echo number_format($item['selling_price'], 0, ',', '.'); ?>đ</td>
            <td style="text-align: right; font-weight: bold; color: #d9534f;"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php require_once 'inc_footer.php'; ?>