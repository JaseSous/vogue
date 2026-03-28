<?php
$host = "localhost";
$user = "root"; // Mặc định của XAMPP
$pass = "";     // Mặc định của XAMPP (thường bỏ trống)
$dbname = "vogue_db";

// Tạo kết nối bằng MySQLi
$conn = new mysqli($host, $user, $pass, $dbname);

// Kiểm tra kết nối
if ($conn->connect_error) {
    die("Kết nối CSDL thất bại: " . $conn->connect_error);
}

// Set charset utf8 để không bị lỗi font tiếng Việt (Yêu cầu quan trọng để tránh trừ điểm)
$conn->set_charset("utf8mb4");
?>