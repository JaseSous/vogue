<?php 
require_once 'inc_header.php'; 
?>

<h2>Bảng Điều Khiển (Dashboard)</h2>
<p style="margin-top: 10px; color: #666;">Chào mừng bạn đến với khu vực quản trị của VOGUE. Vui lòng chọn chức năng từ menu bên trái.</p>

<div style="display: flex; gap: 20px; margin-top: 30px;">
    <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; text-align: center;">
        <h3 style="font-size: 24px; margin-bottom: 10px;">0</h3>
        <p style="text-transform: uppercase; font-size: 12px; color: #666;">Sản phẩm đang bán</p>
    </div>
    <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; text-align: center;">
        <h3 style="font-size: 24px; margin-bottom: 10px;">0</h3>
        <p style="text-transform: uppercase; font-size: 12px; color: #666;">Đơn hàng chờ xử lý</p>
    </div>
    <div style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; text-align: center;">
        <h3 style="font-size: 24px; margin-bottom: 10px;">0</h3>
        <p style="text-transform: uppercase; font-size: 12px; color: #666;">Tổng khách hàng</p>
    </div>
</div>

<?php 
require_once 'inc_footer.php'; 
?>