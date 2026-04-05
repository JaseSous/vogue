<?php
require_once '../includes/db.php';
require_once 'inc_header.php';

// ==========================================
// THỰC HIỆN TRUY VẤN LẤY THỐNG KÊ THỰC TẾ
// ==========================================

// 1. Đếm sản phẩm đang bán (chỉ đếm những SP có status là 'active')
$sql_products = "SELECT COUNT(id) as total FROM products WHERE status = 'visible'";
$res_products = $conn->query($sql_products);
$total_products = $res_products->fetch_assoc()['total'] ?? 0;

// 2. Đếm đơn hàng chờ xử lý (status là 'pending')
$sql_orders = "SELECT COUNT(id) as total FROM orders WHERE status = 'pending'";
$res_orders = $conn->query($sql_orders);
$total_orders = $res_orders->fetch_assoc()['total'] ?? 0;

// 3. Đếm tổng số khách hàng (user có role là 'customer')
$sql_customers = "SELECT COUNT(id) as total FROM users WHERE role = 'customer'";
$res_customers = $conn->query($sql_customers);
$total_customers = $res_customers->fetch_assoc()['total'] ?? 0;
?>

<h2>Bảng Điều Khiển</h2>
<p style="margin-top: 10px; color: #666;">Chào mừng bạn đến với khu vực quản trị của VOGUE. Vui lòng chọn chức năng từ
    menu bên trái.</p>

<div style="display: flex; gap: 20px; margin-top: 30px;">
    <div
        style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
        <h3 style="font-size: 32px; margin-bottom: 10px;"><?php echo $total_products; ?></h3>
        <p style="text-transform: uppercase; font-size: 12px; color: #666; font-weight: bold;">Sản phẩm đang bán</p>
    </div>

    <div
        style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
        <h3 style="font-size: 32px; margin-bottom: 10px;"><?php echo $total_orders; ?></h3>
        <p style="text-transform: uppercase; font-size: 12px; color: #666; font-weight: bold;">Đơn hàng chờ xử lý</p>
    </div>

    <div
        style="flex: 1; background: #fff; padding: 20px; border: 1px solid #ddd; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
        <h3 style="font-size: 32px; margin-bottom: 10px;"><?php echo $total_customers; ?></h3>
        <p style="text-transform: uppercase; font-size: 12px; color: #666; font-weight: bold;">Tổng khách hàng</p>
    </div>
</div>

<?php
require_once 'inc_footer.php';
?>