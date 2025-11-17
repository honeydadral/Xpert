<?php
// Redirect to a specified page
function load($page = 'login.php') {
    $url = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']);
    $url = rtrim($url, '/\\');
    $url .= '/' . $page;
    header("Location: $url");
    exit();
}

// Validate login credentials
function validate($dbc, $email = '', $pwd = '') {
    $errors = array();
    
    // Validate email
    if (empty($email)) {
        $errors[] = 'Enter your email address.';
    } else {
        $e = $dbc->real_escape_string(trim($email));
    }
    
    // Validate password
    if (empty($pwd)) {
        $errors[] = 'Enter your password.';
    } else {
        $p = $dbc->real_escape_string(trim($pwd));
    }

    // Check credentials against database
    if (empty($errors)) {
        $q = "SELECT user_id, first_name, last_name, role, manager_status 
              FROM users 
              WHERE email='$e' AND pass=SHA1('$p')";
        $r = $dbc->query($q);
        
        if ($r->num_rows == 1) {
            $row = $r->fetch_array(MYSQLI_ASSOC);
            // Verify role is valid
            if (in_array($row['role'], ['Client', 'Sales Staff', 'Admin'])) {
                return array(true, $row);
            } else {
                $errors[] = 'Invalid role assigned to account.';
            }
        } else {
            $errors[] = 'Email address and password not found.';
        }
    }
    
    return array(false, $errors);
}
?>