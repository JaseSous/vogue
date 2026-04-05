<?php
require_once '../includes/db.php'; // Đảm bảo đường dẫn file db.php của bạn là chính xác
require_once 'inc_header.php';

// ==========================================
// 1. LẤY DỮ LIỆU CHO 3 THẺ TỔNG QUAN Ở TRÊN
// ==========================================
$sql_products = "SELECT COUNT(id) as total FROM products WHERE status = 'visible'";
$total_products = $conn->query($sql_products)->fetch_assoc()['total'] ?? 0;

$sql_orders = "SELECT COUNT(id) as total FROM orders WHERE status = 'pending'";
$total_orders = $conn->query($sql_orders)->fetch_assoc()['total'] ?? 0;

$sql_customers = "SELECT COUNT(id) as total FROM users WHERE role = 'customer'";
$total_customers = $conn->query($sql_customers)->fetch_assoc()['total'] ?? 0;

// ==========================================
// 2. LẤY DỮ LIỆU CHO BIỂU ĐỒ TRÒN (Sản phẩm theo Danh mục)
// ==========================================
$sql_cat_chart = "SELECT c.name, COUNT(p.id) as count 
                  FROM categories c 
                  LEFT JOIN products p ON c.id = p.category_id 
                  GROUP BY c.id";
$res_cat = $conn->query($sql_cat_chart);

$cat_labels = [];
$cat_data = [];
while ($row = $res_cat->fetch_assoc()) {
    $cat_labels[] = $row['name'];
    $cat_data[] = $row['count'];
}

// ==========================================
// 3. LẤY DỮ LIỆU CHO BIỂU ĐỒ CỘT (Trạng thái đơn hàng)
// ==========================================
// Khởi tạo mảng mặc định để lỡ trạng thái nào bằng 0 thì biểu đồ vẫn hiện
$order_statuses = ['pending' => 0, 'confirmed' => 0, 'successful' => 0, 'cancelled' => 0];
$sql_status_chart = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$res_status = $conn->query($sql_status_chart);

while ($row = $res_status->fetch_assoc()) {
    if (isset($order_statuses[$row['status']])) {
        $order_statuses[$row['status']] = $row['count'];
    }
}
?>

<h2>Bảng Điều Khiển</h2>
<p style="margin-top: 10px; color: #666;">Chào mừng bạn đến với khu vực quản trị của VOGUE. Dưới đây là tổng quan tình
    hình kinh doanh.</p>

<div style="display: flex; gap: 20px; margin-top: 30px; flex-wrap: wrap;">
    <div
        style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border: 1px solid #ddd; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.02); border-bottom: 3px solid #000;">
        <h3 style="font-size: 32px; margin-bottom: 10px;"><?php echo $total_products; ?></h3>
        <p style="text-transform: uppercase; font-size: 12px; color: #666; font-weight: bold;">Sản phẩm đang bán</p>
    </div>

    <div
        style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border: 1px solid #ddd; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.02); border-bottom: 3px solid #d9534f;">
        <h3 style="font-size: 32px; margin-bottom: 10px; color: #d9534f;"><?php echo $total_orders; ?></h3>
        <p style="text-transform: uppercase; font-size: 12px; color: #d9534f; font-weight: bold;">Đơn hàng chờ xử lý</p>
    </div>

    <div
        style="flex: 1; min-width: 200px; background: #fff; padding: 20px; border: 1px solid #ddd; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.02); border-bottom: 3px solid #0275d8;">
        <h3 style="font-size: 32px; margin-bottom: 10px;"><?php echo $total_customers; ?></h3>
        <p style="text-transform: uppercase; font-size: 12px; color: #666; font-weight: bold;">Tổng khách hàng</p>
    </div>
</div>

<div style="display: flex; gap: 20px; margin-top: 30px; flex-wrap: wrap;">
    <div
        style="flex: 1; min-width: 300px; background: #fff; padding: 20px; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
        <h4 style="text-transform: uppercase; font-size: 14px; margin-bottom: 20px; text-align: center; color: #333;">Tỉ
            lệ sản phẩm theo danh mục</h4>
        <div style="position: relative; height: 300px; width: 100%; display: flex; justify-content: center;">
            <canvas id="categoryChart"></canvas>
        </div>
    </div>

    <div
        style="flex: 2; min-width: 400px; background: #fff; padding: 20px; border: 1px solid #ddd; box-shadow: 0 2px 5px rgba(0,0,0,0.02);">
        <h4 style="text-transform: uppercase; font-size: 14px; margin-bottom: 20px; text-align: center; color: #333;">
            Thống kê trạng thái đơn hàng</h4>
        <div style="position: relative; height: 300px; width: 100%;">
            <canvas id="statusChart"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Cấu hình Biểu đồ Tròn (Danh mục)
        const ctxCategory = document.getElementById('categoryChart').getContext('2d');
        new Chart(ctxCategory, {
            type: 'doughnut', // Dạng bánh donut hiện đại
            data: {
                labels: <?php echo json_encode($cat_labels); ?>, // Dữ liệu lấy từ PHP truyền sang JS
                datasets: [{
                    data: <?php echo json_encode($cat_data); ?>,
                    backgroundColor: [
                        '#000000', // Đen Vogue
                        '#888888', // Xám
                        '#cccccc', // Xám nhạt
                        '#d9534f', // Đỏ
                        '#f0ad4e'  // Vàng
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });

        // 2. Cấu hình Biểu đồ Cột (Trạng thái đơn hàng)
        const ctxStatus = document.getElementById('statusChart').getContext('2d');
        new Chart(ctxStatus, {
            type: 'bar', // Dạng cột
            data: {
                labels: ['Chờ xử lý', 'Đã xác nhận', 'Thành công', 'Đã hủy'],
                datasets: [{
                    label: 'Số lượng đơn hàng',
                    data: [
                        <?php echo $order_statuses['pending']; ?>,
                        <?php echo $order_statuses['confirmed']; ?>,
                        <?php echo $order_statuses['successful']; ?>,
                        <?php echo $order_statuses['cancelled']; ?>
                    ],
                    backgroundColor: [
                        'rgba(217, 83, 79, 0.8)',  // Đỏ (Pending)
                        'rgba(240, 173, 78, 0.8)', // Vàng (Confirmed)
                        'rgba(92, 184, 92, 0.8)',  // Xanh lá (Successful)
                        'rgba(153, 153, 153, 0.8)' // Xám (Cancelled)
                    ],
                    borderRadius: 4 // Bo góc cột nhìn cho mềm mại
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 } // Nhảy số nguyên (1, 2, 3 đơn hàng)
                    }
                },
                plugins: {
                    legend: { display: false } // Ẩn chú thích vì chỉ có 1 cột dữ liệu
                }
            }
        });
    });
</script>

<?php
require_once 'inc_footer.php';
?>