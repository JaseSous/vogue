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
    
    <style>
        /* 1. Ép layout header cứng 3 cột không bị lệch */
        .header-inner { display: flex; justify-content: space-between; align-items: center; }
        .header-inner > .logo { flex: 1; }
        .header-inner > nav { flex: 1; display: flex; justify-content: center; }
        .header-inner > .user-actions { flex: 1; display: flex; justify-content: flex-end; gap: 20px; align-items: center; }
        .user-actions a, .user-actions button { color: #000; transition: opacity 0.3s; }
        .user-actions a:hover, .user-actions button:hover { opacity: 0.5; }

        /* 2. HIỆU ỨNG TÌM KIẾM 2 BƯỚC (LINE TRƯỚC -> CHỮ SAU) */
        #js-search-form { display: flex; align-items: center; margin: 0; }
        
        /* 2. HIỆU ỨNG TÌM KIẾM 2 BƯỚC (LINE TRƯỚC -> CHỮ SAU) */
        #js-search-form { display: flex; align-items: center; margin: 0; }
        
        #js-search-input {
            width: 0px;
            /* FIX LỖI CHẠY XÉO: Giữ nguyên padding trên/dưới là 5px, chỉ thu padding trái/phải về 0 */
            padding: 5px 0px; 
            margin-right: 0px;
            border: none;
            border-bottom: 1px solid #000;
            background: transparent;
            outline: none;
            font-size: 13px;
            
            /* TRẠNG THÁI ĐÓNG: Ẩn chữ và con trỏ chuột */
            color: transparent; 
            caret-color: transparent; 
            
            /* KHI ĐÓNG: Chữ biến mất cực nhanh (0.1s), sau đó đường kẻ mới rụt lại (delay 0.1s) */
            transition: color 0.1s ease, caret-color 0.1s ease, width 0.4s ease 0.1s, padding 0.4s ease 0.1s, margin-right 0.4s ease 0.1s;
        }

        /* Ẩn chữ mờ Placeholder khi đóng */
        #js-search-input::placeholder {
            color: transparent;
            transition: color 0.1s ease;
        }

        /* TRẠNG THÁI MỞ (Kích hoạt class .active) */
        #js-search-input.active {
            width: 200px;
            /* Mở padding trái/phải ra 10px, padding trên/dưới vẫn giữ 5px */
            padding: 5px 10px; 
            margin-right: 10px;
            
            /* Hiện chữ và con trỏ chuột */
            color: #000;
            caret-color: #000;
            
            /* KHI MỞ: Đường kẻ dài ra ngay lập tức (0.4s). Chữ phải ĐỢI 0.4s sau mới được hiện (delay 0.4s) */
            transition: width 0.4s ease, padding 0.4s ease, margin-right 0.4s ease, color 0.3s ease 0.4s, caret-color 0.3s ease 0.4s;
        }

        /* Hiện chữ mờ Placeholder sau khi đường kẻ chạy xong */
        #js-search-input.active::placeholder {
            color: #999;
            transition: color 0.3s ease 0.4s;
        }

        /* HIỆU ỨNG DROPDOWN MENU USER */
        .user-dropdown-content a:hover { background-color: #f9f9f9; }

        /* Hiệu ứng hover in đậm cho menu Danh mục và menu User */
        .dropdown-content a:hover, 
        .user-dropdown-content a:hover {
            font-weight: bold;
            color: #000 !important;
            background-color: #f9f9f9;
            opacity: 1 !important;
        }

        /* Hiệu ứng hover nút logout */
        .user-dropdown-content a[href="logout.php"]:hover {
            color: #d9534f !important;
        }
    </style>
</head>
<body>

<header style="border-bottom: 1px solid #eeeeee; padding: 20px 0; background-color: #ffffff; position: relative; z-index: 9999;">
    <div class="container header-inner">
        <div class="logo">
            <a href="index.php" style="font-family: 'Times New Roman', Times, serif; font-size: 32px; font-weight: 700; letter-spacing: 4px; text-transform: uppercase;">VOGUE</a>
        </div>

        <nav>
            <?php
            $header_categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
            ?>
            <ul class="nav-links" style="display: flex; gap: 30px; list-style: none; margin: 0; padding: 0;">
                <li><a href="shop.php" style="font-size: 14px; font-weight: 500; text-transform: uppercase; letter-spacing: 1px;">CỬA HÀNG</a></li>
                <li class="dropdown" style="position: relative;" id="js-dropdown-menu">
                    <a href="javascript:void(0)" class="dropbtn" style="font-size: 14px; font-weight: 500; text-transform: uppercase; letter-spacing: 1px;">DANH MỤC ▾</a>
                    <div class="dropdown-content" id="js-dropdown-content" style="display: none; position: absolute; background: #fff; min-width: 200px; box-shadow: 0 8px 16px rgba(0,0,0,0.08); top: 100%; left: 0; border: 1px solid #eee; padding: 10px 0; z-index: 100;">
                        <?php if($header_categories && $header_categories->num_rows > 0): ?>
                            <?php while($h_cat = $header_categories->fetch_assoc()): ?>
                                <a href="shop.php?category=<?php echo $h_cat['id']; ?>" style="display: block; padding: 12px 20px; font-size: 13px; text-transform: uppercase;">
                                    <?php echo htmlspecialchars($h_cat['name']); ?>
                                </a>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <a href="#" style="display: block; padding: 12px 20px; font-size: 13px;">Chưa có danh mục</a>
                        <?php endif; ?>
                    </div>
                </li>
            </ul>
        </nav>

        <div class="user-actions">
            
            <div class="search-container">
                <form action="shop.php" method="GET" id="js-search-form">
                    <input type="text" name="search" id="js-search-input" placeholder="Tìm kiếm...">
                    
                    <button type="button" id="js-search-btn" aria-label="Tìm kiếm" style="background: none; border: none; cursor: pointer; padding: 0; display: flex; align-items: center;">
                        <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                    </button>
                </form>
            </div>
            
            <div class="user-dropdown-wrapper" id="js-user-dropdown" style="position: relative; display: flex; align-items: center; padding: 10px 0;">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <span style="font-size: 13px; font-weight: bold; text-transform: uppercase; margin-right: 5px; user-select: none; cursor: default;">
                        Chào, <?php 
                            $name_parts = explode(' ', trim($_SESSION['user_fullname']));
                            echo htmlspecialchars(end($name_parts)); 
                        ?>
                    </span>
                    <a href="profile.php" aria-label="Tài khoản" style="display: flex; align-items: center;">
                        <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </a>
                    
                    <div class="user-dropdown-content" id="js-user-dropdown-content" style="display: none; position: absolute; right: 0; top: 100%; background-color: #fff; min-width: 200px; box-shadow: 0 8px 16px rgba(0,0,0,0.08); border: 1px solid #eee; padding: 10px 0; z-index: 100;">
                        <a href="profile.php" style="display: block; padding: 12px 20px; font-size: 13px; text-decoration: none; color: #333; text-transform: uppercase;">Hồ sơ tài khoản</a>
                        <a href="history.php" style="display: block; padding: 12px 20px; font-size: 13px; text-decoration: none; color: #333; text-transform: uppercase;">Lịch sử mua hàng</a>
                        <a href="logout.php" style="display: block; padding: 12px 20px; font-size: 13px; text-decoration: none; color: #d9534f; text-transform: uppercase; border-top: 1px solid #eee;">Đăng xuất</a>
                    </div>
                <?php else: ?>
                    <a href="login.php" aria-label="Tài khoản" title="Đăng nhập" style="display: flex; align-items: center;">
                        <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                    </a>
                <?php endif; ?>
            </div>

            <a href="cart.php" aria-label="Giỏ hàng" class="cart-icon" title="Giỏ hàng" style="display: flex; align-items: center;">
                <svg viewBox="0 0 24 24" width="22" height="22" stroke="currentColor" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"></path><line x1="3" y1="6" x2="21" y2="6"></line><path d="M16 10a4 4 0 0 1-8 0"></path></svg>
            </a>
        </div>
    </div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var searchBtn = document.getElementById('js-search-btn');
    var searchInput = document.getElementById('js-search-input');
    var searchForm = document.getElementById('js-search-form');
    
    if(searchBtn && searchInput && searchForm) {
        searchBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (searchInput.classList.contains('active')) {
                // ĐANG MỞ -> Nếu có chữ thì tìm kiếm
                if (searchInput.value.trim() !== '') {
                    searchForm.submit();
                } else {
                    // Trống thì ĐÓNG LẠI (Chỉ cần gỡ class active, CSS tự lo hiệu ứng thu về)
                    searchInput.classList.remove('active');
                    searchInput.blur(); // Bỏ con trỏ chuột ra ngoài
                }
            } else {
                // ĐANG ĐÓNG -> Thêm class active để mở ra
                searchInput.classList.add('active');
                
                // Cực kỳ tinh tế: Đợi đúng 0.4s (thời gian đường kẻ chạy xong) mới nháy con trỏ chuột vào
                setTimeout(function() {
                    searchInput.focus();
                }, 400);
            }
        });

        // Click vào form không bị đóng
        searchForm.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Click ra ngoài khoảng trắng thì đóng tìm kiếm
        document.addEventListener('click', function(e) {
            if (searchInput.classList.contains('active')) {
                searchInput.classList.remove('active');
                searchInput.blur();
            }
        });
    }

    // Hover Dropdown Categories
    var dropdownLi = document.getElementById('js-dropdown-menu');
    var dropdownContent = document.getElementById('js-dropdown-content');
    if(dropdownLi && dropdownContent) {
        dropdownLi.addEventListener('mouseenter', function() {
            dropdownContent.style.display = 'block';
        });
        dropdownLi.addEventListener('mouseleave', function() {
            dropdownContent.style.display = 'none';
        });
    }

    // Hover Dropdown User
    var userDropdownWrapper = document.getElementById('js-user-dropdown');
    var userDropdownContent = document.getElementById('js-user-dropdown-content');
    if(userDropdownWrapper && userDropdownContent) {
        userDropdownWrapper.addEventListener('mouseenter', function() {
            userDropdownContent.style.display = 'block';
        });
        userDropdownWrapper.addEventListener('mouseleave', function() {
            userDropdownContent.style.display = 'none';
        });
    }
});
</script>
</header>