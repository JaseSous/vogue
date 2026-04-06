<?php
require_once 'includes/header.php';

// 1. KIỂM TRA ĐIỀU KIỆN TIÊN QUYẾT
// Phải đăng nhập mới được thanh toán
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Vui lòng đăng nhập để tiến hành thanh toán!'); window.location.href='login.php';</script>";
    exit();
}

// Giỏ hàng không được trống
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo "<script>window.location.href='shop.php';</script>";
    exit();
}

$user_id = $_SESSION['user_id'];

// Lấy thông tin user để điền sẵn vào form
$user_query = $conn->query("SELECT * FROM users WHERE id = $user_id");
$user_info = $user_query->fetch_assoc();

// 2. XỬ LÝ ĐẶT HÀNG (KHI BẤM SUBMIT)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    // Nhận dữ liệu giao hàng
    $name = trim($_POST['shipping_name']);
    $phone = trim($_POST['shipping_phone']);
    $address = trim($_POST['shipping_address']);
    $ward = trim($_POST['shipping_ward']); // Tiêu chí sắp xếp của Admin
    $district = trim($_POST['shipping_district']);
    $city = trim($_POST['shipping_city']);
    $payment_method = $_POST['payment_method'];

    // Bắt đầu Transaction (Bảo vệ tính toàn vẹn dữ liệu)
    $conn->begin_transaction();

    try {
        // BƯỚC 1: Tính lại tổng tiền và Tạo Đơn Hàng (orders)
        $total_amount = 0;
        $order_date = date('Y-m-d H:i:s');
        $status = 'pending';

        // Tạm thời tạo đơn hàng với tổng tiền = 0 (sẽ update sau khi tính FIFO)
        $stmt_order = $conn->prepare("INSERT INTO orders (user_id, shipping_name, shipping_phone, shipping_address, shipping_ward, shipping_district, shipping_city, total_amount, payment_method, status, order_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_order->bind_param("issssssdsss", $user_id, $name, $phone, $address, $ward, $district, $city, $total_amount, $payment_method, $status, $order_date);
        $stmt_order->execute();
        $order_id = $stmt_order->insert_id; // Lấy ID của đơn hàng vừa tạo

        // BƯỚC 2: TRỪ TỒN KHO VÀ LƯU CHI TIẾT ĐƠN HÀNG (Chuẩn Bình Quân Gia Quyền)
        foreach ($_SESSION['cart'] as $product_id => $qty_needed) {
            // Lấy thông tin giá bán hiện tại và tính lại tồn kho một lần nữa để chắc chắn
            $p_query = $conn->query("SELECT selling_price, 
                (initial_quantity + COALESCE((SELECT SUM(quantity_imported) FROM import_batches ib JOIN import_receipts ir ON ib.receipt_id = ir.id WHERE ir.status = 'completed' AND ib.product_id = products.id), 0) - COALESCE((SELECT SUM(quantity) FROM order_details od JOIN orders o ON od.order_id = o.id WHERE o.status != 'cancelled' AND od.product_id = products.id), 0)) as total_stock 
                FROM products WHERE id = $product_id FOR UPDATE");
            
            $p_info = $p_query->fetch_assoc();
            $selling_price = $p_info['selling_price'];
            $available = $p_info['total_stock'];

            if ($available < $qty_needed) {
                throw new Exception("Sản phẩm ID $product_id hiện không đủ số lượng trong kho!");
            }

            // Ghi vào chi tiết đơn hàng
            $stmt_detail = $conn->prepare("INSERT INTO order_details (order_id, product_id, quantity, selling_price) VALUES (?, ?, ?, ?)");
            $stmt_detail->bind_param("iiid", $order_id, $product_id, $qty_needed, $selling_price);
            $stmt_detail->execute();

            // Cộng dồn vào tổng tiền đơn hàng
            $total_amount += ($qty_needed * $selling_price);
        }

        // BƯỚC 3: Cập nhật lại tổng tiền chuẩn xác vào đơn hàng
        $conn->query("UPDATE orders SET total_amount = $total_amount WHERE id = $order_id");

        // Xác nhận giao dịch thành công (Lưu toàn bộ vào CSDL)
        $conn->commit();

        // Xóa giỏ hàng
        unset($_SESSION['cart']);

        // Chuyển hướng đến trang lịch sử
        echo "<script>alert('Đặt hàng thành công! Mã đơn hàng của bạn là #$order_id'); window.location.href='history.php';</script>";
        exit();

    } catch (Exception $e) {
        // Có lỗi xảy ra (ví dụ 2 người cùng mua 1 lúc hết hàng) -> Hoàn tác toàn bộ
        $conn->rollback();
        echo "<script>alert('Lỗi đặt hàng: " . $e->getMessage() . "'); window.location.href='cart.php';</script>";
        exit();
    }
}
?>

<main style="padding: 40px 0;">
    <div class="container">
        <h2 style="text-align: center; margin-bottom: 40px; letter-spacing: 3px;">THANH TOÁN</h2>

        <form action="checkout.php" method="POST" id="form-checkout" class="checkout-container">
            
            <div class="checkout-form-section">
                <h3>1. Thông tin giao hàng</h3>
                
                <div class="form-group">
                    <label>Họ tên người nhận *</label>
                    <input type="text" name="shipping_name" id="c_name" value="<?php echo htmlspecialchars($user_info['fullname']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Số điện thoại *</label>
                    <input type="text" name="shipping_phone" id="c_phone" value="<?php echo htmlspecialchars($user_info['phone']); ?>">
                </div>
                
                <div class="form-group">
                    <label>Địa chỉ nhà / Tên đường *</label>
                    <input type="text" name="shipping_address" id="c_address" placeholder="Ví dụ: Số 123 Đường ABC...">
                </div>

                <div style="display: flex; gap: 20px;">
                    <div class="form-group" style="flex: 1;">
                        <label>Phường / Xã *</label>
                        <input type="text" name="shipping_ward" id="c_ward" placeholder="Nhập phường/xã">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Quận / Huyện *</label>
                        <input type="text" name="shipping_district" id="c_district" placeholder="Nhập quận/huyện">
                    </div>
                    <div class="form-group" style="flex: 1;">
                        <label>Tỉnh / Thành phố *</label>
                        <input type="text" name="shipping_city" id="c_city" placeholder="Nhập tỉnh/thành">
                    </div>
                </div>

                <h3 style="margin-top: 30px;">2. Phương thức thanh toán</h3>
                <div class="form-group">
                    <label style="font-weight: normal; font-size: 15px; cursor: pointer;">
                        <input type="radio" name="payment_method" value="cash" checked style="width: auto; margin-right: 10px;"> 
                        Thanh toán tiền mặt khi nhận hàng (COD)
                    </label>
                </div>
                <div class="form-group">
                    <label style="font-weight: normal; font-size: 15px; cursor: pointer;">
                        <input type="radio" name="payment_method" value="transfer" style="width: auto; margin-right: 10px;"> 
                        Chuyển khoản ngân hàng (Sẽ có nhân viên gọi xác nhận)
                    </label>
                </div>
                
                <p id="js-error-checkout" style="color: #d9534f; font-size: 14px; display: none; margin-top: 10px;"></p>
            </div>

            <div class="checkout-summary-section">
                <h3 style="text-transform: uppercase; margin-bottom: 20px; font-size: 18px; border-bottom: 2px solid #000; padding-bottom: 10px;">Đơn hàng của bạn</h3>
                
                <?php
                $total_cart_value = 0;
                $product_ids = implode(',', array_keys($_SESSION['cart']));
                
                $sql = "SELECT p.id, p.name, p.selling_price as final_price
                        FROM products p WHERE p.id IN ($product_ids)";
                
                $result = $conn->query($sql);
                while ($row = $result->fetch_assoc()):
                    $qty = $_SESSION['cart'][$row['id']];
                    $subtotal = $row['final_price'] * $qty;
                    $total_cart_value += $subtotal;
                ?>
                    <div class="checkout-item">
                        <div class="checkout-item-name"><?php echo htmlspecialchars($row['name']); ?> <strong>x<?php echo $qty; ?></strong></div>
                        <div class="checkout-item-price"><?php echo number_format($subtotal, 0, ',', '.'); ?>đ</div>
                    </div>
                <?php endwhile; ?>

                <div style="display: flex; justify-content: space-between; margin-top: 20px; padding-top: 20px; border-top: 2px solid #000; font-size: 20px; font-weight: bold;">
                    <span>TỔNG CỘNG:</span>
                    <span style="color: #d9534f;"><?php echo number_format($total_cart_value, 0, ',', '.'); ?>đ</span>
                </div>

                <button type="submit" name="place_order" style="display: block; width: 100%; background: #000; color: #fff; padding: 15px; margin-top: 30px; text-transform: uppercase; font-family: 'Times New Roman', Times, serif; letter-spacing: 2px; border: none; cursor: pointer; transition: 0.3s;">
                    ĐẶT HÀNG NGAY
                </button>
            </div>

        </form>
    </div>
</main>

<script>
// KIỂM TRA FORM BẰNG JAVASCRIPT TRƯỚC KHI GỬI
document.getElementById('form-checkout').addEventListener('submit', function(e) {
    let name = document.getElementById('c_name').value.trim();
    let phone = document.getElementById('c_phone').value.trim();
    let address = document.getElementById('c_address').value.trim();
    let ward = document.getElementById('c_ward').value.trim();
    let district = document.getElementById('c_district').value.trim();
    let city = document.getElementById('c_city').value.trim();
    let errorP = document.getElementById('js-error-checkout');
    
    if (name === '' || phone === '' || address === '' || ward === '' || district === '' || city === '') {
        e.preventDefault();
        errorP.style.display = 'block';
        errorP.innerText = 'Vui lòng điền đầy đủ tất cả các thông tin giao hàng có dấu (*)!';
        return;
    }
    errorP.style.display = 'none';
});
</script>

<?php require_once 'includes/footer.php'; ?>