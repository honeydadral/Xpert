<?php
// Include the header file
include ('includes/header.php');
$page_title = 'Xpert - Login';
// Display errors if any
if (isset($errors) && !empty($errors)) {
    echo '<p id="err_msg">Oops! There was a problem:<br>';
    foreach ($errors as $msg) {
        echo " - $msg<br>";
    }
    echo 'Please try again or <a href="register.php">Register</a></p>';
}
?>

<!-- Login form -->
<div class="container login">
    <form action="login_action.php" method="post" class="login-form">
        <div class="form-group">
            <label for="username">Email</label>
            <input type="text" id="username" name="email" value="<?php if (isset($_POST['email'])) echo $_POST['email']; ?>" class="form-input">
        </div>
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="pass" class="form-input">
        </div>
        <div class="form-group">
            <input type="submit" value="Sign In" class="login-button">
        </div>
        <div class="form-group">
            <a href="register.php" class="forgot-password">Create an account</a>
        </div>
    </form>
</div>

<?php
// Include the footer file
include ('includes/footer.html');
?>