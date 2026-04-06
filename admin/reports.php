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
            // SQL Chuẩn Bình quân gia quyền (Lấy tổng tồn kho thực tế)
            $warn_sql = "
                SELECT p.code, p.name, 
                       (p.initial_quantity 
                        + COALESCE((SELECT SUM(quantity_imported) FROM import_batches ib JOIN import_receipts ir ON ib.receipt_id = ir.id WHERE ir.status = 'completed' AND ib.product_id = p.id), 0)
                        - COALESCE((SELECT SUM(quantity) FROM order_details od JOIN orders o ON od.order_id = o.id WHERE o.status != 'cancelled' AND od.product_id = p.id), 0)
                       ) as total_stock
                FROM products p
                WHERE p.status != 'deleted'
                HAVING total_stock <= $threshold
                ORDER BY total_stock ASC
            ";
            $warnings = $conn->query($warn_sql);
            if ($warnings && $warnings->num_rows > 0):
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
    <h3 style="margin-bottom: 15px; color: #5cb85c;">2. Báo cáo Nhập - Xuất (Tổng hợp)</h3>
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
                <th style="padding: 8px; border: 1px solid #eee;">Mã SP</th>
                <th style="padding: 8px; border: 1px solid #eee;">Tên Sản Phẩm</th>
                <th style="padding: 8px; border: 1px solid #eee; text-align: right; color: #5cb85c;">Tổng Nhập Trong Kỳ</th>
                <th style="padding: 8px; border: 1px solid #eee; text-align: right; color: #d9534f;">Tổng Xuất Trong Kỳ</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $from_dt = $from_date . " 00:00:00";
            $to_dt = $to_date . " 23:59:59";
            
            // Logic: Lấy tổng SL nhập và xuất của từng sản phẩm trong khoảng thời gian
            $io_sql = "
                SELECT p.code, p.name,
                       COALESCE((SELECT SUM(ib.quantity_imported) FROM import_batches ib JOIN import_receipts ir ON ib.receipt_id = ir.id 
                                 WHERE ib.product_id = p.id AND ir.status = 'completed' AND ir.import_date BETWEEN '$from_dt' AND '$to_dt'), 0) as imported_qty,
                       COALESCE((SELECT SUM(od.quantity) FROM order_details od JOIN orders o ON od.order_id = o.id 
                                 WHERE od.product_id = p.id AND o.status != 'cancelled' AND o.order_date BETWEEN '$from_dt' AND '$to_dt'), 0) as exported_qty
                FROM products p
                WHERE p.status != 'deleted'
                HAVING imported_qty > 0 OR exported_qty > 0
                ORDER BY p.code ASC
            ";
            $io_report = $conn->query($io_sql);
            if ($io_report && $io_report->num_rows > 0):
                while($io = $io_report->fetch_assoc()):
            ?>
            <tr>
                <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;"><?php echo $io['code']; ?></td>
                <td style="padding: 8px; border: 1px solid #eee;"><?php echo htmlspecialchars($io['name']); ?></td>
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
    <h3 style="margin-bottom: 15px; color: #f0ad4e;">3. Tra cứu Tồn kho tại một thời điểm</h3>
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
                <th style="padding: 8px; border: 1px solid #eee; text-align: right;">Tồn Ban Đầu</th>
                <th style="padding: 8px; border: 1px solid #eee; text-align: right;">Tổng Nhập (Đến T)</th>
                <th style="padding: 8px; border: 1px solid #eee; text-align: right;">Tổng Xuất (Đến T)</th>
                <th style="padding: 8px; border: 1px solid #eee; text-align: right; color: #000;">Tồn Kho Tại T</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($cat_id > 0) {
                $target_dt = str_replace('T', ' ', $target_time) . ":59";
                
                // Logic: Tính tổng Nhập, tổng Xuất trước thời điểm T
                $stock_sql = "
                    SELECT p.code, p.name, p.initial_quantity,
                           COALESCE((SELECT SUM(ib.quantity_imported) FROM import_batches ib JOIN import_receipts ir ON ib.receipt_id = ir.id 
                                     WHERE ib.product_id = p.id AND ir.status = 'completed' AND ir.import_date <= '$target_dt'), 0) as imported_qty,
                           COALESCE((SELECT SUM(od.quantity) FROM order_details od JOIN orders o ON od.order_id = o.id 
                                     WHERE od.product_id = p.id AND o.status != 'cancelled' AND o.order_date <= '$target_dt'), 0) as exported_qty
                    FROM products p
                    WHERE p.category_id = $cat_id AND p.status != 'deleted'
                    ORDER BY p.code ASC
                ";
                $stock_report = $conn->query($stock_sql);
                
                if ($stock_report && $stock_report->num_rows > 0):
                    while($st = $stock_report->fetch_assoc()):
                        // Tồn tại T = Ban đầu + Nhập - Xuất
                        $stock_at_time = $st['initial_quantity'] + $st['imported_qty'] - $st['exported_qty'];
                ?>
                <tr>
                    <td style="padding: 8px; border: 1px solid #eee; font-weight: bold;"><?php echo $st['code']; ?></td>
                    <td style="padding: 8px; border: 1px solid #eee;"><?php echo htmlspecialchars($st['name']); ?></td>
                    <td style="padding: 8px; border: 1px solid #eee; text-align: right;"><?php echo $st['initial_quantity']; ?></td>
                    <td style="padding: 8px; border: 1px solid #eee; text-align: right;"><?php echo $st['imported_qty']; ?></td>
                    <td style="padding: 8px; border: 1px solid #eee; text-align: right;"><?php echo $st['exported_qty']; ?></td>
                    <td style="padding: 8px; border: 1px solid #eee; text-align: right; font-weight: bold; font-size: 15px;"><?php echo $stock_at_time; ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="6" style="padding: 8px; text-align: center;">Không có dữ liệu cho danh mục này.</td></tr>
                <?php endif; 
            } else {
                echo '<tr><td colspan="6" style="padding: 20px; text-align: center; color: #666;">Vui lòng chọn loại sản phẩm và ấn Tra Cứu để xem kết quả.</td></tr>';
            }
            ?>
        </tbody>
    </table>
</div>

<?php require_once 'inc_footer.php'; ?>