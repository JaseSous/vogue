<?php
require_once 'includes/header.php';

// Khởi tạo giỏ hàng nếu chưa có
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$message = '';

// --- XỬ LÝ CÁC THAO TÁC (THÊM, SỬA, XOÁ) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
    $quantity = isset($_POST['quantity']) ? (int)$_POST['quantity'] : 1;

    if ($product_id > 0) {
        // Kiểm tra tồn kho thực tế từ CSDL trước khi cho phép thao tác
        $stock_check = $conn->query("SELECT COALESCE((SELECT SUM(quantity_remaining) FROM import_batches WHERE product_id = $product_id AND quantity_remaining > 0), 0) as total_stock FROM products WHERE id = $product_id");
        if ($stock_check->num_rows > 0) {
            $stock_row = $stock_check->fetch_assoc();
            $available_stock = (int)$stock_row['total_stock'];

            if ($action == 'add') {
                // Nếu sản phẩm đã có trong giỏ, cộng dồn số lượng
                if (isset($_SESSION['cart'][$product_id])) {
                    $new_qty = $_SESSION['cart'][$product_id] + $quantity;
                } else {
                    $new_qty = $quantity;
                }
                
                // Kiểm tra xem tổng SL muốn mua có vượt tồn kho không
                if ($new_qty > $available_stock) {
                    $_SESSION['cart_msg'] = "Không đủ số lượng! Chỉ còn $available_stock sản phẩm trong kho.";
                } else {
                    $_SESSION['cart'][$product_id] = $new_qty;
                    $_SESSION['cart_msg'] = "Đã thêm sản phẩm vào giỏ hàng thành công!";
                }

            } elseif ($action == 'update') {
                if ($quantity > 0 && $quantity <= $available_stock) {
                    $_SESSION['cart'][$product_id] = $quantity;
                    $_SESSION['cart_msg'] = "Đã cập nhật số lượng!";
                } elseif ($quantity > $available_stock) {
                    $_SESSION['cart_msg'] = "Cập nhật thất bại. Kho chỉ còn $available_stock sản phẩm.";
                } else {
                    unset($_SESSION['cart'][$product_id]);
                }

            } elseif ($action == 'remove') {
                if (isset($_SESSION['cart'][$product_id])) {
                    unset($_SESSION['cart'][$product_id]);
                    $_SESSION['cart_msg'] = "Đã xoá sản phẩm khỏi giỏ hàng.";
                }
            }
        }
    }
    
    // Redirect để tránh lỗi resubmit form khi F5
    echo "<script>window.location.href='cart.php';</script>";
    exit();
}

// Hiển thị thông báo (nếu có)
if (isset($_SESSION['cart_msg'])) {
    $message = $_SESSION['cart_msg'];
    unset($_SESSION['cart_msg']);
}
?>

