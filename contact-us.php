<?php
session_start();

// Connect to the database (for consistency with reference, though not used here)
require ('includes/connect_db.php');
$page_title = 'Xpert - Contact Us';
// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array();
    
    // Validate name
    if (empty($_POST['name'])) {
        $errors[] = 'Full name is required.';
    } else {
        $name = trim($_POST['name']);
    }
    
    // Validate email
    if (empty($_POST['email'])) {
        $errors[] = 'Email address is required.';
    } else {
        $email = trim($_POST['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Invalid email format.';
        }
    }
    
    // Validate comments
    if (empty($_POST['comments'])) {
        $errors[] = 'Event description is required.';
    } else {
        $comments = trim($_POST['comments']);
    }
    
    // Send email if no errors
    if (empty($errors)) {
        // Include PHPMailer
        require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/PHPMailer/src/SMTP.php';
        require_once __DIR__ . '/PHPMailer/src/Exception.php';
        
        // Check if PHPMailer files exist
        if (!file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
            $errors[] = 'PHPMailer library not found. Please contact support.';
            error_log('PHPMailer library not found at ' . __DIR__ . '/PHPMailer/src/PHPMailer.php', 3, 'logs/email_errors.log');
        } else {
            $mail = new PHPMailer\PHPMailer\PHPMailer(true);
            try {
                // Server settings
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = 'hemdadral@gmail.com';
                $mail->Password = 'ikzcyypfwtxpgybl';
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port = 465;
                
                // Recipients
                $mail->setFrom('hemdadral@gmail.com', 'Xpert Events');
                $mail->addAddress('hemdadral@gmail.com', 'Xpert Events Admin');
                $mail->addReplyTo($email, $name);
                
                // Content
                $mail->isHTML(false);
                $mail->Subject = 'New Contact Form Submission - Xpert Events';
                $mail->Body = "New contact form submission received:\n\n";
                $mail->Body .= "Name: $name\n";
                $mail->Body .= "Email: $email\n";
                $mail->Body .= "Event Description: $comments\n\n";
                $mail->Body .= "Submitted at: " . date('Y-m-d H:i:s') . "\n";
                $mail->Body .= "Best regards,\nXpert Events Contact Form";
                
                $mail->send();
                // Redirect to clear form and show success message
                header('Location: contact-us.php?success=1');
                exit();
            } catch (PHPMailer\PHPMailer\Exception $e) {
                $errors[] = 'Failed to send your message. Please try again later.';
                error_log("PHPMailer Error: {$mail->ErrorInfo} at " . date('Y-m-d H:i:s'), 3, 'logs/email_errors.log');
            }
        }
    }
}

// Include header
if (isset($_SESSION['user_id'])) {
    include('includes/header.php');
} else {
    include('includes/header.php');
}
?>

<div class="container">
    <?php
    // Display success message from redirect
    if (isset($_GET['success']) && $_GET['success'] == '1') {
        echo '<div class="alert alert-success">
              <h2>Success!</h2>
              <p>Your message has been sent successfully. We\'ll get back to you soon!</p>
              </div>';
    }
    // Display errors if form was submitted with errors
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($errors)) {
        echo '<div class="alert alert-danger">
              <h2>Error!</h2>
              <p>The following error(s) occurred:<br>';
        foreach ($errors as $msg) {
            echo " - " . htmlspecialchars($msg) . "<br>";
        }
        echo '</p></div>';
    }
    ?>
    <div class="row">
        <div class="col-6">
            <div class="contact-info">
                <h1>Contact Us</h1>
                <p>Let's Connect</p>
                <p>Planning something special? We're here to bring your vision to life. Whether you're looking for a custom event package or need expert help organizing your celebration, our team is ready to assist. Get in touch today — let's make your event unforgettable.</p>
            </div>
        </div>
        <div class="col-6">
            <form action="contact-us.php" method="post" class="contact-form">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" id="name" name="name" value="<?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($errors)) echo htmlspecialchars($_POST['name']); ?>" class="form-input">
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="text" id="email" name="email" value="<?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($errors)) echo htmlspecialchars($_POST['email']); ?>" class="form-input">
                </div>
                <div class="form-group">
                    <label for="comments">Event Description</label>
                    <textarea id="comments" name="comments" rows="5" cols="30" class="form-input"><?php if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($errors)) echo htmlspecialchars($_POST['comments']); ?></textarea>
                </div>
                <div class="form-group">
                    <input type="submit" name="submit" value="Submit" class="submit-button">
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Close database connection
mysqli_close($dbc);

// Include footer
include('includes/footer.html');
?>