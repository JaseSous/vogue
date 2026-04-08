-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql101.infinityfree.com
-- Generation Time: Apr 07, 2026 at 11:21 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41584104_vogue`
--
CREATE DATABASE IF NOT EXISTS `if0_41584104_vogue` DEFAULT CHARACTER SET latin1 COLLATE latin1_swedish_ci;
USE `if0_41584104_vogue`;

-- --------------------------------------------------------

--
-- Table structure for table `addresses`
--

CREATE TABLE `addresses` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `receiver_name` varchar(100) NOT NULL,
  `receiver_phone` varchar(20) NOT NULL,
  `address_line` varchar(255) NOT NULL,
  `ward` varchar(100) NOT NULL,
  `district` varchar(100) NOT NULL,
  `city` varchar(100) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `addresses`
--

INSERT INTO `addresses` (`id`, `user_id`, `receiver_name`, `receiver_phone`, `address_line`, `ward`, `district`, `city`, `is_default`) VALUES
(1, 2, 'Nguyễn Mạnh Thắng', '0936159691', '39/9 Hồ Đắc Di', 'Tây Thạnh', 'Tân Phú', 'Hồ Chí Minh', 1),
(2, 3, 'hẹ hẹ', '1234', '12 dg 1', 'hẹ hẹ', 'hẹ hẹ', 'hẹ hẹ', 1),
(3, 4, 'Tran Huynh Phat', '0768847633', '4124125', '1251221', '4124125', '124123', 1);

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `description` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Áo khoác', ''),
(2, 'Áo thun & Áo nỉ', ''),
(3, 'Áo sơ mi & Áo polo', ''),
(4, 'Quần', '');

-- --------------------------------------------------------

--
-- Table structure for table `import_batches`
--

CREATE TABLE `import_batches` (
  `id` int(11) NOT NULL,
  `receipt_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `import_price` decimal(15,2) NOT NULL,
  `quantity_imported` int(11) NOT NULL,
  `quantity_remaining` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `import_batches`
--

INSERT INTO `import_batches` (`id`, `receipt_id`, `product_id`, `import_price`, `quantity_imported`, `quantity_remaining`) VALUES
(1, 1, 1, '1100000.00', 30, 29),
(2, 1, 6, '685000.00', 20, 20),
(3, 1, 3, '910000.00', 20, 20),
(4, 2, 14, '900000.00', 10, 10),
(6, 3, 12, '130000.00', 50, 50);

-- --------------------------------------------------------

--
-- Table structure for table `import_receipts`
--

CREATE TABLE `import_receipts` (
  `id` int(11) NOT NULL,
  `import_date` datetime DEFAULT current_timestamp(),
  `status` enum('pending','completed') DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `import_receipts`
--

INSERT INTO `import_receipts` (`id`, `import_date`, `status`, `created_by`) VALUES
(1, '2026-04-05 16:26:05', 'completed', 1),
(2, '2026-04-06 22:39:45', 'completed', 1),
(3, '2026-04-07 06:06:40', 'completed', 1),
(4, '2026-04-07 07:05:10', 'pending', 1);

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_date` datetime DEFAULT current_timestamp(),
  `total_amount` decimal(15,2) NOT NULL,
  `payment_method` enum('cash','transfer','online') NOT NULL,
  `shipping_name` varchar(100) NOT NULL,
  `shipping_phone` varchar(20) NOT NULL,
  `shipping_address` varchar(255) NOT NULL,
  `shipping_ward` varchar(100) NOT NULL,
  `shipping_district` varchar(100) NOT NULL,
  `shipping_city` varchar(100) NOT NULL,
  `status` enum('pending','confirmed','successful','cancelled') DEFAULT 'pending'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_date`, `total_amount`, `payment_method`, `shipping_name`, `shipping_phone`, `shipping_address`, `shipping_ward`, `shipping_district`, `shipping_city`, `status`) VALUES
