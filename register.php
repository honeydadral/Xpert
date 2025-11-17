<?php
// Include the header file
include ('includes/header.php');

$page_title = 'Xpert - Register';
// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Connect to the database
    require ('includes/connect_db.php');
    
    // Create an array to store error messages
    $errors = array();
    
    // Validate username
    if (empty($_POST['username'])) {
        $errors[] = 'Enter your username.';
    } else {
        $un = $dbc->real_escape_string(trim($_POST['username']));
    }
    
    // Validate first name
    if (empty($_POST['first_name'])) {
        $errors[] = 'Enter your first name.';
    } else {
        $fn = $dbc->real_escape_string(trim($_POST['first_name']));
    }
    
    // Validate last name
    if (empty($_POST['last_name'])) {
        $errors[] = 'Enter your last name.';
    } else {
        $ln = $dbc->real_escape_string(trim($_POST['last_name']));
    }
    
    // Validate email
    if (empty($_POST['email'])) {
        $errors[] = 'Enter your email address.';
    } else {
        $e = $dbc->real_escape_string(trim($_POST['email']));
    }
    
    // Validate role
    if (empty($_POST['role']) || !in_array($_POST['role'], ['Client', 'Sales Staff'])) {
        $errors[] = 'Select a valid role (Client or Sales Staff).';
    } else {
        $role = $dbc->real_escape_string(trim($_POST['role']));
    }
    
    // Validate passwords
    if (!empty($_POST['pass1'])) {
        if ($_POST['pass1'] != $_POST['pass2']) {
            $errors[] = 'Passwords do not match.';
        } else {
            $p = $dbc->real_escape_string(trim($_POST['pass1']));
        }
    } else {
        $errors[] = 'Enter your password.';
    }
    
    // Check if username or email is already registered
    if (empty($errors)) {
        $q = "SELECT user_id FROM users WHERE email='$e' OR username='$un'";
        $r = $dbc->query($q);
        $rowcount = $r->num_rows;
        if ($rowcount != 0) {
            $errors[] = 'Email address or username already registered. <a href="login.php">Login</a>';
        }
    }
    
    // If no errors, insert the user into the database
    if (empty($errors)) {
        $q = "INSERT INTO users (username, first_name, last_name, email, role, manager_status, pass, reg_date) 
              VALUES ('$un', '$fn', '$ln', '$e', '$role', 0, SHA1('$p'), NOW())";
        $r = $dbc->query($q);
        
        if ($r) {
            echo '<h1>Registered!</h1>
                  <p>You are now registered.</p>
                  <p><a href="login.php">Login</a></p>';
            $dbc->close();
            include ('includes/footer.html');
            exit();
        }
    }
    
    // Display errors if registration fails
    else {
        echo '<h1>Error!</h1>
              <p id="err_msg">The following error(s) occurred:<br>';
        foreach ($errors as $msg) {
            echo " - $msg<br>";
        }
        echo 'Please try again.</p>';
        $dbc->close();
    }
}
?>

<!-- Registration form -->
<div class="container">
    <form action="register.php" method="post" class="register-form">
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" id="username" name="username" value="<?php if (isset($_POST['username'])) echo $_POST['username']; ?>" class="form-input">
        </div>
        <div class="form-group name-row">
            <div class="name-field">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?php if (isset($_POST['first_name'])) echo $_POST['first_name']; ?>" class="form-input">
            </div>
            <div class="name-field">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?php if (isset($_POST['last_name'])) echo $_POST['last_name']; ?>" class="form-input">
            </div>
        </div>
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="text" id="email" name="email" value="<?php if (isset($_POST['email'])) echo $_POST['email']; ?>" class="form-input">
        </div>
        <div class="form-group">
            <label for="role">Role:</label>
            <select id="role" name="role" class="form-input">
                <option value="Client" <?php if (isset($_POST['role']) && $_POST['role'] == 'Client') echo 'selected'; ?>>Client</option>
                <option value="Sales Staff" <?php if (isset($_POST['role']) && $_POST['role'] == 'Sales Staff') echo 'selected'; ?>>Sales Staff</option>
            </select>
        </div>
        <div class="form-group">
            <label for="pass1">Password</label>
            <input type="password" id="pass1" name="pass1" class="form-input">
        </div>
        <div class="form-group">
            <label for="pass2">Confirm Password</label>
            <input type="password" id="pass2" name="pass2" class="form-input">
        </div>
        <div class="form-group">
            <input type="submit" value="Register" class="register-button">
        </div>
    </form>
</div>

<?php
// Include the footer file
include ('includes/footer.html');
?>