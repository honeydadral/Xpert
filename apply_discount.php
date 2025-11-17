<?php
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require 'includes/connect_db.php';

// Verify user is Sales Staff with manager status
$user_id = $_SESSION['user_id'];
$query = "SELECT role, manager_status FROM users WHERE user_id = ?";
$stmt = $dbc->prepare($query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    if ($row['role'] !== 'Sales Staff' || $row['manager_status'] != 1) {
        header('Location: view_packages.php');
        exit();
    }
} else {
    header('Location: login.php');
    exit();
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $package_id = $_POST['package_id'] ?? '';
    $discount_type = $_POST['discount_type'] ?? '';
    $discount_value = floatval($_POST['discount_value'] ?? 0);

    // Fetch original price
    $query = "SELECT price FROM packages WHERE package_id = ?";
    $stmt = $dbc->prepare($query);
    $stmt->bind_param('i', $package_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $original_price = floatval($row['price']);
        $new_price = $original_price;

        // Calculate new price
        if ($discount_type === 'percentage') {
            $new_price = $original_price * (1 - $discount_value / 100);
        } elseif ($discount_type === 'fixed') {
            $new_price = $original_price - $discount_value;
        }

        // Ensure new price is not negative
        $new_price = max(0, $new_price);

        // Update package
        $query = "UPDATE packages SET price_after_discount = ? WHERE package_id = ?";
        $stmt = $dbc->prepare($query);
        $stmt->bind_param('di', $new_price, $package_id);
        if ($stmt->execute()) {
            header('Location: view_packages.php?success=Discount applied successfully');
        } else {
            header('Location: view_packages.php?error=Failed to apply discount');
        }
    } else {
        header('Location: view_packages.php?error=Package not found');
    }
    $stmt->close();
} else {
    header('Location: view_packages.php');
}

mysqli_close($dbc);
exit();
?>