(1, 2, '2026-04-06 17:24:04', '2156000.00', 'cash', 'Nguyễn Mạnh Thắng', '0936159691', '39/9 Hồ Đắc Di', 'Tây Thạnh', 'Tân Phú', 'Hồ Chí Minh', 'pending'),
(2, 2, '2026-04-06 17:37:11', '4158000.00', 'cash', 'Nguyễn Mạnh Thắng', '0936159691', '39/9 Hồ Đắc Di', 'Tây Thạnh', 'Tân Phú', 'Hồ Chí Minh', 'successful'),
(3, 2, '2026-04-06 18:09:49', '4242015.70', 'transfer', 'Nguyễn Mạnh Thắng', '0936159691', '39/9 Hồ Đắc Di', 'Tây Thạnh', 'Tân Phú', 'Hồ Chí Minh', 'confirmed'),
(4, 2, '2026-04-06 18:51:51', '1794000.00', 'online', 'Nguyễn Mạnh Thắng', '0936159691', '39/9 Hồ Đắc Di', 'Tây Thạnh', 'Tân Phú', 'Hồ Chí Minh', 'confirmed'),
(5, 2, '2026-04-07 00:48:48', '1069775.70', 'transfer', 'Nguyễn Mạnh Thắng', '0936159691', '39/9 Hồ Đắc Di', 'Tây Thạnh', 'Tân Phú', 'Hồ Chí Minh', 'pending'),
(6, 3, '2026-04-07 01:01:36', '3588000.00', 'transfer', 'hẹ hẹ', '1234', '12 dg 1', 'hẹ hẹ', 'hẹ hẹ', 'hẹ hẹ', 'successful'),
(7, 4, '2026-04-07 09:16:40', '19044000.00', 'cash', 'Tran Huynh Phat', '0768847633', '4124125', '1251221', '4124125', '124123', 'cancelled'),
(8, 2, '2026-04-07 13:19:56', '528120.00', 'online', 'Nguyễn Mạnh Thắng', '0936159691', '39/9 Hồ Đắc Di', 'Tây Thạnh', 'Tân Phú', 'Hồ Chí Minh', 'pending');

-- --------------------------------------------------------

--
-- Table structure for table `order_details`
--

CREATE TABLE `order_details` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  `selling_price` decimal(15,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_details`
--

INSERT INTO `order_details` (`id`, `order_id`, `product_id`, `quantity`, `selling_price`) VALUES
(1, 1, 14, 2, '1078000.00'),
(2, 2, 14, 1, '1078000.00'),
(3, 2, 1, 2, '1540000.00'),
(4, 3, 14, 1, '1069775.70'),
(5, 3, 8, 2, '528120.00'),
(6, 3, 3, 2, '1058000.00'),
(7, 4, 4, 2, '897000.00'),
(8, 5, 14, 1, '1069775.70'),
(9, 6, 10, 8, '448500.00'),
(10, 7, 3, 18, '1058000.00'),
(11, 8, 8, 1, '528120.00');

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `category_id` int(11) NOT NULL,
  `supplier_id` int(11) NOT NULL,
  `description` text DEFAULT NULL,
  `unit` varchar(50) NOT NULL,
  `initial_quantity` int(11) DEFAULT 0,
  `cost_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `image` varchar(255) DEFAULT NULL,
  `selling_price` decimal(15,2) NOT NULL DEFAULT 0.00,
  `profit_margin` decimal(5,2) NOT NULL,
  `status` enum('visible','hidden','deleted') DEFAULT 'visible',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `code`, `name`, `category_id`, `supplier_id`, `description`, `unit`, `initial_quantity`, `cost_price`, `image`, `selling_price`, `profit_margin`, `status`, `created_at`) VALUES