<main style="padding: 40px 0;">
    <div class="container">
        <h2 style="text-align: center; margin-bottom: 40px; letter-spacing: 3px;">GIỎ HÀNG CỦA BẠN</h2>

        <?php if ($message): ?>
            <div style="background: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px; text-align: center; color: #d9534f; font-weight: bold;">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($_SESSION['cart'])): ?>
            <div style="text-align: center; padding: 50px 0;">
                <p style="color: #666; margin-bottom: 20px;">Giỏ hàng của bạn hiện đang trống.</p>
                <a href="shop.php" style="display: inline-block; background: #000; color: #fff; padding: 12px 30px; text-transform: uppercase; text-decoration: none; font-family: 'Times New Roman', Times, serif;">Tiếp tục mua sắm</a>
            </div>
        <?php else: ?>
            
            <div class="cart-container">
                <div class="cart-items">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th style="text-align: center;">Đơn giá</th>
                                <th style="text-align: center;">Số lượng</th>
                                <th style="text-align: right;">Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total_cart_value = 0;
                            $product_ids = implode(',', array_keys($_SESSION['cart']));
                            
                            // Lấy thông tin sản phẩm và tính Giá bán FIFO chuẩn nhất
                            $sql = "SELECT p.*, 
                                    COALESCE((SELECT SUM(quantity_remaining) FROM import_batches WHERE product_id = p.id AND quantity_remaining > 0), 0) as total_stock,
                                    GREATEST(
                                        COALESCE(
                                            (SELECT b.import_price 
                                             FROM import_batches b 
                                             JOIN import_receipts r ON b.receipt_id = r.id 
                                             WHERE b.product_id = p.id AND b.quantity_remaining > 0 AND r.status = 'completed' 
                                             ORDER BY r.import_date ASC, b.id ASC LIMIT 1)
                                        , 0) * (1 + p.profit_margin / 100), 
                                        p.suggested_price
                                    ) as final_price
                                    FROM products p
                                    WHERE p.id IN ($product_ids)";
                            
                            $result = $conn->query($sql);
                            
                            while ($row = $result->fetch_assoc()):
                                $pid = $row['id'];
                                $qty = $_SESSION['cart'][$pid];
                                $stock = $row['total_stock'];
                                $price = $row['final_price'];
                                $subtotal = $price * $qty;
                                $total_cart_value += $subtotal;
                            ?>
                                <tr>
                                    <td>
                                        <div class="cart-item-info">
                                            <img src="<?php echo $row['image'] ? $row['image'] : 'assets/images/no-image.jpg'; ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="cart-item-img">
                                            <div>
                                                <div class="cart-item-title"><a href="product_detail.php?id=<?php echo $pid; ?>"><?php echo htmlspecialchars($row['name']); ?></a></div>
                                                <div class="cart-item-meta">Mã SP: <?php echo $row['code']; ?></div>
                                                <div class="cart-item-meta" style="color: <?php echo $stock >= $qty ? '#5cb85c' : '#d9534f'; ?>;">
                                                    Kho còn: <?php echo $stock; ?>
                                                </div>
                                                
                                                <form action="cart.php" method="POST" style="margin-top: 10px;">
                                                    <input type="hidden" name="action" value="remove">
                                                    <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                                                    <button type="submit" class="cart-btn-remove">Xoá</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                    <td style="text-align: center; font-weight: bold;">
                                        <?php echo number_format($price, 0, ',', '.'); ?>đ
                                    </td>
                                    <td style="text-align: center;">
                                        <form action="cart.php" method="POST" class="cart-qty-form" style="justify-content: center;">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?php echo $pid; ?>">
                                            <input type="number" name="quantity" value="<?php echo $qty; ?>" min="1" max="<?php echo $stock; ?>">
                                            <button type="submit" class="cart-btn-update">Cập nhật</button>
                                        </form>
                                    </td>
                                    <td style="text-align: right; font-weight: bold; color: #d9534f; font-size: 16px;">
                                        <?php echo number_format($subtotal, 0, ',', '.'); ?>đ
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="cart-summary">
                    <h3>Tổng đơn hàng</h3>
                    <div class="summary-row">
                        <span>Tạm tính:</span>
                        <span style="font-weight: bold;"><?php echo number_format($total_cart_value, 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Phí vận chuyển:</span>
                        <span>Sẽ tính ở bước sau</span>
                    </div>
                    <div class="summary-total">
                        <span>TỔNG CỘNG:</span>
                        <span style="color: #d9534f;"><?php echo number_format($total_cart_value, 0, ',', '.'); ?>đ</span>
                    </div>

                    <a href="checkout.php" class="btn-checkout" style="text-decoration: none;">Tiến Hành Thanh Toán</a>
                    
                    <a href="shop.php" style="display: block; text-align: center; margin-top: 15px; font-size: 13px; color: #666; text-decoration: underline;">Tiếp tục mua sắm</a>
                </div>
            </div>

        <?php endif; ?>
    </div>
</main>

<?php require_once 'includes/footer.php'; ?>