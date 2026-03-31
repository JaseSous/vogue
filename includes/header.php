<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db.php'; 
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGUE - Minimalist Fashion</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<header>
    <div class="container header-inner">
        <div class="logo">
            <a href="index.php">VOGUE</a>
        </div>

        <nav>
            <?php
            // Lấy danh sách danh mục từ database để hiển thị lên Menu
            $header_categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
            ?>
            <ul class="nav-links">
                <li><a href="shop.php">CỬA HÀNG</a></li>
                
                <li class="dropdown">
                    <a href="javascript:void(0)" class="dropbtn">DANH MỤC ▾</a>
                    <div class="dropdown-content">
                        <?php if($header_categories && $header_categories->num_rows > 0): ?>
                            <?php while($h_cat = $header_categories->fetch_assoc()): ?>
                                <a href="shop.php?category=<?php echo $h_cat['id']; ?>">
                                    <?php echo htmlspecialchars($h_cat['name']); ?>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <a href="#">Chưa có danh mục</a>
                        <?php endif; ?>
                    </div>
                </li>
            </ul>
        </nav>

        
        <div class="user-actions" style="display: flex; align-items: center; gap: 20px;">
            <div class="search-container">
                <form action="shop.php" method="GET" id="search-form">
                    <input type="text" name="search" id="search-input" placeholder="Tìm kiếm...">
                    <button type="button" id="search-btn" aria-label="Tìm kiếm">
                        <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </form>
            </div>
            
            <?php if(isset($_SESSION['user_id'])): ?>
                <span style="font-size: 13px; font-weight: bold; text-transform: uppercase;">
                    Chào, <?php 
                        $name_parts = explode(' ', trim($_SESSION['user_fullname']));
                        echo htmlspecialchars(end($name_parts)); 
                    ?>
                </span>
                
                <a href="history.php" aria-label="Lịch sử đơn hàng" title="Lịch sử đơn hàng">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </a>
                
                <a href="logout.php" aria-label="Đăng xuất" title="Đăng xuất" style="color: #d9534f;">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                </a>
            <?php else: ?>
                <a href="login.php" aria-label="Tài khoản" title="Đăng nhập">
                    <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                </a>
            <?php endif; ?>

            <a href="cart.php" aria-label="Giỏ hàng" class="cart-icon" title="Giỏ hàng">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            </a>
        </div>
    </div>
</header>