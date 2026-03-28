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
            <ul class="nav-links">
                <li><a href="index.php">Cửa hàng</a></li>
                <li><a href="#">Nam</a></li>
                <li><a href="#">Nữ</a></li>
                <li><a href="#">Phụ kiện</a></li>
            </ul>
        </nav>

        <div class="user-actions">
            <div class="search-container">
                <form action="index.php" method="GET" id="search-form">
                    <input type="text" name="search" id="search-input" placeholder="Tìm kiếm...">
                    <button type="button" id="search-btn" aria-label="Tìm kiếm">
                        <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </form>
            </div>
            
            <?php 
                // Định tuyến: Đã đăng nhập -> vào lịch sử/tài khoản; Chưa -> vào login
                $profile_link = isset($_SESSION['user_id']) ? 'history.php' : 'login.php'; 
            ?>
            <a href="<?php echo $profile_link; ?>" aria-label="Tài khoản">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
            </a>

            <a href="cart.php" aria-label="Giỏ hàng" class="cart-icon">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            </a>
        </div>
    </div>
</header>

<main>
    <div class="container">