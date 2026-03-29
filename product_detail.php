<?php
require_once 'includes/header.php';

// Kiểm tra ID hợp lệ
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo "<script>window.location.href='shop.php';</script>";
    exit();
}

$product_id = (int)$_GET['id'];

// --- CÂU LỆNH SQL: Lấy chi tiết SP, tính Tồn kho và Giá bán FIFO ---
$sql = "SELECT p.*, c.name as category_name,
        -- Tính tổng tồn kho hiện tại (cộng dồn từ các lô có số lượng > 0)
        COALESCE((SELECT SUM(quantity_remaining) FROM import_batches WHERE product_id = p.id AND quantity_remaining > 0), 0) as total_stock,
        
        -- Tính giá bán FIFO (Lô cũ nhất)
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
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.id = $product_id AND p.status = 'visible'";

$result = $conn->query($sql);

if ($result->num_rows == 0) {
    echo "<main style='padding: 60px 0; text-align: center;'><div class='container'><h2>Sản phẩm không tồn tại hoặc đã bị ẩn!</h2><a href='shop.php' style='text-decoration: underline; margin-top: 20px; display: inline-block;'>Quay lại cửa hàng</a></div></main>";
    require_once 'includes/footer.php';
    exit();
}

$product = $result->fetch_assoc();
$stock = (int)$product['total_stock'];
$final_price = (float)$product['final_price'];
?>

<main style="padding: 40px 0;">
    <div class="container">
        
        <div class="breadcrumb" style="margin-bottom: 20px; font-size: 13px; color: #666;">
            <a href="index.php" style="color: #666; text-decoration: none;">Trang chủ</a> / 
            <a href="shop.php" style="color: #666; text-decoration: none;">Cửa hàng</a> / 
            <span style="color: #000; font-weight: bold;"><?php echo htmlspecialchars($product['name']); ?></span>
        </div>

        <div class="product-detail-wrapper">
            
            <div class="product-detail-image">
                <?php if($product['image']): ?>
                    <img src="<?php echo $product['image']; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>">
                <?php else: ?>
                    <div style="width: 100%; height: 100%; min-height: 400px; background: #eee; display: flex; align-items: center; justify-content: center; color: #999;">Không có hình ảnh</div>
                <?php endif; ?>
            </div>
            
            <div class="product-detail-info">
                <h1 class="product-detail-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="product-detail-price"><?php echo number_format($final_price, 0, ',', '.'); ?> VNĐ</div>
                
                <div class="product-meta">Mã sản phẩm: <span><?php echo $product['code']; ?></span></div>
                <div class="product-meta">Danh mục: <span><?php echo htmlspecialchars($product['category_name']); ?></span></div>
                <div class="product-meta">Tình trạng: 
                    <?php if($stock > 0): ?>
                        <span style="color: #5cb85c;">Còn hàng (<?php echo $stock; ?> <?php echo $product['unit']; ?>)</span>
                    <?php else: ?>
                        <span style="color: #d9534f;">Hết hàng tạm thời</span>
                    <?php endif; ?>
                </div>

                <form action="cart.php" method="POST" id="add-to-cart-form" class="add-to-cart-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    
                    <input type="number" name="quantity" id="buy_qty" value="1" min="1" max="<?php echo $stock; ?>" <?php echo ($stock == 0) ? 'disabled' : ''; ?>>
                    
                    <button type="submit" class="btn-add-cart" <?php echo ($stock == 0) ? 'disabled' : ''; ?>>
                        <?php echo ($stock > 0) ? 'Thêm Vào Giỏ Hàng' : 'Hết Hàng'; ?>
                    </button>
                </form>
                <p id="js-error-cart" style="color: #d9534f; font-size: 13px; display: none; margin-top: -10px; margin-bottom: 20px;"></p>
                
                <div class="product-detail-desc">
                    <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                </div>
                
            </div>
        </div>

    </div>
</main>

<script>
document.getElementById('add-to-cart-form').addEventListener('submit', function(e) {
    let stock = <?php echo $stock; ?>;
    let qtyInput = document.getElementById('buy_qty');
    let qty = parseInt(qtyInput.value);
    let errorP = document.getElementById('js-error-cart');
    
    if (isNaN(qty) || qty <= 0) {
        e.preventDefault();
        errorP.style.display = 'block';
        errorP.innerText = 'Vui lòng nhập số lượng hợp lệ (lớn hơn 0)!';
        return;
    }
    
    if (qty > stock) {
        e.preventDefault();
        errorP.style.display = 'block';
        errorP.innerText = 'Số lượng bạn chọn vượt quá số lượng tồn kho (' + stock + ')!';
        return;
    }
    
    errorP.style.display = 'none';
});
</script>

<?php require_once 'includes/footer.php'; ?>