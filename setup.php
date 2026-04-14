<?php

// ============================================================
// CONFIGURATION — Edit these values
// ============================================================
$SPANEL_URL      = 'https://app.3ecandy.com/spanel/api.php';  // your server IP
$SPANEL_TOKEN    = '5a75273fcb5103e147620b0f2bf9e03d79c62848';                         // your SPanel token
$SPANEL_ACCOUNT  = 'ecandycom';                              // your main cPanel username

$DB_NAME         = 'ecandy_db';                              // will become ecandycom_ecandy_db
$DB_USER         = 'nssi';                                   // will become ecandycom_nssi
$DB_PASS         = 'NSSI@2026!';

$DB_FULL_NAME    = $SPANEL_ACCOUNT . '_' . $DB_NAME;
$DB_FULL_USER    = $SPANEL_ACCOUNT . '_' . $DB_USER;

// ============================================================
// HELPER FUNCTION — SPanel API caller
// ============================================================
function callSPanel($url, $token, $account, $action, $extra = []) {
    $postData = array_merge([
        'token'       => $token,
        'accountuser' => $account,
        'action'      => $action,
    ], $extra);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $result = curl_exec($ch);
    $error  = curl_error($ch);
    curl_close($ch);

    if ($error) {
        return ['result' => 'error', 'msg' => $error];
    }

    return json_decode($result, true) ?? ['result' => 'error', 'msg' => 'Invalid JSON response'];
}

// ============================================================
// STEP 1 — Create Database
// ============================================================
// NEW
echo "<h2>Step 1: Creating Database...</h2>";
$res = callSPanel($SPANEL_URL, $SPANEL_TOKEN, $SPANEL_ACCOUNT, 'mysql/createmysqldatabase', [
    'database' => $DB_NAME,
]);
echo "<pre>" . json_encode($res, JSON_PRETTY_PRINT) . "</pre>";

if ($res['result'] === 'success') {
    echo "<p style='color:green'>✅ Database created successfully.</p>";
} elseif (str_contains(strtolower($res['msg'] ?? ''), 'exist')) {
    echo "<p style='color:orange'>⚠️ Database already exists, continuing...</p>";
} else {
    die("<p style='color:red'>❌ Failed to create database. Stopping.</p>");
}
// ============================================================
// STEP 2 — Create Database User
// ============================================================
// NEW
echo "<h2>Step 2: Creating Database User...</h2>";
$res = callSPanel($SPANEL_URL, $SPANEL_TOKEN, $SPANEL_ACCOUNT, 'mysql/createmysqluser', [
    'username' => $DB_USER,
    'password' => $DB_PASS,
]);
echo "<pre>" . json_encode($res, JSON_PRETTY_PRINT) . "</pre>";

if ($res['result'] === 'success') {
    echo "<p style='color:green'>✅ User created successfully.</p>";
} elseif (str_contains(strtolower($res['msg'] ?? ''), 'exist')) {
    echo "<p style='color:orange'>⚠️ User already exists, continuing...</p>";
} else {
    die("<p style='color:red'>❌ Failed to create user. Stopping.</p>");
}

// ============================================================
// STEP 3 — Assign User to Database
// ============================================================
// NEW
echo "<h2>Step 3: Assigning User to Database...</h2>";
$res = callSPanel($SPANEL_URL, $SPANEL_TOKEN, $SPANEL_ACCOUNT, 'mysql/assignmysqluser', [
    'username'   => $DB_FULL_USER,
    'database'   => $DB_FULL_NAME,
    'privileges' => 'ALL',
    'hostname'   => 'localhost',
]);
echo "<pre>" . json_encode($res, JSON_PRETTY_PRINT) . "</pre>";

if ($res['result'] === 'success') {
    echo "<p style='color:green'>✅ User assigned to database successfully.</p>";
} elseif (str_contains(strtolower($res['msg'] ?? ''), 'exist') || 
          str_contains(strtolower($res['msg'] ?? ''), 'already')) {
    echo "<p style='color:orange'>⚠️ User already assigned, continuing...</p>";
} else {
    die("<p style='color:red'>❌ Failed to assign user to database. Stopping.</p>");
}