(1, 'K0001', 'Áo Khoác AirSense', 1, 1, 'Điểm nổi bật\r\n- Chất liệu cực kỳ thoải mái, gọn nhẹ, co giãn và mau khô, do Toray và UNIQLO phát triển.\r\n- Chất vải co giãn hai chiều giúp bạn dễ vận động.\r\n- Kiểu dáng thanh lịch, vừa vặn.\r\n- Phom dáng ôm tự nhiên từ vai đến tay áo, phù hợp cho môi trường công sở và ngày thường.\r\n\r\nChức năng:\r\n- Dáng: Dáng suông\r\n- Túi: Có túi (Túi trong)\r\n\r\nChất liệu / Cách chăm sóc\r\n- Vải Chính: 100% Polyeste | Lớp Lót: 100% Polyeste | Vải Túi: 100% Polyeste.\r\n- Giặt máy nước lạnh, giặt nhẹ, giặt khô, không sấy khô.', 'Cái', 5, '1400000.00', 'assets/images/products/1774776662_0001 (1).png', '1540000.00', '10.00', 'visible', '2026-03-29 16:31:02'),
(2, 'K0002', 'DRY-EX Áo Hoodie Chống Tia UV Kéo Khóa', 1, 1, 'Điểm nổi bật\r\n- Chất vải lưới đặc biệt đảm bảo độ thoáng khí. Có công nghệ chống tia UV.\r\n- Công nghệ “DRY-EX” thấm hút và tản mồ hôi nhanh chóng, giúp cơ thể luôn khô thoáng.\r\n- Vải siêu co giãn đa chiều, giúp chuyển động dễ dàng.\r\n- Túi bên hông có khóa kéo, bảo vệ đồ dùng cá nhân an toàn khi tham gia các hoạt động.\r\n- Tích hợp lỗ xỏ ngón cái để bảo vệ mu bàn tay khỏi tia UV.\r\n- UPF50+.\r\n\r\nChức năng\r\n- Độ xuyên thấu: Không xuyên thấu\r\n- Dáng: Dáng suông\r\n- Túi: Có túi\r\n\r\nChất liệu / Cách chăm sóc\r\n- Thân: 100% Polyeste (39% Sử Dụng Sợi Polyeste Tái Chế) | Vải Túi: 100% Polyeste\r\n- Giặt máy nước lạnh, giặt nhẹ, không giặt khô, không sấy khô', 'Cái', 0, '500000.00', 'assets/images/products/1774781884_0002 (1).png', '600000.00', '20.00', 'visible', '2026-03-29 17:58:04'),
(3, 'K0003', 'Áo Khoác Kiểu Sơ Mi Vải Cotton Linen', 1, 4, 'Chức năng\r\n- Độ xuyên thấu: Không xuyên thấu\r\n- Dáng: Dáng thoải mái\r\n\r\nChất liệu / Cách chăm sóc\r\n- Thân: 66% Bông, 34% Lanh | Vải Túi Bên: Lớp Ngoài: 80% Polyeste, 20% Bông | Vải Túi Bên: Lớp Trong: 66% Bông, 34% Lanh.\r\n- Giặt máy nước lạnh, giặt nhẹ, giặt khô.', 'Cái', 0, '920000.00', 'assets/images/products/1774782189_0003.png', '1058000.00', '15.00', 'visible', '2026-03-29 18:03:09'),
(4, 'K0004', 'Áo Khoác Kéo Khóa Dáng Ngắn', 1, 3, 'Điểm nổi bật\r\n- Sản phẩm đã được xử lý giặt qua để mang đến vẻ ngoài tự nhiên, phóng khoáng.\r\n- Chiều dài áo ngắn, dễ phối đồ và linh hoạt trong nhiều hoàn cảnh.\r\n\r\nChức năng\r\n- Dáng: Dáng rộng thoải mái\r\n- Túi: Có túi\r\n\r\nChất liệu / Cách chăm sóc\r\n- Thân: 98% Bông, 2% Elastan/ Cổ Áo: Mặt Trước: 100% Bông/ Vải Túi: 65% Polyeste, 35% Bông\r\n- Giặt máy nước lạnh, Giặt khô, Không sấy khô', 'Cái', 2, '780000.00', 'assets/images/products/1774782459_0004.png', '897000.00', '15.00', 'visible', '2026-03-29 18:07:39'),
(5, 'K0005', 'Áo Khoác Vải Pha Linen', 1, 1, 'Chức năng\r\n- Dáng: Dáng thoải mái\r\n- Túi: Có túi\r\n\r\nChất liệu / Cách chăm sóc\r\n- 67% Bông, 33% Lanh.\r\n- Giặt tay nước lạnh, Giặt khô', 'Cái', 10, '780000.00', 'assets/images/products/1774970003_0005.png', '897000.00', '15.00', 'visible', '2026-03-31 22:13:23'),
(6, 'K0006', 'PUFFTECH Áo Khoác Chần Bông', 1, 1, 'Điểm nổi bật\r\n- Lớp đệm “PUFFTECH” nhẹ, ấm áp, tính năng tốt, được sản xuất bằng công nghệ sợi tiên tiến nhất. Sợi vải rỗng giữ nhiệt tốt, giúp bạn luôn cảm thấy ấm áp.\r\n- Chất vải chống thấm nước làm bằng công nghệ “NANODESIGN™” của Toray. Lớp phủ chống bám nước bảo vệ bạn khỏi những cơn mưa nhỏ.\r\n- Thiết kế tinh tế với khóa trượt ở đằng trước.\r\n- Lớp lót chống tĩnh điện.\r\n- Phom ôm vừa vặn.\r\n- Có thể giặt tay.\r\n\r\nChức năng\r\n- Dáng: Dáng suông.\r\n- Túi: Có túi.\r\n- Có túi đựng.\r\n\r\nChất liệu / Cách chăm sóc\r\n- Mặt Trước: 100% Nylon | Lớp Độn: 100% Polyeste | Mặt Sau: 100% Nylon | Vải Túi: 100% Polyeste.\r\n- Giặt tay nước lạnh, Không giặt khô, Không sấy khô.', 'Cái', 0, '980000.00', 'assets/images/products/1774970356_0006.png', '1078000.00', '10.00', 'visible', '2026-03-31 22:19:16'),
(7, 'K0007', 'Áo Khoác Blouson Dáng Ngắn Kéo Khóa', 1, 2, 'Điểm nổi bật\r\n- Điểm nhấn là cổ áo và tay áo bằng vải nhung gân, phối cùng lớp lót họa tiết kẻ ca-rô.\r\n- Thiết kế lấy cảm hứng từ trang phục công sở.\r\n- Kiểu dáng rộng rãi với chiều dài ngắn, rất phù hợp khi mặc với quần ống rộng.\r\n\r\nChức năng\r\n- Dáng: Dáng thoải mái.\r\n- Túi: Có túi (Túi trong).\r\n\r\nChất liệu / Cách chăm sóc\r\n- Vải Chính: 100% Bông | Lớp Lót Thân Áo: 100% Bông | Lớp Lót Tay Áo: 100% Polyeste | Cổ Áo: 100% Bông | Vải Túi: 100% Polyeste.\r\n- Giặt tay nước lạnh, Giặt khô, Không sấy khô.', 'Cái', 0, '1275000.00', 'assets/images/products/1774970589_0007.png', '1530000.00', '20.00', 'visible', '2026-03-31 22:23:09'),
(8, 'K0008', 'Áo Khoác Giả Lông Cừu Loại Dày Kéo Khóa', 1, 1, 'Điểm nổi bật\r\n- Vải lông cừu nhẹ với vòng lông dài, mềm mại, ấm áp.\r\n- Cổ đứng giúp giữ ấm.\r\n- Đường viền ở gấu áo và cổ tay áo giúp ngăn gió.\r\n- Có thể giặt máy, dễ bảo quản.\r\n\r\nChất liệu / Cách chăm sóc\r\n- Thân: Vải Túi: 100% Polyeste (100% Sử Dụng Sợi Polyeste Tái Chế).\r\n- Giặt máy nước lạnh, giặt nhẹ, Không giặt khô, Không sấy khô.', 'Cái', 20, '489000.00', 'assets/images/products/1774970765_0008.png', '528120.00', '8.00', 'visible', '2026-03-31 22:26:05'),
(9, 'K0009', 'Áo Parka Chống Tia UV Vải Sheer', 1, 4, 'Điểm nổi bật\r\n- Lớp hoàn thiện chống thấm nước, giúp đẩy lùi mưa nhẹ.\r\n- Phần mở mũ có chun co giãn giúp ngăn nước mưa lọt vào.\r\n- Eo có thể điều chỉnh bằng dây rút ẩn, tạo cảm giác tinh tế.\r\n- 100% nylon hơi trong suốt một cách nhẹ nhàng.\r\n- Áo khoác hoodie dáng ngắn với khóa kéo nylon đồng bộ.\r\n- Dài ngang hông, gấu áo bo chun mềm giúp chống mưa hiệu quả.\r\n- Chống tia UV UPF50+ / JIS L 1925 : 2019.\r\n\r\nChất liệu / Cách chăm sóc\r\n- Thân: 100% Nylon (51% Sử Dụng Sợi Nylon Tái Chế) | Lớp Lót Mũ Trùm: 100% Nylon (51% Sử Dụng Sợi Nylon Tái Chế).\r\n- Giặt máy nước lạnh, giặt nhẹ, Không giặt khô, Không sấy khô.', 'Cái', 15, '480000.00', 'assets/images/products/1774971017_0009.png', '612000.00', '27.50', 'visible', '2026-03-31 22:30:17'),
(10, 'TN0001', 'Áo Thun Cổ Henley', 2, 1, 'Điểm nổi bật\r\n- Chất liệu hoàn toàn bằng cotton mềm mại.\r\n- Logo JW ANDERSON thêu ở bên trái gấu áo.\r\n- Được cải tiến với phần phom áo rộng rãi.\r\n- Áo thun phong cách thể thao, cổ điển.\r\n- Cổ áo kiểu Henley có thể mặc riêng hoặc kết hợp nhiều lớp.\r\n\r\nChức năng\r\n- Độ xuyên thấu: Không xuyên thấu.\r\n- Dáng: Dáng thoải mái.\r\n- Túi: Không túi.\r\n\r\nChất liệu / Cách chăm sóc\r\n- 100% Bông.\r\n- Giặt máy nước lạnh, Không giặt khô, Không sấy khô.', 'Cái', 25, '390000.00', 'assets/images/products/1774971809_TN0001.png', '448500.00', '15.00', 'visible', '2026-03-31 22:43:29'),
(11, 'TN0002', 'Áo Thun Raglan', 2, 1, 'Điểm nổi bật\r\n- Chất liệu 100% cotton mang lại cảm giác thoải mái, năng động.\r\n- Logo JW ANDERSON được thêu ở phía bên trái của gấu áo.\r\n- Thiết kế có xẻ hai bên, kèm viền băng dệt kiểu xương cá.\r\n- Áo thun ringer lấy cảm hứng từ phong cách vintage.\r\n- Bảng màu đặc trưng của JW ANDERSON.\r\n\r\nChức năng\r\n- Độ xuyên thấu: Xuyên thấu nhẹ.\r\n- Dáng: Dáng thoải mái.\r\n- Túi: Không túi.\r\n\r\nChất liệu / Cách chăm sóc\r\n- 100% Bông.\r\n- Giặt máy nước lạnh, Không giặt khô, Không sấy khô.', 'Cái', 0, '380000.00', 'assets/images/products/1775385680_TN0002.png', '456000.00', '20.00', 'hidden', '2026-04-05 17:41:20'),
(12, 'TN0003', 'Áo Thun Vải Dry Cổ V', 2, 2, 'Điểm nổi bật\r\n- Tích hợp công nghệ DRY giúp khô nhanh.\r\n- Form dáng cơ bản, phù hợp mặc riêng hoặc làm lớp áo bên trong.\r\n- Cổ chữ V cổ điển.\r\n\r\nChất liệu / Cách chăm sóc\r\n- 66% Bông, 34% Polyeste (34% Sử Dụng Sợi Polyeste Tái Chế).\r\n- Giặt máy nước lạnh, Giặt khô, Không sấy khô.', 'Cái', 0, '130000.00', 'assets/images/products/1775385909_TN0003.png', '175500.00', '35.00', 'visible', '2026-04-05 17:45:09'),
(13, 'SMP0001', 'Áo Sơ Mi Vải Oxford Dáng Boxy', 3, 4, 'Điểm nổi bật\r\n- Chất liệu pha cotton–rayon mềm mại, thoải mái.\r\n- Cổ tay áo có thể điều chỉnh với hai nút cài.\r\n- Logo JW ANDERSON được thêu ở phần gấu trước.\r\n- Dáng ngắn với phom rộng.\r\n\r\nChức năng\r\n- Độ xuyên thấu: Không xuyên thấu.\r\n- Dáng: Dáng rộng thoải mái.\r\n\r\nChất liệu / Cách chăm sóc\r\n- 60% Bông, 40% Visco.\r\n- Giặt máy nước lạnh, giặt nhẹ, Giặt khô, Không sấy khô.', 'Cái', 0, '550000.00', 'assets/images/products/1775386201_SMP0001.png', '605000.00', '10.00', 'visible', '2026-04-05 17:50:01'),
(14, 'Q0001', 'Quần Jeans Baggy Ống Cong', 4, 2, 'Điểm nổi bật\r\n- Dáng quần ôm mềm mại với phần cạp cao ở eo và có độ phồng ở hai bên. Phù hợp cho mọi lứa tuổi.\r\n- Chất vải pha cotton mềm mại và thoải mái. Có thể mặc quanh năm.\r\n- Chỉ có màu XANH DƯƠNG (64 BLUE) được xử lý hiệu ứng sờn rách kiểu cổ điển.\r\n\r\nChức năng\r\n- Độ xuyên thấu: Không xuyên thấu.\r\n- Dáng: Dáng thụng.\r\n- Phom dáng: Ống ôm dần.\r\n- Túi: Có túi.\r\n- Cạp quần: Lưng cao.\r\n\r\nChất liệu / Cách chăm sóc\r\n- Thân: 79% Bông, 21% Lyocell | Vải Túi: 65% Polyeste, 35% Bông.\r\n- Giặt máy nước lạnh, giặt nhẹ, Không giặt khô, Không sấy khô.', 'Cái', 100, '972523.36', 'assets/images/products/1775387299_Q0001.png', '1069775.70', '10.00', 'visible', '2026-04-05 18:08:19');

