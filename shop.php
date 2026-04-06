<?php
require_once 'includes/header.php';

// --- NHẬN DỮ LIỆU TÌM KIẾM NÂNG CAO ---
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$min_price = isset($_GET['min_price']) && $_GET['min_price'] !== '' ? (float)$_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : 0;

// Lấy danh sách danh mục cho thanh Filter
$categories_query = $conn->query("SELECT * FROM categories");

// --- THIẾT LẬP PHÂN TRANG ---
$limit = 8; // Số sản phẩm trên 1 trang
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- CÂU LỆNH SQL THẦN THÁNH CỦA ĐỒ ÁN ---
// Truy vấn này lấy sản phẩm hiển thị, đồng thời tính luôn Giá Bán Cuối Cùng (final_price)
// dựa trên giá nhập của lô hàng cũ nhất còn tồn kho (FIFO) kết hợp với % lợi nhuận và giá đề xuất.
$sql = "SELECT p.*, 
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
        WHERE p.status = 'visible'";

// Gắn các điều kiện tìm kiếm
if ($search !== '') {
    $sql .= " AND p.name LIKE '%" . $conn->real_escape_string($search) . "%'";
}
if ($category > 0) {
    $sql .= " AND p.category_id = " . $category;
}

// Lọc theo khoảng giá (Phải dùng HAVING vì final_price là cột được tính toán ảo)
if ($min_price > 0 || $max_price > 0) {
    $sql .= " HAVING 1=1";
    if ($min_price > 0) $sql .= " AND final_price >= $min_price";
    if ($max_price > 0) $sql .= " AND final_price <= $max_price";
}

// Tính tổng số sản phẩm để chia trang (Gói câu SQL thành 1 bảng tạm)
$count_sql = "SELECT COUNT(*) as total FROM ($sql) as temp_table";
$total_res = $conn->query($count_sql);
$total_rows = $total_res->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Thêm sắp xếp và Limit cho phân trang
$sql .= " ORDER BY p.id DESC LIMIT $limit OFFSET $offset";
$products = $conn->query($sql);
?>

<main style="padding: 40px 0;">
    <div class="container">
        <div class="filter-bar">
            <form action="shop.php" method="GET" style="display: flex; gap: 15px; flex-wrap: wrap; width: 100%;">
                <input type="text" name="search" placeholder="Tên sản phẩm..." value="<?php echo htmlspecialchars($search); ?>" style="flex: 1; min-width: 200px;">
                
                <select name="category" style="width: 200px;">
                    <option value="0">-- Tất cả danh mục --</option>
                    <?php while($c = $categories_query->fetch_assoc()): ?>
                        <option value="<?php echo $c['id']; ?>" <?php if($category == $c['id']) echo 'selected'; ?>><?php echo $c['name']; ?></option>
                    <?php endwhile; ?>
                </select>
                
                <input type="number" name="min_price" placeholder="Giá từ (VNĐ)" value="<?php echo $min_price > 0 ? $min_price : ''; ?>" min="0" style="width: 150px;">
                <input type="number" name="max_price" placeholder="Đến giá (VNĐ)" value="<?php echo $max_price > 0 ? $max_price : ''; ?>" min="0" style="width: 150px;">
                
                <button type="submit">Lọc Sản Phẩm</button>
                <?php if($search || $category || $min_price || $max_price): ?>
                    <a href="shop.php" style="font-size: 12px; color: #666; text-decoration: underline; align-self: center;">Xoá bộ lọc</a>
                <?php endif; ?>
            </form>
        </div>

        <h2 style="margin-bottom: 30px; text-align: center;">TẤT CẢ SẢN PHẨM</h2>

        <?php if($products->num_rows > 0): ?>
            <div class="product-grid">
                <?php while($p = $products->fetch_assoc()): ?>
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

            <?php if($total_pages > 1): ?>
                <div class="pagination">
                    <?php 
                    $query_string = "";
                    if($search) $query_string .= "&search=".urlencode($search);
                    if($category) $query_string .= "&category=".$category;
                    if($min_price) $query_string .= "&min_price=".$min_price;
                    if($max_price) $query_string .= "&max_price=".$max_price;

                    // Đổi tất cả index.php thành shop.php
                    if($page > 1) {
                        echo '<a href="shop.php?page='.($page - 1).$query_string.'">&laquo;</a>';
                    }

                    for($i = 1; $i <= $total_pages; $i++) {
                        if($i == $page) {
                            echo '<span class="active">'.$i.'</span>';
                        } else {
                            echo '<a href="shop.php?page='.$i.$query_string.'">'.$i.'</a>';
                        }
                    }

                    if($page < $total_pages) {
                        echo '<a href="shop.php?page='.($page + 1).$query_string.'">&raquo;</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <p style="text-align: center; color: #666; padding: 50px 0;">Không tìm thấy sản phẩm nào phù hợp với yêu cầu của bạn.</p>
        <?php endif; ?>

    </div>
</main>
<?php require_once 'includes/footer.php'; ?>