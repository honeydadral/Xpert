<?php
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Connect to the database
    require ('includes/connect_db.php');
    
    // Load login tools
    require ('login_tools.php');
    
    // Validate login credentials
    list($check, $data) = validate($dbc, $_POST['email'], $_POST['pass']);
    
    // If login is successful, start session and redirect
    if ($check) {
        session_start();
        $_SESSION['user_id'] = $data['user_id'];
        $_SESSION['first_name'] = $data['first_name'];
        $_SESSION['last_name'] = $data['last_name'];
        $_SESSION['role'] = $data['role'];
        $_SESSION['manager_status'] = $data['manager_status'];
        
        // Redirect based on role
        switch ($data['role']) {
            case 'Client':
                load('index.php');
                break;
            case 'Sales Staff':
                load('index.php');
                break;
            case 'Admin':
                load('index.php');
                break;
            default:
                load('login.php'); // Fallback in case of invalid role
        }
    } else {
        $errors = $data;
    }
    
    // Close database connection
    $dbc->close();
}

// Include the login form
include ('login.php');
?>