-- --------------------------------------------------------

--
-- Table structure for table `suppliers`
--

CREATE TABLE `suppliers` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `contact_info` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `suppliers`
--

INSERT INTO `suppliers` (`id`, `name`, `contact_info`) VALUES
(1, 'VOGUE In-house Studio', 'contact@vogue.vn - 0901234567'),
(2, 'Xưởng may gia công Hà Nội', 'xuongmayhn@gmail.com - 0987654321'),
(3, 'Nguồn hàng Quảng Châu Cao Cấp', 'quangchau_import@yahoo.com - 0911222333'),
(4, 'Nhà máy dệt may Sài Gòn', 'saigontextile@vogue.com - 0909999888');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `fullname` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `role` enum('admin','customer') DEFAULT 'customer',
  `status` enum('active','locked') DEFAULT 'active',
  `created_at` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `fullname`, `email`, `phone`, `role`, `status`, `created_at`) VALUES
(1, 'admin', '$2y$10$bsGSIPsnoSKkDWd62zIu8eT76oip3BytGB6y8FV2N1vq8SwcRvPD2', 'Quản Trị Viên', 'admin@vogue.vn', '0900000000', 'admin', 'active', '2026-03-29 17:29:13'),
(2, 'JaseSous', '$2y$10$JIbL6MrTDcZ7Af0k1TR.fOWQkKD58wTleY5xeriW35eYhfjEc8FoC', 'Nguyễn Mạnh Thắng', 'jasesous@gmail.com', '0936159691', 'customer', 'active', '2026-04-06 20:28:45'),
(3, 'hẹ hẹ', '$2y$10$HGsq2qG7lSRONENc1/as1.pSTi.3jVN2XjZh4bEdLqmUjzLm3puUS', 'hẹ hẹ', 'hehe@gmail.com', '1234', 'customer', 'active', '2026-04-06 11:00:20'),
(4, 'phatkhung111lo@gmail.com', '$2y$10$U9wVkForyl/iQQQryXFVdOBEpy6WJ02KAT15xIvD1QzpzxhSang82', 'Tran Huynh Phat', 'daphatvomom0303@gmail.com', '0768847633', 'customer', 'active', '2026-04-06 19:15:29');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `addresses`
--
ALTER TABLE `addresses`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `import_batches`
--
ALTER TABLE `import_batches`
  ADD PRIMARY KEY (`id`),
  ADD KEY `receipt_id` (`receipt_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `import_receipts`
--
ALTER TABLE `import_receipts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `created_by` (`created_by`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `order_details`
--
ALTER TABLE `order_details`
  ADD PRIMARY KEY (`id`),
  ADD KEY `order_id` (`order_id`),
  ADD KEY `product_id` (`product_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`),
  ADD KEY `category_id` (`category_id`),
  ADD KEY `supplier_id` (`supplier_id`);

--
-- Indexes for table `suppliers`
--
ALTER TABLE `suppliers`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `addresses`
--
ALTER TABLE `addresses`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `import_batches`
--
ALTER TABLE `import_batches`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `import_receipts`
--
ALTER TABLE `import_receipts`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT for table `order_details`
--
ALTER TABLE `order_details`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=15;

--
-- AUTO_INCREMENT for table `suppliers`
--
ALTER TABLE `suppliers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `addresses`
--
ALTER TABLE `addresses`
  ADD CONSTRAINT `addresses_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `import_batches`
--
ALTER TABLE `import_batches`
  ADD CONSTRAINT `import_batches_ibfk_1` FOREIGN KEY (`receipt_id`) REFERENCES `import_receipts` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `import_batches_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `import_receipts`
--
ALTER TABLE `import_receipts`
  ADD CONSTRAINT `import_receipts_ibfk_1` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`);

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`);

--
-- Constraints for table `order_details`
--
ALTER TABLE `order_details`
  ADD CONSTRAINT `order_details_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_details_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`);

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`),
  ADD CONSTRAINT `products_ibfk_2` FOREIGN KEY (`supplier_id`) REFERENCES `suppliers` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
