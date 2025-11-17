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
$page_title = 'Xpert - Add Event';
include ('includes/header.php');

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $errors = array();
    
    // Validate client ID
    if (empty($_POST['client_id']) || !is_numeric($_POST['client_id'])) {
        $errors[] = 'Select a valid client.';
    } else {
        $client_id = $dbc->real_escape_string(trim($_POST['client_id']));
        // Verify client exists and is a Client
        $q = "SELECT user_id FROM users WHERE user_id = '$client_id' AND role = 'Client'";
        $r = $dbc->query($q);
        if ($r->num_rows == 0) {
            $errors[] = 'Invalid client selected.';
        }
    }
    
    // Validate event name
    if (empty($_POST['event_name'])) {
        $errors[] = 'Enter the event name.';
    } else {
        $event_name = $dbc->real_escape_string(trim($_POST['event_name']));
    }
    // Validate event date
    if (empty($_POST['event_date'])) {
        $errors[] = 'Select an event date.';
    } else {
        $event_date = $dbc->real_escape_string(trim($_POST['event_date']));
    }


    // Handle optional fields
    $catering = !empty($_POST['catering_details']) ? $dbc->real_escape_string(trim($_POST['catering_details'])) : NULL;
    $furniture = !empty($_POST['furniture_requirements']) ? $dbc->real_escape_string(trim($_POST['furniture_requirements'])) : NULL;
    $av = !empty($_POST['av_requirements']) ? $dbc->real_escape_string(trim($_POST['av_requirements'])) : NULL;
    
    //Validate discount (manager-only)
    $discount = 0.00;

    if ($_SESSION['manager_status'] == 1) {
        if (isset($_POST['discount_percentage']) && $_POST['discount_percentage'] !== '') {
            $discount = $dbc->real_escape_string(trim($_POST['discount_percentage']));
            if (!is_numeric($discount) || $discount < 0 || $discount > 50) {
                $errors[] = 'Discount must be between 0 and 50%.';
            }
        }
    } elseif (isset($_POST['discount_percentage']) && $_POST['discount_percentage'] !== '') {
        $errors[] = 'Only managers can apply discounts.';
    }

    // Insert event if no errors
    if (empty($errors)) {
        $q = "INSERT INTO events (client_id, event_name, event_date, catering_details, furniture_requirements, av_requirements, discount_percentage, created_at) 
              VALUES ('$client_id', '$event_name', '$event_date', " . 
                     ($catering ? "'$catering'" : "NULL") . ", 
                     " . ($furniture ? "'$furniture'" : "NULL") . ", 
                     " . ($av ? "'$av'" : "NULL") . ", 
                     '$discount', NOW())";

        $r = $dbc->query($q);
        
        if ($r) {
            // Send notification to client (simplified)
            $q = "SELECT email FROM users WHERE user_id = '$client_id'";
            $r = $dbc->query($q);
            $client = $r->fetch_array(MYSQLI_ASSOC);
            $to = $client['email'];
            $subject = 'New Event Created';
            $message = "Dear Client,\n\nA new event '$event_name' has been created for you.\n\nThank you,\nXpert Events";
            mail($to, $subject, $message);
            
            echo '<h1>Success!</h1>
                  <p>Event added successfully.</p>
                  <p><a href="view_events.php">View Events</a></p>';
            mysqli_close($dbc);
            include ('includes/footer.html');
            exit();
        } else {
            $errors[] = 'Failed to add event.';
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

// Retrieve clients for dropdown
$q = "SELECT user_id, username, first_name, last_name FROM users WHERE role = 'Client'";
$r = $dbc->query($q);
$clients = [];
while ($row = $r->fetch_array(MYSQLI_ASSOC)) {
    $clients[] = $row;
}
$r->free_result();
?>

<!-- Add event form -->
<div class="container">
    <h2 class="text-center mt-4 mb-4">Add New Event</h2>
    <form action="add_event.php" method="post" class="register-form">
        <div class="form-group">
            <label for="Client">Client</label>
            <select name="client_id">
                <option value="">Select Client</option>
                <?php foreach ($clients as $client): ?>
                    <option value="<?php echo $client['user_id']; ?>" 
                            <?php if (isset($_POST['client_id']) && $_POST['client_id'] == $client['user_id']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($client['first_name'] . ' ' . $client['last_name'] . ' (' . $client['username'] . ')'); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label for="Event Name">Event Name</label>
            <input type="text" name="event_name" value="<?php if (isset($_POST['event_name'])) echo $_POST['event_name']; ?>" class="form-input">
        </div>
        <div class="form-group">
            <label for="Event Date">Event Date</label>
            <input type="date" name="event_date" value="<?php if (isset($_POST['event_date'])) echo $_POST['event_date']; ?>" class="form-input">
        </div>
        <div class="form-group">
            <label for="Catering Details">Catering Details</label>
            <textarea name="catering_details" class="form-input"><?php if (isset($_POST['catering_details'])) echo $_POST['catering_details']; ?></textarea>
        </div>
        <div class="form-group">
            <label for="Furniture Requirements">Furniture Requirements</label>
            <textarea name="furniture_requirements" class="form-input"><?php if (isset($_POST['furniture_requirements'])) echo $_POST['furniture_requirements']; ?></textarea>
        </div>
        <div class="form-group">
            <label for="AV Requirements">AV Requirements</label>
            <textarea name="av_requirements" class="form-input"><?php if (isset($_POST['av_requirements'])) echo $_POST['av_requirements']; ?></textarea>
        </div>
        <div class="form-group">
            <?php if ($_SESSION['manager_status'] == 1): ?>
                <p>Discount Percentage (0-50%): <input type="number" name="discount_percentage" step="0.01" min="0" max="50" value="<?php if (isset($_POST['discount_percentage'])) echo $_POST['discount_percentage']; else echo '0'; ?>"></p>
            <?php endif; ?>
        </div>
        <div class="form-group">
            <input type="submit" value="Add Event" class="register-button">
        </div>
    </form>
</div>
<?php
// Close database connection
mysqli_close($dbc);

// Include footer
include ('includes/footer.html');
?>