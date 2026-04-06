<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra quyền truy cập của Admin
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

if (isset($_GET['id'])) {
    $product_id = (int)$_GET['id'];

    // 1. Kiểm tra xem sản phẩm này đã từng được nhập hàng chưa
    // Bằng cách đếm xem có dòng dữ liệu nào của sản phẩm này trong bảng import_batches không
    $check_import = $conn->query("SELECT id FROM import_batches WHERE product_id = $product_id LIMIT 1");
    $check_order = $conn->query("SELECT id FROM order_details WHERE product_id = $product_id LIMIT 1");

    if ($check_import->num_rows > 0 || $check_order->num_rows > 0) {
        // TRƯỜNG HỢP A: SẢN PHẨM ĐÃ ĐƯỢC NHẬP HÀNG -> XOÁ MỀM
        // Cập nhật trạng thái thành 'deleted' để ẩn hoàn toàn khỏi danh sách bán và danh sách quản trị
        // nhưng vẫn giữ lại ID và tên trong CSDL để phục vụ tra cứu lịch sử đơn hàng/phiếu nhập.
        $stmt = $conn->prepare("UPDATE products SET status = 'deleted' WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            $msg = "Đã đánh dấu ẨN sản phẩm này. Không thể xoá vĩnh viễn vì sản phẩm đã có lịch sử nhập/xuất hàng!";
        } else {
            $msg = "Có lỗi xảy ra khi ẩn sản phẩm: " . $conn->error;
        }

    } else {
        // TRƯỜNG HỢP B: SẢN PHẨM CHƯA TỪNG NHẬP HÀNG -> XOÁ CỨNG (XOÁ HẲN)
        
        // Bước phụ: Lấy đường dẫn ảnh để xoá file vật lý trên server nhằm tiết kiệm dung lượng
        $get_img = $conn->query("SELECT image FROM products WHERE id = $product_id");
        if ($get_img->num_rows > 0) {
            $row = $get_img->fetch_assoc();
            if (!empty($row['image'])) {
                $image_path = "../" . $row['image']; // Đường dẫn lùi 1 cấp từ thư mục admin
                if (file_exists($image_path)) {
                    unlink($image_path); // Hàm unlink dùng để xoá file trong PHP
                }
            }
        }
        
        // Tiến hành lệnh DELETE hẳn khỏi CSDL
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        
        if ($stmt->execute()) {
            $msg = "Đã XOÁ VĨNH VIỄN sản phẩm khỏi cơ sở dữ liệu thành công!";
        } else {
            $msg = "Có lỗi xảy ra khi xoá sản phẩm: " . $conn->error;
        }
    }

    // Dùng JavaScript để hiển thị thông báo kết quả cho quản trị viên và quay lại trang quản lý
    echo "<script>
        alert('$msg');
        window.location.href = 'manage_products.php';
    </script>";
    exit();

} else {
    // Nếu không có ID truyền vào thì đẩy về trang quản lý
    header("Location: manage_products.php");
    exit();
}
?>