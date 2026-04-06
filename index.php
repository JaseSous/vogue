<?php
require_once 'includes/header.php';

// Truy vấn lấy 4 sản phẩm mới nhất
$sql = "SELECT *, selling_price as final_price 
        FROM products 
        WHERE status = 'visible' 
        ORDER BY id DESC 
        LIMIT 4";

$latest_products = $conn->query($sql);
?>

<section class="hero-banner">
    <h1>VOGUE</h1>
    <p>Định Hình Phong Cách - Tôn Vinh Cá Tính</p>
</section>

<main style="padding-bottom: 60px;">
    <div class="container">
        
        <h2 style="text-align: center; margin-bottom: 40px; letter-spacing: 3px; font-size: 24px;">HÀNG MỚI VỀ</h2>

        <?php if($latest_products->num_rows > 0): ?>
            <div class="product-grid">
                <?php while($p = $latest_products->fetch_assoc()): ?>
                    <a href="product_detail.php?id=<?php echo $p['id']; ?>" class="product-card">
                        <?php if($p['image']): ?>
                            <img src="<?php echo $p['image']; ?>" alt="<?php echo htmlspecialchars($p['name']); ?>" class="product-image">
                        <?php else: ?>
                            <div class="product-image" style="display: flex; align-items: center; justify-content: center; color: #999;">Không có hình</div>
                        <?php endif; ?>
                        
                        <h3 class="product-title"><?php echo htmlspecialchars($p['name']); ?></h3>
                        <p class="product-price"><?php echo number_format($p['final_price'], 0, ',', '.'); ?>đ</p>
                    </a>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; color: #666; padding: 40px 0;">Hiện chưa có sản phẩm nào.</p>
        <?php endif; ?>

        <div style="text-align: center; margin-top: 50px;">
            <a href="shop.php" class="btn-view-all">Khám phá tất cả bộ sưu tập</a>
        </div>

    </div> </main> <?php require_once 'includes/footer.php'; ?>