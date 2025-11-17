<?php
session_start();

// Redirect if not Sales Staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Sales Staff') {
    require ('login_tools.php');
    load();
}

// Connect to the database
require ('includes/connect_db.php');

// Set page title and include header
$page_title = 'Add Package';
include ('includes/header.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array();
    
    // Validate package name
    if (empty($_POST['package_name'])) {
        $errors[] = 'Enter the package name.';
    } else {
        $pn = trim($_POST['package_name']);
    }
    
    // Validate description
    if (empty($_POST['description'])) {
        $errors[] = 'Enter the package description.';
    } else {
        $desc = trim($_POST['description']);
    }
    
    // Validate price
    if (empty($_POST['price']) || !is_numeric($_POST['price']) || $_POST['price'] <= 0) {
        $errors[] = 'Enter a valid price.';
    } else {
        $price = trim($_POST['price']);
    }
    
    // Insert package if no errors
    if (empty($errors)) {
        $q = "INSERT INTO packages (package_name, description, price, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $dbc->prepare($q);
        if (!$stmt) {
            $errors[] = 'Database prepare failed: ' . $dbc->error;
            error_log("Prepare failed in add_package.php: " . $dbc->error);
        } else {
            $stmt->bind_param("ssd", $pn, $desc, $price);
            if ($stmt->execute()) {
                echo '<h1>Success!</h1>
                      <p>Package added successfully.</p>
                      <p><a href="view_packages.php">View Packages</a></p>';
                $stmt->close();
                include ('includes/footer.html');
                mysqli_close($dbc);
                exit();
            } else {
                $errors[] = 'Failed to add package: ' . $stmt->error;
                error_log("Insert failed in add_package.php: " . $stmt->error);
                $stmt->close();
            }
        }
    }
    
    // Display errors
    if (!empty($errors)) {
        echo '<h1>Error!</h1>
              <p id="err_msg">The following error(s) occurred:<br>';
        foreach ($errors as $msg) {
            echo " - $msg<br>";
        }
        echo 'Please try again.</p>';
    }
}
?>

<!-- Add package form -->
<div class="container">
    <h2 class="text-center mt-4 mb-4">Add a Package</h2>
    <form action="add_package.php" method="post" class="register-form">
        <div class="form-group">
            <label for="Name">Package Name</label>
            <input type="text" name="package_name" value="<?php if (isset($_POST['package_name'])) echo htmlspecialchars($_POST['package_name']); ?>" class="form-input">
        </div>
        <div class="form-group">
            <label for="Description">Description</label>
            <textarea name="description" class="form-input"><?php if (isset($_POST['description'])) echo htmlspecialchars($_POST['description']); ?></textarea>
        </div>
        <div class="form-group">
            <label for="last_name">Price (£)</label>
            <input type="number" name="price" step="0.01" value="<?php if (isset($_POST['price'])) echo htmlspecialchars($_POST['price']); ?>" class="form-input">
        </div>
        <div class="form-group">
            <input type="submit" value="Add Package" class="register-button">
        </div>
    </form>
</div>
<?php
// Close database connection
mysqli_close($dbc);

// Include footer
include ('includes/footer.html');
?>