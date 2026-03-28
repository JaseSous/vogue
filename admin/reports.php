<?php
require_once 'inc_header.php';

// Khởi tạo các biến mặc định cho form
$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$target_time = isset($_GET['target_time']) ? $_GET['target_time'] : date('Y-m-d\TH:i');

$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-t');

$threshold = isset($_GET['threshold']) ? (int)$_GET['threshold'] : 10;

// Lấy danh sách danh mục cho Dropdown
$categories = $conn->query("SELECT * FROM categories");

?>

<h2 style="margin-bottom: 20px;">Thống Kê & Báo Cáo Tồn Kho</h2>

<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-top: 3px solid #d9534f; margin-bottom: 30px;">
    <h3 style="margin-bottom: 15px; color: #d9534f;">1. Cảnh báo sản phẩm sắp hết hàng</h3>
    <form action="reports.php" method="GET" style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
        <label style="font-size: 14px; font-weight: bold;">Ngưỡng cảnh báo (Số lượng <=) :</label>
        <input type="number" name="threshold" value="<?php echo $threshold; ?>" min="0" style="padding: 5px; width: 80px; text-align: center; border: 1px solid #ccc;">
        
        <input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>">
        <input type="hidden" name="target_time" value="<?php echo $target_time; ?>">
        <input type="hidden" name="from_date" value="<?php echo $from_date; ?>">
        <input type="hidden" name="to_date" value="<?php echo $to_date; ?>">
        
        <button type="submit" style="background: #000; color: #fff; border: none; padding: 6px 15px; cursor: pointer;">Xem Cảnh Báo</button>
    </form>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f9f9f9;">
                <th style="padding: 8px; border: 1px solid #eee;">Mã SP</th>
                <th style="padding: 8px; border: 1px solid #eee;">Tên Sản Phẩm</th>
                <th style="padding: 8px; border: 1px solid #eee; text-align: right;">Tổng Tồn Kho Hiện Tại</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Truy vấn lấy tổng tồn (Số lượng ban đầu + tổng số lượng còn lại của các lô)
            $warn_sql = "
                SELECT p.code, p.name, 
                       (p.initial_quantity + COALESCE((SELECT SUM(quantity_remaining) FROM import_batches WHERE product_id = p.id), 0)) as total_stock
                FROM products p
                WHERE p.status != 'deleted'
                HAVING total_stock <= $threshold
                ORDER BY total_stock ASC
            ";
            $warnings = $conn->query($warn_sql);
            if ($warnings->num_rows > 0):
                while($w = $warnings->fetch_assoc()):
            ?>
            <tr>
                <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;"><?php echo $w['code']; ?></td>
                <td style="padding: 8px; border: 1px solid #eee;"><?php echo htmlspecialchars($w['name']); ?></td>
                <td style="padding: 8px; border: 1px solid #eee; text-align: right; color: #d9534f; font-weight: bold;"><?php echo $w['total_stock']; ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="3" style="padding: 8px; text-align: center;">Không có sản phẩm nào dưới ngưỡng <?php echo $threshold; ?>.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-top: 3px solid #5cb85c; margin-bottom: 30px;">
    <h3 style="margin-bottom: 15px; color: #5cb85c;">2. Báo cáo Nhập - Xuất (Theo lô)</h3>
    <form action="reports.php" method="GET" style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px;">
        <label style="font-size: 14px; font-weight: bold;">Từ ngày:</label>
        <input type="date" name="from_date" value="<?php echo $from_date; ?>" style="padding: 5px; border: 1px solid #ccc;">
        
        <label style="font-size: 14px; font-weight: bold;">Đến ngày:</label>
        <input type="date" name="to_date" value="<?php echo $to_date; ?>" style="padding: 5px; border: 1px solid #ccc;">
        
        <input type="hidden" name="cat_id" value="<?php echo $cat_id; ?>">
        <input type="hidden" name="target_time" value="<?php echo $target_time; ?>">
        <input type="hidden" name="threshold" value="<?php echo $threshold; ?>">

        <button type="submit" style="background: #000; color: #fff; border: none; padding: 6px 15px; cursor: pointer;">Xem Báo Cáo</button>
    </form>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f9f9f9;">
                <th style="padding: 8px; border: 1px solid #eee;">Sản Phẩm</th>
                <th style="padding: 8px; border: 1px solid #eee;">Mã Lô (Phiếu)</th>
                <th style="padding: 8px; border: 1px solid #eee; text-align: right; color: #5cb85c;">SL Nhập Trong Kỳ</th>
                <th style="padding: 8px; border: 1px solid #eee; text-align: right; color: #d9534f;">SL Xuất Trong Kỳ</th>
            </tr>
        </thead>
        <tbody>
            <?php
            // Logic: Lấy SL nhập theo ngày phiếu nhập, lấy SL xuất theo ngày đơn hàng (đã chốt)
            $from_dt = $from_date . " 00:00:00";
            $to_dt = $to_date . " 23:59:59";
            
            $io_sql = "
                SELECT p.code, p.name, b.id as batch_id, r.id as receipt_id,
                       (CASE WHEN r.import_date BETWEEN '$from_dt' AND '$to_dt' THEN b.quantity_imported ELSE 0 END) as imported_qty,
                       COALESCE((SELECT SUM(od.quantity) FROM order_details od JOIN orders o ON od.order_id = o.id 
                                 WHERE od.batch_id = b.id AND o.status IN ('confirmed', 'successful') AND o.order_date BETWEEN '$from_dt' AND '$to_dt'), 0) as exported_qty
                FROM import_batches b
                JOIN products p ON b.product_id = p.id
                JOIN import_receipts r ON b.receipt_id = r.id
                HAVING imported_qty > 0 OR exported_qty > 0
                ORDER BY p.code ASC, b.id ASC
            ";
            $io_report = $conn->query($io_sql);
            if ($io_report->num_rows > 0):
                while($io = $io_report->fetch_assoc()):
            ?>
            <tr>
                <td style="padding: 8px; border: 1px solid #eee;">[<?php echo $io['code']; ?>] <?php echo htmlspecialchars($io['name']); ?></td>
                <td style="padding: 8px; border: 1px solid #eee;">Lô #<?php echo $io['batch_id']; ?> (PN-<?php echo $io['receipt_id']; ?>)</td>
                <td style="padding: 8px; border: 1px solid #eee; text-align: right; color: #5cb85c; font-weight: bold;"><?php echo $io['imported_qty']; ?></td>
                <td style="padding: 8px; border: 1px solid #eee; text-align: right; color: #d9534f; font-weight: bold;"><?php echo $io['exported_qty']; ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="4" style="padding: 8px; text-align: center;">Không có phát sinh Nhập/Xuất nào trong khoảng thời gian này.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<div style="background: #fff; padding: 20px; border: 1px solid #ddd; border-top: 3px solid #f0ad4e; margin-bottom: 30px;">
    <h3 style="margin-bottom: 15px; color: #f0ad4e;">3. Tra cứu Tồn kho tại một thời điểm (Phân tách theo lô)</h3>
    <form action="reports.php" method="GET" style="margin-bottom: 15px; display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        
        <label style="font-size: 14px; font-weight: bold;">Loại sản phẩm:</label>
        <select name="cat_id" style="padding: 6px; border: 1px solid #ccc;">
            <option value="0">-- Chọn danh mục --</option>
            <?php while($c = $categories->fetch_assoc()): ?>
                <option value="<?php echo $c['id']; ?>" <?php if($cat_id == $c['id']) echo 'selected'; ?>><?php echo $c['name']; ?></option>
            <?php endwhile; ?>
        </select>

        <label style="font-size: 14px; font-weight: bold; margin-left: 10px;">Tại thời điểm:</label>
        <input type="datetime-local" name="target_time" value="<?php echo $target_time; ?>" style="padding: 5px; border: 1px solid #ccc;">
        
        <input type="hidden" name="from_date" value="<?php echo $from_date; ?>">
        <input type="hidden" name="to_date" value="<?php echo $to_date; ?>">
        <input type="hidden" name="threshold" value="<?php echo $threshold; ?>">

        <button type="submit" style="background: #000; color: #fff; border: none; padding: 6px 15px; cursor: pointer;">Tra Cứu</button>
    </form>

    <table style="width: 100%; border-collapse: collapse;">
        <thead>
            <tr style="background: #f9f9f9;">
                <th style="padding: 8px; border: 1px solid #eee;">Mã SP</th>
                <th style="padding: 8px; border: 1px solid #eee;">Tên Sản Phẩm</th>
                <th style="padding: 8px; border: 1px solid #eee;">Mã Lô</th>
                <th style="padding: 8px; border: 1px solid #eee; text-align: right;">Đã Nhập (Tính đến T)</th>
                <th style="padding: 8px; border: 1px solid #eee; text-align: right;">Đã Xuất (Tính đến T)</th>
                <th style="padding: 8px; border: 1px solid #eee; text-align: right; color: #000;">Tồn Kho (Lô) tại T</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($cat_id > 0) {
                // Logic: Tồn tại thời điểm T = SL Nhập (của các phiếu có ngày <= T) trừ SL Xuất (của các đơn có ngày <= T)
                $target_dt = str_replace('T', ' ', $target_time) . ":59"; // Format lại chuẩn datetime SQL
                
                $stock_sql = "
                    SELECT p.code, p.name, b.id as batch_id,
                           b.quantity_imported,
                           COALESCE((SELECT SUM(od.quantity) FROM order_details od JOIN orders o ON od.order_id = o.id 
                                     WHERE od.batch_id = b.id AND o.status IN ('confirmed', 'successful') AND o.order_date <= '$target_dt'), 0) as exported_qty
                    FROM import_batches b
                    JOIN products p ON b.product_id = p.id
                    JOIN import_receipts r ON b.receipt_id = r.id
                    WHERE p.category_id = $cat_id AND r.import_date <= '$target_dt' AND r.status = 'completed'
                    ORDER BY p.code ASC, b.id ASC
                ";
                $stock_report = $conn->query($stock_sql);
                
                if ($stock_report->num_rows > 0):
                    while($st = $stock_report->fetch_assoc()):
                        $stock_at_time = $st['quantity_imported'] - $st['exported_qty'];
                ?>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee;"><?php echo $st['code']; ?></td>
                    <td style="padding: 8px; border: 1px solid #eee;"><?php echo htmlspecialchars($st['name']); ?></td>
                    <td style="padding: 8px; border: 1px solid #eee;">Lô #<?php echo $st['batch_id']; ?></td>
                    <td style="padding: 8px; border: 1px solid #eee; text-align: right;"><?php echo $st['quantity_imported']; ?></td>
                    <td style="padding: 8px; border: 1px solid #eee; text-align: right;"><?php echo $st['exported_qty']; ?></td>
                    <td style="padding: 8px; border: 1px solid #eee; text-align: right; font-weight: bold; font-size: 15px;"><?php echo $stock_at_time; ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="6" style="padding: 8px; text-align: center;">Không có lô hàng nào của danh mục này được nhập trước thời điểm đã chọn.</td></tr>
                <?php endif; 
            } else {
                echo '<tr><td colspan="6" style="padding: 20px; text-align: center; color: #666;">Vui lòng chọn loại sản phẩm và ấn Tra Cứu để xem kết quả.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php require_once 'inc_footer.php'; ?>