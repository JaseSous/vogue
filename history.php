<?php
require_once 'includes/header.php';

// Bắt buộc phải đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Truy vấn lấy danh sách đơn hàng của User này, sắp xếp mới nhất lên đầu
$orders_sql = "SELECT * FROM orders WHERE user_id = $user_id ORDER BY order_date DESC";
$orders_result = $conn->query($orders_sql);

// Hàm dịch trạng thái
function getStatusFormat($status) {
    switch($status) {
        case 'pending': return ['Chưa xử lý', 'status-pending'];
        case 'confirmed': return ['Đã xác nhận', 'status-confirmed'];
        case 'successful': return ['Giao thành công', 'status-successful'];
        case 'cancelled': return ['Đã huỷ', 'status-cancelled'];
        default: return ['Không xác định', ''];
    }
}
?>

<main style="padding: 40px 0; background-color: #fcfcfc; min-height: 70vh;">
    <div class="container history-container">
        <h2 style="text-align: center; margin-bottom: 40px; letter-spacing: 3px;">LỊCH SỬ ĐƠN HÀNG</h2>

        <?php if ($orders_result->num_rows == 0): ?>
            <div style="text-align: center; padding: 50px 0; background: #fff; border: 1px solid #eee;">
                <p style="color: #666; margin-bottom: 20px;">Bạn chưa có đơn hàng nào.</p>
                <a href="shop.php" style="display: inline-block; background: #000; color: #fff; padding: 12px 30px; text-transform: uppercase; text-decoration: none; font-family: 'Times New Roman', Times, serif;">Khám phá bộ sưu tập</a>
            </div>
        <?php else: ?>
            
            <?php while ($order = $orders_result->fetch_assoc()): 
                $order_id = $order['id'];
                $statusInfo = getStatusFormat($order['status']);
                
                // Truy vấn lấy chi tiết các sản phẩm trong đơn hàng này
                $details_sql = "
                    SELECT od.*, p.name, p.image 
                    FROM order_details od 
                    JOIN products p ON od.product_id = p.id 
                    WHERE od.order_id = $order_id
                ";
                $details_result = $conn->query($details_sql);
            ?>
                
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-header-info">
                            Đơn hàng <strong>#<?php echo $order['id']; ?></strong><br>
                            <span style="font-size: 12px;">Ngày đặt: <?php echo date('d/m/Y H:i', strtotime($order['order_date'])); ?></span>
                        </div>
                        <div class="order-status <?php echo $statusInfo[1]; ?>">
                            <?php echo $statusInfo[0]; ?>
                        </div>
                    </div>

                    <div class="order-body">
                        <div class="order-item-list">
                            <?php while ($item = $details_result->fetch_assoc()): ?>
                                <div class="history-item">
                                    <img src="<?php echo $item['image'] ? $item['image'] : 'assets/images/no-image.jpg'; ?>" alt="Ảnh SP">
                                    <div class="history-item-info">
                                        <div class="history-item-title"><?php echo htmlspecialchars($item['name']); ?></div>
                                        <div class="history-item-meta">
                                            Đơn giá: <?php echo number_format($item['selling_price'], 0, ',', '.'); ?>đ <br>
                                            Số lượng: <strong>x<?php echo $item['quantity']; ?></strong> 
                                            <span style="color: #ccc; margin: 0 5px;">|</span> 
                                            Thành tiền: <span style="color: #d9534f; font-weight: bold;"><?php echo number_format($item['selling_price'] * $item['quantity'], 0, ',', '.'); ?>đ</span>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>

                        <div class="order-footer">
                            <div class="shipping-info">
                                <strong>Giao đến:</strong> <?php echo htmlspecialchars($order['shipping_name']); ?> - <?php echo htmlspecialchars($order['shipping_phone']); ?><br>
                                <?php echo htmlspecialchars($order['shipping_address']); ?>, <?php echo htmlspecialchars($order['shipping_ward']); ?>, <?php echo htmlspecialchars($order['shipping_district']); ?>, <?php echo htmlspecialchars($order['shipping_city']); ?><br>
                                <span style="font-size: 12px; color: #888;">Thanh toán: <?php echo ($order['payment_method'] == 'cash') ? 'Tiền mặt (COD)' : 'Chuyển khoản'; ?></span>
                            </div>
                            <div style="text-align: right;">
                                <div style="font-size: 13px; color: #666; margin-bottom: 5px;">Tổng cộng:</div>
                                <div class="order-total-price"><?php echo number_format($order['total_amount'], 0, ',', '.'); ?>đ</div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php endwhile; ?>
            
        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>