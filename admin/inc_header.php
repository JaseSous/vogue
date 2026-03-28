<?php
session_start();
require_once '../includes/db.php';

// Kiểm tra nếu chưa đăng nhập thì đuổi về trang login
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGUE - Admin Dashboard</title>
    <style>
        /* CSS Giao diện Admin - Tối giản, Trắng Đen */
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; }
        h1, h2, h3, h4 { font-family: 'Times New Roman', Times, serif; font-weight: normal; }
        body { display: flex; min-height: 100vh; background-color: #f9f9f9; color: #000; }
        
        /* Sidebar bên trái */
        .sidebar { width: 250px; background-color: #000; color: #fff; display: flex; flex-direction: column; }
        .sidebar .logo { padding: 20px; font-family: 'Times New Roman', Times, serif; font-size: 28px; font-weight: bold; letter-spacing: 3px; text-transform: uppercase; text-align: center; border-bottom: 1px solid #333; }
        .sidebar .logo a { color: #fff; text-decoration: none; }
        .sidebar .logo span { font-size: 12px; display: block; letter-spacing: 2px; font-family: sans-serif; font-weight: normal; margin-top: 5px; }
        
        .nav-menu { flex: 1; padding-top: 20px; list-style: none; }
        .nav-menu li { border-bottom: 1px solid #222; }
        .nav-menu a { display: block; padding: 15px 20px; color: #ccc; text-decoration: none; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; transition: 0.3s; }
        .nav-menu a:hover, .nav-menu a.active { background-color: #fff; color: #000; }
        
        /* Khu vực nội dung chính */
        .main-content { flex: 1; display: flex; flex-direction: column; }
        
        /* Thanh Topbar hiển thị thông tin đăng nhập */
        .topbar { background-color: #fff; padding: 15px 30px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        .admin-info { font-size: 14px; }
        .btn-logout { background-color: #000; color: #fff; padding: 8px 15px; text-decoration: none; font-size: 12px; text-transform: uppercase; transition: 0.3s; }
        .btn-logout:hover { opacity: 0.7; }

        /* Container cho nội dung từng trang */
        .content-area { padding: 30px; flex: 1; overflow-y: auto; }
        
        /* Table dùng chung cho Admin */
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: #fff; }
        table th, table td { border: 1px solid #ddd; padding: 12px; text-align: left; font-size: 14px; }
        table th { background-color: #000; color: #fff; text-transform: uppercase; font-family: 'Times New Roman', Times, serif; font-weight: normal; letter-spacing: 1px; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="logo">
        <a href="dashboard.php">VOGUE<span>Workspace</span></a>
    </div>
    <ul class="nav-menu">
        <li><a href="dashboard.php">Dashboard</a></li>
        <li><a href="manage_users.php">Quản lý người dùng</a></li>
        <li><a href="manage_categories.php">Quản lý danh mục</a></li>
        <li><a href="manage_products.php">Quản lý sản phẩm</a></li>
        <li><a href="manage_imports.php">Quản lý nhập hàng</a></li>
        <li><a href="manage_prices.php">Quản lý giá bán</a></li>
        <li><a href="manage_orders.php">Quản lý đơn hàng</a></li>
        <li><a href="reports.php">Thống kê & Tồn kho</a></li>
    </ul>
</aside>

<div class="main-content">
    <header class="topbar">
        <div class="admin-info">
            Xin chào, <strong><?php echo $_SESSION['admin_name']; ?></strong>
        </div>
        <a href="logout.php" class="btn-logout">Đăng xuất</a>
    </header>
    
    <div class="content-area">