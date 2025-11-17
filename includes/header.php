<!-- includes/header.php -->
<?php
// Start output buffering to prevent header issues
ob_start();
// session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) : 'Xpert Events Management System'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <div class="hero-content">
                <a href="index.php"><img src="assets/img/xpert_logo.png" alt="XPERT EVENTS" class="hero-image" style="width: 200px; height: auto; padding: 0px 30px; margin: 4px 20px;"></a>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" style="<?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'text-decoration: underline;' : ''; ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_packages.php' ? 'active' : ''; ?>" style="<?php echo basename($_SERVER['PHP_SELF']) == 'view_packages.php' ? 'text-decoration: underline;' : ''; ?>" href="view_packages.php">Book Event Packages</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle <?php echo in_array(basename($_SERVER['PHP_SELF']), ['request_custom_event.php', 'add_event.php']) ? 'active' : ''; ?>" style="<?php echo in_array(basename($_SERVER['PHP_SELF']), ['request_custom_event.php', 'add_event.php' ,'view_events.php']) ? 'text-decoration: underline;' : ''; ?>" href="#" id="eventsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Events
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="eventsDropdown">
                            <li>
                                <a class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'view_events.php' ? 'active' : ''; ?>" style="<?php echo basename($_SERVER['PHP_SELF']) == 'view_events.php' ? 'text-decoration: underline;' : ''; ?>" href="view_events.php">My Events</a>
                            </li>
                            <li>
                                <a class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'request_custom_event.php' ? 'active' : ''; ?>" style="<?php echo basename($_SERVER['PHP_SELF']) == 'request_custom_event.php' ? 'text-decoration: underline;' : ''; ?>" href="request_custom_event.php">Custom Events</a>
                            </li>
                            <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Sales Staff'): ?>
                                <li>
                                    <a class="dropdown-item <?php echo basename($_SERVER['PHP_SELF']) == 'add_event.php' ? 'active' : ''; ?>" style="<?php echo basename($_SERVER['PHP_SELF']) == 'add_event.php' ? 'text-decoration: underline;' : ''; ?>" href="add_event.php">Add Event</a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Sales Staff'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_package.php' ? 'active' : ''; ?>" style="<?php echo basename($_SERVER['PHP_SELF']) == 'add_package.php' ? 'text-decoration: underline;' : ''; ?>" href="add_package.php">Add Package</a>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>" style="<?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'text-decoration: underline;' : ''; ?>" href="manage_users.php">User Management</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact-us.php' ? 'active' : ''; ?>" style="<?php echo basename($_SERVER['PHP_SELF']) == 'contact-us.php' ? 'text-decoration: underline;' : ''; ?>" href="contact-us.php">Contact Us</a>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" style="<?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'text-decoration: underline;' : ''; ?>" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>" style="<?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'text-decoration: underline;' : ''; ?>" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <div class="hero-content">
                <img src="assets/img/xpert_logo.png" alt="XPERT EVENTS" class="hero-image" style="width: 200px; height: auto; padding: 0px 30px; margin: 4px 20px;">
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'view_packages.php' ? 'active' : ''; ?>" href="view_packages.php">Book Event Packages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'contact-us.php' ? 'active' : ''; ?>" href="contact-us.php">Contact Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'request_custom_event.php' ? 'active' : ''; ?>" href="request_custom_event.php">Custom Events</a>
                    </li>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Sales Staff'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_package.php' ? 'active' : ''; ?>" href="add_package.php">Add Package</a>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Sales Staff'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'add_event.php' ? 'active' : ''; ?>" href="add_event.php">Add Event</a>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : ''; ?>" href="manage_users.php">User Management</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['first_name'] ?? 'User'); ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'login.php' ? 'active' : ''; ?>" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'register.php' ? 'active' : ''; ?>" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav> -->