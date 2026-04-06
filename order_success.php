<?php
require_once 'includes/header.php';

// Kiểm tra đăng nhập
if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='login.php';</script>";
    exit();
}

// Kiểm tra xem có truyền mã đơn hàng trên URL không
if (!isset($_GET['id'])) {
    echo "<script>window.location.href='history.php';</script>";
    exit();
}

$order_id = intval($_GET['id']);
$user_id = $_SESSION['user_id'];

// Truy vấn lấy thông tin Đơn hàng (Đảm bảo đơn hàng này đúng là của user đang đăng nhập)
$stmt_order = $conn->prepare("SELECT * FROM orders WHERE id = ? AND user_id = ?");
$stmt_order->bind_param("ii", $order_id, $user_id);
$stmt_order->execute();
$order_info = $stmt_order->get_result()->fetch_assoc();

// Nếu người dùng cố tình gõ ID bậy bạ của người khác thì đá về trang lịch sử
if (!$order_info) {
    echo "<script>alert('Không tìm thấy đơn hàng!'); window.location.href='history.php';</script>";
    exit();
}

// Truy vấn lấy Danh sách Sản phẩm trong đơn hàng
$stmt_details = $conn->prepare("SELECT od.*, p.name, p.image FROM order_details od JOIN products p ON od.product_id = p.id WHERE od.order_id = ?");
$stmt_details->bind_param("i", $order_id);
$stmt_details->execute();
$order_details = $stmt_details->get_result();
?>

<main style="padding: 60px 0; background-color: #f9f9f9; min-height: 70vh; display: flex; justify-content: center;">
    <div style="background: #fff; padding: 40px; width: 100%; max-width: 700px; border: 1px solid #eee; box-shadow: 0 4px 15px rgba(0,0,0,0.05);">
        
        <div style="text-align: center; margin-bottom: 30px;">
            <svg viewBox="0 0 24 24" width="64" height="64" stroke="#5cb85c" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 10px;">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <h2 style="margin: 0; letter-spacing: 2px; text-transform: uppercase; color: #000;">Đặt Hàng Thành Công!</h2>
            <p style="color: #666; margin-top: 10px; font-size: 15px;">Cảm ơn bạn đã mua sắm tại VOGUE. Dưới đây là thông tin đơn hàng của bạn.</p>
        </div>

        <div style="background: #fdfdfd; border: 1px dashed #ccc; padding: 20px; margin-bottom: 30px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 20px;">
            <div style="flex: 1; min-width: 250px;">
                <h4 style="margin-top: 0; font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 8px;">Thông tin nhận hàng</h4>
                <p style="margin: 5px 0; font-size: 14px;"><strong>Người nhận:</strong> <?php echo htmlspecialchars($order_info['shipping_name']); ?></p>
                <p style="margin: 5px 0; font-size: 14px;"><strong>Điện thoại:</strong> <?php echo htmlspecialchars($order_info['shipping_phone']); ?></p>
                <p style="margin: 5px 0; font-size: 14px;"><strong>Địa chỉ:</strong> <?php echo htmlspecialchars($order_info['shipping_address'] . ', ' . $order_info['shipping_ward'] . ', ' . $order_info['shipping_district'] . ', ' . $order_info['shipping_city']); ?></p>
            </div>
            <div style="flex: 1; min-width: 250px;">
                <h4 style="margin-top: 0; font-size: 14px; text-transform: uppercase; border-bottom: 1px solid #eee; padding-bottom: 8px;">Chi tiết hóa đơn</h4>
                <p style="margin: 5px 0; font-size: 14px;"><strong>Mã đơn hàng:</strong> #<?php echo $order_info['id']; ?></p>
                <p style="margin: 5px 0; font-size: 14px;"><strong>Ngày đặt:</strong> <?php echo date('d/m/Y H:i', strtotime($order_info['order_date'])); ?></p>
                <p style="margin: 5px 0; font-size: 14px;"><strong>Thanh toán:</strong> <?php 
                    if($order_info['payment_method'] == 'cash') echo 'Tiền mặt (COD)'; 
                    elseif($order_info['payment_method'] == 'transfer') echo 'Chuyển khoản ngân hàng'; 
                    else echo 'Thanh toán trực tuyến (VNPAY/Momo)'; 
                ?></p>
            </div>
        </div>

        <h4 style="margin-top: 0; font-size: 14px; text-transform: uppercase; border-bottom: 2px solid #000; padding-bottom: 8px; margin-bottom: 15px;">Sản phẩm đã đặt</h4>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <tbody>
                <?php while ($item = $order_details->fetch_assoc()): ?>
                    <tr style="border-bottom: 1px solid #eee;">
                        <td style="padding: 15px 0; display: flex; align-items: center; gap: 15px;">
                            <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="product" style="width: 60px; height: auto; object-fit: cover; border: 1px solid #eee;">
                            <div>
                                <strong style="display: block; font-size: 14px; margin-bottom: 5px;"><?php echo htmlspecialchars($item['name']); ?></strong>
                                <span style="font-size: 13px; color: #666;">Số lượng: <?php echo $item['quantity']; ?></span>
                            </div>
                        </td>
                        <td style="padding: 15px 0; text-align: right; font-weight: bold; font-size: 14px;">
                            <?php echo number_format($item['selling_price'] * $item['quantity'], 0, ',', '.'); ?>đ
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <div style="display: flex; justify-content: space-between; align-items: center; font-size: 18px; font-weight: bold; padding-top: 10px;">
            <span>TỔNG CỘNG:</span>
            <span style="color: #d9534f; font-size: 22px;"><?php echo number_format($order_info['total_amount'], 0, ',', '.'); ?>đ</span>
        </div>

        <div style="margin-top: 40px; display: flex; gap: 15px; justify-content: center;">
            <a href="shop.php" style="padding: 12px 25px; border: 1px solid #000; color: #000; text-decoration: none; text-transform: uppercase; font-size: 13px; font-weight: bold; transition: 0.3s; text-align: center;">Tiếp tục mua sắm</a>
            <a href="history.php" style="padding: 12px 25px; background: #000; color: #fff; text-decoration: none; text-transform: uppercase; font-size: 13px; font-weight: bold; transition: 0.3s; text-align: center;">Xem lịch sử đơn</a>
        </div>

    </div>
</main>

<style>
/* CSS Hover cho 2 nút cuối trang */
a[href="shop.php"]:hover { background-color: #f5f5f5; }
a[href="history.php"]:hover { background-color: #333; }
</style>

<?php require_once 'includes/footer.php'; ?>