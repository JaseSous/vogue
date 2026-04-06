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
            p.selling_price
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

<style>
/* 1. Đổi con trỏ chuột khi chỉ vào ảnh gốc */
.product-detail-image img {
    cursor: zoom-in;
    transition: opacity 0.3s ease;
}
.product-detail-image img:hover {
    opacity: 0.9;
}

/* 2. Giao diện nền đen của Pop-up */
#vogue-lightbox {
    display: none; 
    position: fixed;
    z-index: 10000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.85); 
    backdrop-filter: blur(5px);
    justify-content: center;
    align-items: center;
    opacity: 0;
    transition: opacity 0.3s ease;
}

/* 3. Hiệu ứng mặc định của ảnh trong pop-up */
#vogue-lightbox .lightbox-content {
    max-width: 90%;
    max-height: 90vh;
    object-fit: contain;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    transform: scale(0.8);
    cursor: zoom-in;
}

/* Class kích hoạt hiệu ứng mở */
#vogue-lightbox.show {
    opacity: 1;
}

/* Các trạng thái con trỏ chuột khi Zoom và Kéo */
.lightbox-content.grab { cursor: grab !important; }
.lightbox-content.grabbing { cursor: grabbing !important; }

/* 4. Nút tắt (X) ở góc phải */
.lightbox-close {
    position: absolute;
    top: 20px;
    right: 35px;
    color: #f1f1f1;
    font-size: 40px;
    font-weight: 300;
    cursor: pointer;
    transition: color 0.3s;
    z-index: 10001;
}
.lightbox-close:hover {
    color: #d9534f;
}
</style>

<div id="vogue-lightbox">
    <span class="lightbox-close">&times;</span>
    <img class="lightbox-content" id="vogue-lightbox-img" draggable="false">
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const lightbox = document.getElementById('vogue-lightbox');
    const lightboxImg = document.getElementById('vogue-lightbox-img');
    const closeBtn = document.querySelector('.lightbox-close');
    const productImg = document.querySelector('.product-detail-image img');

    // Các biến lưu trữ trạng thái Toán học của ảnh
    let scale = 1;
    let translateX = 0;
    let translateY = 0;
    
    // Biến cho tính năng kéo thả
    let isDragging = false;
    let startX, startY;

    // --- 1. MỞ POP-UP ---
    if(productImg) {
        productImg.addEventListener('click', function() {
            lightbox.style.display = "flex"; 
            lightboxImg.src = this.src;      
            
            // Reset các thông số zoom mỗi khi mở lại
            scale = 1;
            translateX = 0;
            translateY = 0;
            lightboxImg.style.transition = 'transform 0.3s ease'; // Bật hiệu ứng mở mượt
            lightboxImg.style.transform = `translate(0px, 0px) scale(1)`;
            lightboxImg.classList.remove('grab', 'grabbing'); // Trả lại con trỏ kính lúp

            setTimeout(() => lightbox.classList.add('show'), 10);
        });
    }

    // --- 2. ĐÓNG POP-UP ---
    function closeLightbox() {
        lightbox.classList.remove('show'); 
        lightboxImg.style.transition = 'transform 0.3s ease'; // Bật hiệu ứng thu nhỏ mượt
        lightboxImg.style.transform = `translate(0px, 0px) scale(0.8)`;

        setTimeout(() => lightbox.style.display = "none", 300);
    }

    if(closeBtn) closeBtn.addEventListener('click', closeLightbox);
    
    // Bấm ra nền đen ngoài ảnh thì đóng
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) closeLightbox();
    });

    // --- 3. LĂN CHUỘT: ZOOM VÀO VỊ TRÍ CON TRỎ ---
    lightboxImg.addEventListener('wheel', function(e) {
        e.preventDefault(); // Chặn cuộn trang web

        // Tắt CSS transition để thao tác cuộn mượt tức thì, không bị bóng ma (lag)
        lightboxImg.style.transition = 'none';

        // Xác định hướng cuộn: Lên (<0) là phóng to, Xuống (>0) là thu nhỏ
        const zoomDirection = e.deltaY < 0 ? 1 : -1;
        const zoomSpeed = 0.2; // Độ nhạy của mỗi lần cuộn chuột
        let newScale = scale + (zoomDirection * zoomSpeed);

        // Giới hạn chỉ cho phép zoom từ 1x (kích thước thật) đến 4x (zoom to 4 lần)
        newScale = Math.min(Math.max(1, newScale), 4);

        // THUẬT TOÁN BÙ TRỪ TỌA ĐỘ
        const rect = lightboxImg.getBoundingClientRect();
        const imgCenterX = rect.left + rect.width / 2; // Tâm X của ảnh
        const imgCenterY = rect.top + rect.height / 2; // Tâm Y của ảnh

        // Khoảng cách từ vị trí chuột đến tâm ảnh
        const mouseOffsetX = e.clientX - imgCenterX;
        const mouseOffsetY = e.clientY - imgCenterY;

        // Tỉ lệ thay đổi giữa scale mới và cũ
        const ratio = newScale / scale;

        // Dịch chuyển X và Y để điểm dưới chuột giữ nguyên vị trí
        translateX = translateX - (mouseOffsetX * (ratio - 1));
        translateY = translateY - (mouseOffsetY * (ratio - 1));

        // Nếu thu nhỏ kịch sàn về 1x, reset ảnh về chính giữa màn hình
        if (newScale === 1) {
            translateX = 0;
            translateY = 0;
            lightboxImg.classList.remove('grab'); // Trả lại trỏ kính lúp
        } else {
            lightboxImg.classList.add('grab'); // Hiện trỏ bàn tay báo hiệu có thể kéo
        }

        scale = newScale;
        lightboxImg.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
    });

    // --- 4. BẤM GIỮ CHUỘT: KÉO ẢNH ĐỂ XEM QUANH (PAN) ---
    lightboxImg.addEventListener('mousedown', function(e) {
        // Chỉ cho phép kéo khi ảnh đang được phóng to
        if (scale > 1) {
            e.preventDefault();
            isDragging = true;
            lightboxImg.classList.replace('grab', 'grabbing'); // Bàn tay nắm lại
            
            // Lưu tọa độ chuột ban đầu trừ đi tọa độ ảnh đang bị lệch
            startX = e.clientX - translateX;
            startY = e.clientY - translateY;
        }
    });

    // --- 5. RÊ CHUỘT: DỊCH CHUYỂN ẢNH ---
    window.addEventListener('mousemove', function(e) {
        if (isDragging && scale > 1) {
            lightboxImg.style.transition = 'none';
            // Cập nhật tọa độ mới
            translateX = e.clientX - startX;
            translateY = e.clientY - startY;
            lightboxImg.style.transform = `translate(${translateX}px, ${translateY}px) scale(${scale})`;
        }
    });

    // --- 6. NHẢ CHUỘT: KẾT THÚC KÉO ---
    window.addEventListener('mouseup', function() {
        if (isDragging) {
            isDragging = false;
            lightboxImg.classList.replace('grabbing', 'grab'); // Bàn tay mở ra
        }
    });
});
</script>
<?php require_once 'includes/footer.php'; ?>