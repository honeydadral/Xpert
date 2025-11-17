<?php
session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    require ('login_tools.php');
    load();
}

// Include header
include ('includes/header.php');

// Clear session data
$_SESSION = array();
session_destroy();

// Display logout message
echo "<h1>Goodbye!</h1>
      <p>You are now logged out.</p>
      <p>See you soon.</p>";

// Include footer
include ('includes/footer.html');
?>