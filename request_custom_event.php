<?php
session_start();

// Redirect if not Client
if (!isset($_SESSION['user_id'])) {
    require ('login_tools.php');
    load();
}

// Connect to the database
require ('includes/connect_db.php');

// Set page title and include header
$page_title = 'Xpert - Request Custom Event';
include ('includes/header.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array();
    
    // Validate event name
    if (empty($_POST['event_name'])) {
        $errors[] = 'Event name is required.';
    } else {
        $event_name = $dbc->real_escape_string(trim($_POST['event_name']));
    }
    
    // Validate event date
    if (empty($_POST['event_date'])) {
        $errors[] = 'Event date is required.';
    } else {
        $event_date = $dbc->real_escape_string(trim($_POST['event_date']));
        $today = date('Y-m-d');
        if ($event_date < $today) {
            $errors[] = 'Event date must be in the future.';
        }
    }
    
    // Validate description
    if (empty($_POST['description'])) {
        $errors[] = 'Event description is required.';
    } else {
        $description = $dbc->real_escape_string(trim($_POST['description']));
    }
    
    // Validate budget estimate (optional)
    $budget = NULL;
    if (!empty($_POST['budget_estimate'])) {
        $budget = $dbc->real_escape_string(trim($_POST['budget_estimate']));
        if (!is_numeric($budget) || $budget < 0) {
            $errors[] = 'Budget cannot be negative.';
        }
    }
    
    // Insert request if no errors
    if (empty($errors)) {
        $client_id = $_SESSION['user_id'];
        $q = "INSERT INTO custom_event_requests (client_id, event_name, event_date, description, budget_estimate, submitted_at) 
              VALUES ('$client_id', '$event_name', '$event_date', '$description', " . ($budget ? "'$budget'" : "NULL") . ", NOW())";
        $r = $dbc->query($q);
        
        if ($r) {
            // Retrieve client email and name
            $q = "SELECT email, first_name FROM users WHERE user_id = '$client_id'";
            $r = $dbc->query($q);
            $client = $r->fetch_array(MYSQLI_ASSOC);
            
            // Send confirmation email using PHPMailer
            require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
            require_once __DIR__ . '/PHPMailer/src/SMTP.php';
            require_once __DIR__ . '/PHPMailer/src/Exception.php';
            
            // Check if PHPMailer files exist
            if (!file_exists(__DIR__ . '/PHPMailer/src/PHPMailer.php')) {
                $errors[] = 'PHPMailer library not found. Please ensure PHPMailer is installed in the correct directory.';
                error_log('PHPMailer library not found at ' . __DIR__ . '/PHPMailer/src/PHPMailer.php', 3, 'logs/email_errors.log');
            } else {
                $mail = new PHPMailer\PHPMailer\PHPMailer(true);
                try {
                    // Server settings from view_events.php
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'hemdadral@gmail.com';
                    $mail->Password = 'ikzcyypfwtxpgybl';
                    $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port = 465;
                    
                    // Recipients
                    $mail->setFrom('hemdadral@gmail.com', 'Xpert Events');
                    $mail->addAddress($client['email'], $client['first_name']);
                    $mail->addReplyTo('support@xpertevents.com', 'Xpert Events Support');
                    
                    // Content
                    $mail->isHTML(false);
                    $mail->Subject = 'Custom Event Request Confirmation - Xpert Events';
                    $mail->Body = "Dear {$client['first_name']},\n\n";
                    $mail->Body .= "Thank you for submitting a custom event request. Here are the details:\n\n";
                    $mail->Body .= "Event Name: $event_name\n";
                    $mail->Body .= "Event Date: $event_date\n";
                    $mail->Body .= "Description: $description\n";
                    $mail->Body .= "Budget Estimate: " . ($budget ? "£" . number_format($budget, 2) : "Not provided") . "\n\n";
                    $mail->Body .= "We will review your request and contact you soon.\n\n";
                    $mail->Body .= "Best regards,\nXpert Events Team\nxpertevents.com";
                    
                    $mail->send();
                    $email_status = "Email sent successfully to {$client['email']}.";
                } catch (PHPMailer\PHPMailer\Exception $e) {
                    $email_status = "Failed to send email to {$client['email']}.";
                    error_log("PHPMailer Error: {$mail->ErrorInfo} at " . date('Y-m-d H:i:s'), 3, 'logs/email_errors.log');
                }
            }
            
            echo '<h1>Success!</h1>
                  <p>Custom event request submitted.</p>
                  <p>' . htmlspecialchars($email_status) . '</p>
                  <p><a href="client_home.php">Back to Dashboard</a></p>';
            mysqli_close($dbc);
            include ('includes/footer.html');
            exit();
        } else {
            $errors[] = 'Failed to submit request.';
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

<!-- Custom event request form -->
<h2 class="text-center mt-4">Request Custom Event</h1>
<div class="container login">
    <form action="request_custom_event.php" method="post" class="login-form">
        <div class="form-group">
            <label for="Event Name">Event Name</label>
            <input type="text" name="event_name" value="<?php if (isset($_POST['event_name'])) echo $_POST['event_name']; ?>" class="form-input">
        </div>
        <div class="form-group">
            <label for="Event Date">Event Date</label>
            <input type="date" name="event_date" value="<?php if (isset($_POST['event_date'])) echo $_POST['event_date']; ?>" class="form-input">
        </div>
        <div class="form-group">
            <label for="Description">Description</label>
            <textarea class="form-input" name="description"><?php if (isset($_POST['description'])) echo $_POST['description']; ?></textarea>
        </div>
        <div class="form-group">
            <label for="Budget">Budget Estimate (£, optional)</label>
            <input type="number" name="budget_estimate" step="0.01" min="0" value="<?php if (isset($_POST['budget_estimate'])) echo $_POST['budget_estimate']; ?>" class="form-input">
        </div>
        <div class="form-group">
            <input type="submit" value="Submit Request" class="login-button">
        </div>
    </form>
</div>
<?php
// Close database connection
mysqli_close($dbc);

// Include footer
include ('includes/footer.html');
?>