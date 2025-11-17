<?php
session_start();
require_once __DIR__ . '/includes/connect_db.php';
require_once __DIR__ . '/classes/Event.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

date_default_timezone_set('UTC');

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "Access denied. Please log in as a Client.";
    header("Location: login.php");
    exit();
}
include ('includes/header.php');
$page_title = 'Xpert - Events';

$event = new Event($dbc);
$message = '';
$error = '';

$clientId = $_SESSION['user_id'];

// Fetch client's events
$stmt = $dbc->prepare("SELECT event_id, event_name, event_date, catering_details, furniture_requirements, av_requirements, discount_percentage 
                      FROM events WHERE client_id = ?");
$stmt->bind_param("i", $clientId);
$stmt->execute();
$events = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("POST data received: " . print_r($_POST, true));

    if (isset($_POST['event_id']) && isset($_POST['mode'])) {
        $eventId = (int)$_POST['event_id'];
        $mode = $dbc->real_escape_string($_POST['mode']);
        $token = bin2hex(random_bytes(16));
        $expiryDate = date('Y-m-d H:i:s', strtotime('+30 days'));

        // Validate and get guest email and name
        if (isset($_POST['email']) && filter_var($_POST['email'], FILTER_VALIDATE_EMAIL) && isset($_POST['name']) && !empty(trim($_POST['name']))) {
            $guestEmail = $dbc->real_escape_string($_POST['email']);
            $guestName = $dbc->real_escape_string(trim($_POST['name']));

            // Add guest to the guests table
            if ($event->addGuest($eventId, $guestName, $guestEmail, 'Pending')) {
                $guestId = $dbc->insert_id;
                error_log("Guest added successfully: guest_id=$guestId, name=$guestName, email=$guestEmail, event_id=$eventId");

                // Insert into shared_links table with guest_id
                $stmt = $dbc->prepare("INSERT INTO shared_links (token, event_id, guest_id, mode, expiry_date) VALUES (?, ?, ?, ?, ?)");
                if (!$stmt) {
                    $error = "Prepare failed for shared_links: " . $dbc->error;
                    error_log("Prepare failed for shared_links: " . $dbc->error);
                } else {
                    $stmt->bind_param("siiss", $token, $eventId, $guestId, $mode, $expiryDate);
                    if ($stmt->execute()) {
                        error_log("Link inserted successfully: token=$token, event_id=$eventId, guest_id=$guestId, mode=$mode, expiry_date=$expiryDate");
                        $message .= "Link generated successfully. ";
                    } else {
                        $error = "Failed to insert link into shared_links: " . $stmt->error;
                        error_log("Insert failed for shared_links: " . $stmt->error);
                    }
                    $stmt->close();
                }

                // Generate the link
                $link = "http://" . $_SERVER['HTTP_HOST'] . "/~c4054234/xpert/view_event_details.php?token=" . urlencode($token);
                error_log("Generated link: $link");

                // Send email with event details
                $eventDetails = null;
                foreach ($events as $evt) {
                    if ($evt['event_id'] == $eventId) {
                        $eventDetails = $evt;
                        break;
                    }
                }

                if ($eventDetails) {
                    $eventName = $eventDetails['event_name'];
                    $eventDate = $eventDetails['event_date'];
                    $cateringDetails = $eventDetails['catering_details'] ?: 'N/A';
                    $furnitureRequirements = $eventDetails['furniture_requirements'] ?: 'N/A';
                    $avRequirements = $eventDetails['av_requirements'] ?: 'N/A';
                    $discountPercentage = $eventDetails['discount_percentage'];

                    $mail = new PHPMailer(true);
                    try {
                        $mail->SMTPDebug = 0;
                        $mail->isSMTP();
                        $mail->Host = 'smtp.gmail.com';
                        $mail->SMTPAuth = true;
                        $mail->Username = 'hemdadral@gmail.com';
                        $mail->Password = 'ikzcyypfwtxpgybl';
                        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                        $mail->Port = 465;

                        $mail->setFrom('hemdadral@gmail.com', 'Xpert Events');
                        $mail->addAddress($guestEmail);

                        $mail->isHTML(false);
                        $mail->Subject = "Event Invitation - $eventName";
                        $mail->Body = "Hi $guestName,\n\nYou are invited to the event: $eventName\n\nEvent Details:\n- Date: $eventDate\n- Catering: $cateringDetails\n- Furniture: $furnitureRequirements\n- AV Requirements: $avRequirements\n- Discount: $discountPercentage%\n\nClick the link to view more details:\n$link\n\nBest,\nXpert Events";

                        $mail->send();
                        $message .= "Event details sent successfully to $guestEmail.";
                        error_log("Email sent successfully to $guestEmail with link: $link");
                    } catch (Exception $e) {
                        $message .= "Failed to send email: {$mail->ErrorInfo}. Use this link: <a href='$link' target='_blank'>$link</a>";
                        error_log("PHPMailer Error: " . $mail->ErrorInfo);
                    }
                } else {
                    $error = "Event not found for event_id: $eventId.";
                    error_log("Event not found for event_id: $eventId");
                }
            } else {
                $error = "Failed to add guest to the database.";
                error_log("Failed to add guest: name=$guestName, email=$guestEmail, event_id=$eventId");
            }
        } else {
            $error = "Invalid or missing guest email or name.";
            error_log("Invalid or missing guest email or name in POST data.");
        }
    } else {
        $error = "Missing event_id or mode in POST data.";
        error_log("Missing event_id or mode in POST data.");
    }
}
?>
<div class="container">
    <h2>My Events</h2>
    <?php if ($error) { ?>
        <p class="message error"><?php echo htmlspecialchars($error); ?></p>
    <?php } ?>
    <?php if ($message) { ?>
        <p class="message success"><?php echo htmlspecialchars($message); ?></p>
    <?php } ?>
    <?php if (!empty($events)) { ?>
        <div class="event-cards">
            <?php foreach ($events as $event) { ?>
                <div class="event-card">
                    <a href="view_event_details.php?event_id=<?php echo $event['event_id']; ?>">
                        <img src="assets/img/dummy-post-square.jpg" alt="Event Image">
                    </a>
                    <h3><a href="view_event_details.php?event_id=<?php echo $event['event_id']; ?>" class="event-name-link"><?php echo htmlspecialchars($event['event_name']); ?></a></h3>
                    <p>Description</p>
                    <button class="btn-custom" onclick="openShareModal(<?php echo $event['event_id']; ?>, '<?php echo $dbc->real_escape_string($event['event_name']); ?>')">Share</button>
                </div>
            <?php } ?>
        </div>
    <?php } else { ?>
        <p>No events found for your account.</p>
    <?php } ?>
</div>

<!-- Bootstrap Modal for Sharing Event -->
<div class="modal fade" id="shareModal" tabindex="-1" aria-labelledby="shareModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shareModalLabel">Share Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailForm" method="post" action="view_events.php">
                    <input type="hidden" name="event_id" id="modalEventId">
                    <input type="hidden" name="mode" value="Read-Only">
                    <div class="mb-3">
                        <label for="name" class="form-label">Enter Guest Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Enter Guest Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" name="email" id="email" required>
                    </div>
                    <button class="btn btn-custom" type="submit">Send Link</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openShareModal(eventId, eventName) {
    document.getElementById('modalEventId').value = eventId;
    const shareModal = new bootstrap.Modal(document.getElementById('shareModal'), {
        backdrop: 'static',
        keyboard: false
    });
    shareModal.show();
}
</script>
<?php include 'includes/footer.html'; ?>