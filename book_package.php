<?php
session_start();

// Redirect if not Sales Staff
if (!isset($_SESSION['user_id'])) {
    require 'login_tools.php';
    load();
}

// Connect to the database
require 'includes/connect_db.php';

// Set page title
$page_title = 'Xpert - Book Package';

// Validate and process form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $package_id = filter_input(INPUT_POST, 'package_id', FILTER_VALIDATE_INT);
    $event_date = trim($_POST['event_date'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $user_id = $_SESSION['user_id'];

    // Validate inputs
    $errors = [];
    if (!$package_id) {
        $errors[] = 'Invalid package ID.';
    }
    if (empty($event_date) || !strtotime($event_date)) {
        $errors[] = 'Event date is required and must be a valid date.';
    }

    if (empty($errors)) {
        // Prepare and execute the insert query
        $query = "INSERT INTO bookings (user_id, package_id, event_date, notes, booking_date) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $dbc->prepare($query);
        if ($stmt) {
            $stmt->bind_param('iiss', $user_id, $package_id, $event_date, $notes);
            if ($stmt->execute()) {
                // Redirect to view_packages.php with success message
                $stmt->close();
                mysqli_close($dbc);
                header('Location: view_packages.php?success=Booking created successfully.');
                exit();
            } else {
                $errors[] = 'Failed to create booking: ' . htmlspecialchars($stmt->error);
            }
            $stmt->close();
        } else {
            $errors[] = 'Database error: ' . htmlspecialchars($dbc->error);
        }
    }
} else {
    $errors[] = 'Invalid request method.';
}

// If there are errors, display them
mysqli_close($dbc);
?>

<?php include 'includes/header.php'; ?>

<div class="container mt-4">
    <h1>Book Package</h1>
    <div class="alert alert-danger">
        <h4>Error</h4>
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <p><a href="view_packages.php" class="btn btn-outline-primary">Back to Packages</a></p>
</div>

<?php include 'includes/footer.html'; ?>