// ============================================================
// STEP 4 — Create Tables via PDO
// ============================================================
echo "<h2>Step 4: Creating Tables...</h2>";
    // NEW - mysqli
    $conn = mysqli_connect('localhost', $DB_FULL_USER, $DB_PASS, $DB_FULL_NAME);

    if (!$conn) {
        die("<p style='color:red'>❌ Connection Error: " . mysqli_connect_error() . "</p>");
    }

    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=0");

    $statements = [

        "CREATE TABLE IF NOT EXISTS `roles` (
            `role_id` int(11) NOT NULL AUTO_INCREMENT,
            `role_name` varchar(50) NOT NULL,
            `description` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`role_id`),
            UNIQUE KEY `role_name` (`role_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `modules` (
            `module_id` int(11) NOT NULL AUTO_INCREMENT,
            `module_name` varchar(100) NOT NULL,
            `description` varchar(255) DEFAULT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            PRIMARY KEY (`module_id`),
            UNIQUE KEY `module_name` (`module_name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `stores` (
            `store_id` int(11) NOT NULL AUTO_INCREMENT,
            `store_name` varchar(100) NOT NULL,
            `address` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `warehouses` (
            `warehouse_id` int(11) NOT NULL AUTO_INCREMENT,
            `warehouse_name` varchar(100) NOT NULL,
            `address` varchar(255) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`warehouse_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `products` (
            `product_code` varchar(20) NOT NULL,
            `item_description` varchar(255) NOT NULL,
            `unit_price` decimal(12,2) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`product_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `users` (
            `user_id` int(11) NOT NULL AUTO_INCREMENT,
            `username` varchar(50) NOT NULL,
            `full_name` varchar(255) NOT NULL,
            `email` varchar(100) NOT NULL,
            `password` varchar(255) NOT NULL,
            `role_id` int(11) NOT NULL,
            `is_active` tinyint(1) DEFAULT 1,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `store_id` int(11) DEFAULT NULL,
            `warehouse_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`user_id`),
            UNIQUE KEY `username` (`username`),
            UNIQUE KEY `email` (`email`),
            KEY `role_id` (`role_id`),
            KEY `users_store_fk` (`store_id`),
            KEY `users_warehouse_fk` (`warehouse_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `role_permissions` (
            `permission_id` int(11) NOT NULL AUTO_INCREMENT,
            `role_id` int(11) NOT NULL,
            `module_id` int(11) NOT NULL,
            `can_access` tinyint(1) DEFAULT 0,
            PRIMARY KEY (`permission_id`),
            UNIQUE KEY `role_id` (`role_id`, `module_id`),
            KEY `module_id` (`module_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `transactions` (
            `transaction_id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_code` varchar(50) NOT NULL,
            `user_id` int(11) NOT NULL,
            `store_id` int(11) NOT NULL,
            `total_amount` decimal(14,2) NOT NULL,
            `amount_tendered` decimal(14,2) NOT NULL,
            `change_amount` decimal(14,2) NOT NULL,
            `transaction_no` varchar(50) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`transaction_id`),
            UNIQUE KEY `transaction_no` (`transaction_no`),
            KEY `user_id` (`user_id`),
            KEY `store_id` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `transaction_items` (
            `item_id` int(11) NOT NULL AUTO_INCREMENT,
            `transaction_id` int(11) NOT NULL,
            `product_code` varchar(20) NOT NULL,
            `item_name` varchar(255) NOT NULL,
            `unit_price` decimal(12,2) NOT NULL,
            `quantity` decimal(12,4) NOT NULL,
            `subtotal` decimal(14,2) NOT NULL,
            PRIMARY KEY (`item_id`),
            KEY `transaction_id` (`transaction_id`),
            KEY `product_code` (`product_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `customer_transaction_summary` (
            `summary_id` int(11) NOT NULL AUTO_INCREMENT,
            `customer_code` varchar(50) NOT NULL,
            `transaction_id` int(11) NOT NULL,
            `transaction_no` varchar(50) NOT NULL,
            `items_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL CHECK (json_valid(`items_json`)),
            `total_amount` decimal(14,2) NOT NULL,
            `amount_tendered` decimal(14,2) NOT NULL,
            `change_amount` decimal(14,2) NOT NULL,
            `transacted_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`summary_id`),
            UNIQUE KEY `transaction_id` (`transaction_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `deliveries` (
            `delivery_id` int(11) NOT NULL AUTO_INCREMENT,
            `po_number` varchar(50) NOT NULL,
            `warehouse_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'RECEIVED',
            `delivery_date` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`delivery_id`),
            UNIQUE KEY `po_number` (`po_number`),
            KEY `warehouse_id` (`warehouse_id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `delivery_items` (
            `delivery_item_id` int(11) NOT NULL AUTO_INCREMENT,
            `delivery_id` int(11) NOT NULL,
            `product_code` varchar(20) NOT NULL,
            `quantity` int(11) NOT NULL,
            `unit_price` decimal(12,2) NOT NULL,
            `subtotal` decimal(14,2) NOT NULL,
            PRIMARY KEY (`delivery_item_id`),
            KEY `delivery_id` (`delivery_id`),
            KEY `product_code` (`product_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `disposals` (
            `disposal_id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `warehouse_id` int(11) NOT NULL,
            `reason` varchar(255) NOT NULL,
            `notes` text DEFAULT NULL,
            `disposal_date` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`disposal_id`),
            KEY `user_id` (`user_id`),
            KEY `warehouse_id` (`warehouse_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `disposal_items` (
            `disposal_item_id` int(11) NOT NULL AUTO_INCREMENT,
            `disposal_id` int(11) NOT NULL,
            `product_code` varchar(20) NOT NULL,
            `quantity` decimal(12,4) NOT NULL,
            `unit_price` decimal(12,2) NOT NULL,
            `total_loss` decimal(14,2) NOT NULL,
            PRIMARY KEY (`disposal_item_id`),
            KEY `disposal_id` (`disposal_id`),
            KEY `product_code` (`product_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `stock_transfers` (
            `transfer_id` int(11) NOT NULL AUTO_INCREMENT,
            `warehouse_id` int(11) NOT NULL,
            `store_id` int(11) NOT NULL,
            `user_id` int(11) NOT NULL,
            `transfer_no` varchar(50) NOT NULL,
            `status` varchar(20) NOT NULL DEFAULT 'PENDING',
            `transfer_date` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`transfer_id`),
            UNIQUE KEY `transfer_no` (`transfer_no`),
            KEY `warehouse_id` (`warehouse_id`),
            KEY `store_id` (`store_id`),
            KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `stock_transfer_items` (
            `transfer_item_id` int(11) NOT NULL AUTO_INCREMENT,
            `transfer_id` int(11) NOT NULL,
            `product_code` varchar(20) NOT NULL,
            `quantity` decimal(12,4) NOT NULL,
            `unit_price` decimal(12,2) NOT NULL,
            `subtotal` decimal(14,2) NOT NULL,
            PRIMARY KEY (`transfer_item_id`),
            KEY `transfer_id` (`transfer_id`),
            KEY `product_code` (`product_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `store_product_prices` (
            `price_id` int(11) NOT NULL AUTO_INCREMENT,
            `store_id` int(11) NOT NULL,
            `product_code` varchar(20) NOT NULL,
            `unit_price` decimal(12,2) NOT NULL,
            `price_overridden` tinyint(1) NOT NULL DEFAULT 0,
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`price_id`),
            UNIQUE KEY `store_product` (`store_id`, `product_code`),
            KEY `product_code` (`product_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `inventory_snapshots` (
            `snapshot_id` int(11) NOT NULL AUTO_INCREMENT,
            `store_id` int(11) NOT NULL,
            `as_of_date` date NOT NULL,
            `submission_date` date DEFAULT NULL,
            `annex` varchar(10) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`snapshot_id`),
            KEY `store_id` (`store_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `inventory_items` (
            `item_id` int(11) NOT NULL AUTO_INCREMENT,
            `snapshot_id` int(11) NOT NULL,
            `product_code` varchar(20) NOT NULL,
            `unit_price` decimal(12,2) NOT NULL,
            `quantity_in_stock` int(11) NOT NULL,
            `total_cost` decimal(14,2) NOT NULL,
            `transfer_id` int(11) DEFAULT NULL,
            PRIMARY KEY (`item_id`),
            KEY `snapshot_id` (`snapshot_id`),
            KEY `product_code` (`product_code`),
            KEY `inventory_items_transfer_fk` (`transfer_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `warehouse_inventory` (
            `warehouse_item_id` int(11) NOT NULL AUTO_INCREMENT,
            `warehouse_id` int(11) NOT NULL,
            `product_code` varchar(20) NOT NULL,
            `quantity` int(11) DEFAULT NULL,
            `unit_price` decimal(12,2) NOT NULL,
            `total_cost` decimal(14,2) NOT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
            PRIMARY KEY (`warehouse_item_id`),
            KEY `warehouse_id` (`warehouse_id`),
            KEY `product_code` (`product_code`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `refunds` (
            `refund_id` int(11) NOT NULL AUTO_INCREMENT,
            `transaction_id` int(11) DEFAULT NULL,
            `user_id` int(11) NOT NULL,
            `store_id` int(11) NOT NULL,
            `reason` varchar(255) NOT NULL,
            `total_amount` decimal(14,2) NOT NULL DEFAULT 0.00,
            `refund_date` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`refund_id`),
            KEY `user_id` (`user_id`),
            KEY `store_id` (`store_id`),
            KEY `transaction_id` (`transaction_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `refund_items` (
            `refund_item_id` int(11) NOT NULL AUTO_INCREMENT,
            `refund_id` int(11) NOT NULL,
            `product_code` varchar(20) NOT NULL,
            `item_name` varchar(255) NOT NULL,
            `quantity` decimal(12,4) NOT NULL,
            `unit_price` decimal(12,2) NOT NULL,
            `subtotal` decimal(14,2) NOT NULL,
            PRIMARY KEY (`refund_item_id`),
            KEY `refund_id` (`refund_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `activity_logs` (
            `log_id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) NOT NULL,
            `module_id` int(11) NOT NULL,
            `action` varchar(100) NOT NULL,
            `description` text DEFAULT NULL,
            `ip_address` varchar(45) DEFAULT NULL,
            `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`log_id`),
            KEY `user_id` (`user_id`),
            KEY `module_id` (`module_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        "CREATE TABLE IF NOT EXISTS `user_activity_logs` (
            `log_id` int(11) NOT NULL AUTO_INCREMENT,
            `user_id` int(11) DEFAULT NULL,
            `action` varchar(60) NOT NULL,
            `detail` text DEFAULT NULL,
            `performed_by` int(11) DEFAULT NULL,
            `created_at` datetime NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`log_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci",

        // FOREIGN KEYS
        "ALTER TABLE `users`
            ADD CONSTRAINT `users_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
            ADD CONSTRAINT `users_store_fk` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
            ADD CONSTRAINT `users_warehouse_fk` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`) ON DELETE SET NULL",

        "ALTER TABLE `role_permissions`
            ADD CONSTRAINT `role_permissions_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`role_id`),
            ADD CONSTRAINT `role_permissions_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`)",

        "ALTER TABLE `transactions`
            ADD CONSTRAINT `transactions_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
            ADD CONSTRAINT `transactions_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`)",

        "ALTER TABLE `transaction_items`
            ADD CONSTRAINT `transaction_items_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`),
            ADD CONSTRAINT `transaction_items_ibfk_2` FOREIGN KEY (`product_code`) REFERENCES `products` (`product_code`)",

        "ALTER TABLE `customer_transaction_summary`
            ADD CONSTRAINT `customer_transaction_summary_ibfk_1` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`)",

        "ALTER TABLE `deliveries`
            ADD CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
            ADD CONSTRAINT `deliveries_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)",

        "ALTER TABLE `delivery_items`
            ADD CONSTRAINT `delivery_items_ibfk_1` FOREIGN KEY (`delivery_id`) REFERENCES `deliveries` (`delivery_id`),
            ADD CONSTRAINT `delivery_items_ibfk_2` FOREIGN KEY (`product_code`) REFERENCES `products` (`product_code`)",

        "ALTER TABLE `disposals`
            ADD CONSTRAINT `disposals_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
            ADD CONSTRAINT `disposals_ibfk_2` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`)",

        "ALTER TABLE `disposal_items`
            ADD CONSTRAINT `disposal_items_ibfk_1` FOREIGN KEY (`disposal_id`) REFERENCES `disposals` (`disposal_id`) ON DELETE CASCADE,
            ADD CONSTRAINT `disposal_items_ibfk_2` FOREIGN KEY (`product_code`) REFERENCES `products` (`product_code`)",

        "ALTER TABLE `stock_transfers`
            ADD CONSTRAINT `stock_transfers_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
            ADD CONSTRAINT `stock_transfers_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
            ADD CONSTRAINT `stock_transfers_ibfk_3` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`)",

        "ALTER TABLE `stock_transfer_items`
            ADD CONSTRAINT `stock_transfer_items_ibfk_1` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`transfer_id`),
            ADD CONSTRAINT `stock_transfer_items_ibfk_2` FOREIGN KEY (`product_code`) REFERENCES `products` (`product_code`)",

        "ALTER TABLE `store_product_prices`
            ADD CONSTRAINT `store_product_prices_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
            ADD CONSTRAINT `store_product_prices_ibfk_2` FOREIGN KEY (`product_code`) REFERENCES `products` (`product_code`)",

        "ALTER TABLE `inventory_snapshots`
            ADD CONSTRAINT `inventory_snapshots_ibfk_1` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`)",

        "ALTER TABLE `inventory_items`
            ADD CONSTRAINT `inventory_items_ibfk_1` FOREIGN KEY (`snapshot_id`) REFERENCES `inventory_snapshots` (`snapshot_id`),
            ADD CONSTRAINT `inventory_items_ibfk_2` FOREIGN KEY (`product_code`) REFERENCES `products` (`product_code`),
            ADD CONSTRAINT `inventory_items_transfer_fk` FOREIGN KEY (`transfer_id`) REFERENCES `stock_transfers` (`transfer_id`)",

        "ALTER TABLE `warehouse_inventory`
            ADD CONSTRAINT `warehouse_inventory_ibfk_1` FOREIGN KEY (`warehouse_id`) REFERENCES `warehouses` (`warehouse_id`),
            ADD CONSTRAINT `warehouse_inventory_ibfk_2` FOREIGN KEY (`product_code`) REFERENCES `products` (`product_code`)",

        "ALTER TABLE `refunds`
            ADD CONSTRAINT `refunds_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
            ADD CONSTRAINT `refunds_ibfk_2` FOREIGN KEY (`store_id`) REFERENCES `stores` (`store_id`),
            ADD CONSTRAINT `refunds_ibfk_3` FOREIGN KEY (`transaction_id`) REFERENCES `transactions` (`transaction_id`) ON DELETE SET NULL",

        "ALTER TABLE `refund_items`
            ADD CONSTRAINT `refund_items_ibfk_1` FOREIGN KEY (`refund_id`) REFERENCES `refunds` (`refund_id`) ON DELETE CASCADE",

        "ALTER TABLE `activity_logs`
            ADD CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`),
            ADD CONSTRAINT `activity_logs_ibfk_2` FOREIGN KEY (`module_id`) REFERENCES `modules` (`module_id`)",
    ];

    $created = [];
    $errors  = [];

    foreach ($statements as $sql) {
        if (mysqli_query($conn, $sql)) {
            preg_match('/`(\w+)`/', $sql, $match);
            $created[] = $match[1] ?? 'statement';
        } else {
            $errors[] = mysqli_error($conn);
        }
    }

    mysqli_query($conn, "SET FOREIGN_KEY_CHECKS=1");
    mysqli_close($conn);

    echo "<h2>Step 4 Result:</h2>";
    echo "<pre>" . json_encode([
        'status'  => count($errors) === 0 ? 'success' : 'partial',
        'created' => $created,
        'errors'  => $errors,
    ], JSON_PRETTY_PRINT) . "</pre>";

    if (count($errors) === 0) {
        echo "<h2 style='color:green'>✅ All tables created successfully!</h2>";
    } else {
        echo "<h2 style='color:orange'>⚠️ Done with some errors. Check above.</h2>";
    }
