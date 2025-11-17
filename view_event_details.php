<?php
session_start();
require_once __DIR__ . '/includes/connect_db.php';
require_once __DIR__ . '/classes/Event.php';

$eventObj = new Event($dbc);
$message = '';
$error = '';
$eventDetails = null;
$isGuest = false;
$guestId = null;

include ('includes/header.php');

$page_title = 'Xpert - Event Details';

// Handle RSVP submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rsvp_action']) && isset($_GET['token'])) {
    $token = $dbc->real_escape_string($_GET['token']);
    $rsvpAction = $_POST['rsvp_action'];
    
    // Get guest_id and event_id from shared_links
    $stmt = $dbc->prepare("SELECT guest_id, event_id FROM shared_links WHERE token = ? AND expiry_date > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $linkData = $result->fetch_assoc();
    $stmt->close();
    
    if ($linkData) {
        $guestId = $linkData['guest_id'];
        $eventId = $linkData['event_id'];
        $status = ($rsvpAction === 'confirm') ? 'Confirmed' : 'Declined';
        
        // Update RSVP status
        $stmt = $dbc->prepare("UPDATE guests SET rsvp_status = ? WHERE guest_id = ? AND event_id = ?");
        $stmt->bind_param("sii", $status, $guestId, $eventId);
        if ($stmt->execute()) {
            $message = "RSVP status updated successfully.";
        } else {
            $error = "Failed to update RSVP status.";
        }
        $stmt->close();
    } else {
        $error = "Invalid or expired link.";
    }
}

// Handle Edit Guest submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_guest'])) {
    $guestId = (int)$_POST['guest_id'];
    $eventId = (int)$_POST['event_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $rsvpStatus = $_POST['rsvp_status'];
    
    // Validate inputs
    if (empty($name) || empty($email) || !in_array($rsvpStatus, ['Pending', 'Confirmed', 'Declined'])) {
        $error = "Invalid input data.";
    } else {
        $stmt = $dbc->prepare("UPDATE guests SET name = ?, email = ?, rsvp_status = ? WHERE guest_id = ? AND event_id = ?");
        $stmt->bind_param("sssii", $name, $email, $rsvpStatus, $guestId, $eventId);
        if ($stmt->execute()) {
            $message = "Guest updated successfully.";
        } else {
            $error = "Failed to update guest.";
        }
        $stmt->close();
    }
}

// Handle Delete Guest
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_guest'])) {
    $guestId = (int)$_POST['guest_id'];
    $eventId = (int)$_POST['event_id'];
    
    // Delete from shared_links first
    $stmt = $dbc->prepare("DELETE FROM shared_links WHERE guest_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $guestId, $eventId);
    $stmt->execute();
    $stmt->close();
    
    // Delete from guests
    $stmt = $dbc->prepare("DELETE FROM guests WHERE guest_id = ? AND event_id = ?");
    $stmt->bind_param("ii", $guestId, $eventId);
    if ($stmt->execute()) {
        $message = "Guest deleted successfully.";
    } else {
        $error = "Failed to delete guest.";
    }
    $stmt->close();
}

// Check if accessing via event_id (client) or token (guest)
if (isset($_GET['event_id']) && is_numeric($_GET['event_id'])) {
    // Client access
    if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'Client') {
        $_SESSION['error'] = "Access denied. Please log in as a Client.";
        header("Location: login.php");
        exit();
    }

    $eventId = (int)$_GET['event_id'];
    $clientId = $_SESSION['user_id'];

    // Fetch event details and verify client ownership
    $stmt = $dbc->prepare("SELECT event_name, event_date, catering_details, 
                          furniture_requirements, av_requirements, discount_percentage 
                          FROM events WHERE event_id = ? AND client_id = ?");
    if (!$stmt) {
        $error = "Database error: " . $dbc->error;
        error_log("Prepare failed for event query: " . $dbc->error);
    } else {
        $stmt->bind_param("ii", $eventId, $clientId);
        $stmt->execute();
        $result = $stmt->get_result();
        $eventDetails = $result->fetch_assoc();
        $stmt->close();
    }

    if (!$eventDetails) {
        $error = "Event not found or you do not have access to this event.";
        error_log("Event not found or access denied: event_id=$eventId, client_id=$clientId");
    }
} elseif (isset($_GET['token'])) {
    // Guest access via token
    $token = $dbc->real_escape_string($_GET['token']);
    error_log("Attempting to validate token: $token");
    $eventDetails = $eventObj->validateLink($token);

    if (!$eventDetails) {
        $error = "Invalid or expired link. Please contact the event organizer.";
        error_log("Token validation failed: token=$token, no event details returned");
    } else {
        $isGuest = true;
        // Get guest_id for RSVP
        $stmt = $dbc->prepare("SELECT guest_id FROM shared_links WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $guestData = $result->fetch_assoc();
        $guestId = $guestData['guest_id'];
        $stmt->close();
        error_log("Token validated successfully: token=$token, event_id={$eventDetails['event_id']}");
    }
} else {
    $error = "No event ID or token provided.";
    error_log("No event_id or token in request: " . print_r($_GET, true));
}
?>
<style>
    .modal {
        z-index: 1055;
    }
    .modal-backdrop {
        z-index: 1050;
    }

    .custom-overlay, .navbar, .header {
        z-index: 1040 !important;
    }
</style>

<div class="container">
    <div class="row">
        <div class="col">
            <h2>Event Details</h2>
            <?php if ($error) { ?>
                <p class="message error"><?php echo htmlspecialchars($error); ?></p>
            <?php } elseif ($message) { ?>
                <p class="message success"><?php echo htmlspecialchars($message); ?></p>
            <?php } ?>
            
            <?php if ($eventDetails) { ?>
                <div class="event-details">
                    <h3><?php echo htmlspecialchars($eventDetails['event_name']); ?></h3>
                    <p><strong>Date:</strong> <?php echo htmlspecialchars($eventDetails['event_date']); ?></p>
                    <p><strong>Catering Details:</strong> <?php echo $eventDetails['catering_details'] ? htmlspecialchars($eventDetails['catering_details']) : 'N/A'; ?></p>
                    <p><strong>Furniture Requirements:</strong> <?php echo $eventDetails['furniture_requirements'] ? htmlspecialchars($eventDetails['furniture_requirements']) : 'N/A'; ?></p>
                    <p><strong>AV Requirements:</strong> <?php echo $eventDetails['av_requirements'] ? htmlspecialchars($eventDetails['av_requirements']) : 'N/A'; ?></p>
                    <p><strong>Discount:</strong> <?php echo htmlspecialchars($eventDetails['discount_percentage']); ?>%</p>
                    
                    <?php if ($isGuest) { ?>
                        <!-- RSVP Buttons for Guests -->
                        <form method="POST" action="">
                            <button type="submit" name="rsvp_action" value="confirm" class="btn btn-custom">Confirm</button>
                            <button type="submit" name="rsvp_action" value="reject" class="btn btn-custom">Reject</button>
                        </form>
                    <?php } ?>
                </div>

                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'Client') { ?>
                    <!-- Guest List Management for Clients -->
                    <h3 class="mt-4">Guest List</h3>
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>RSVP Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $dbc->prepare("SELECT guest_id, name, email, rsvp_status FROM guests WHERE event_id = ?");
                            $stmt->bind_param("i", $eventId);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            
                            while ($guest = $result->fetch_assoc()) {
                                ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($guest['name']); ?></td>
                                    <td><?php echo htmlspecialchars($guest['email']); ?></td>
                                    <td><?php echo htmlspecialchars($guest['rsvp_status']); ?></td>
                                    <td>
                                        <!-- Edit Button triggers modal -->
                                        <button type="button" class="btn btn-sm btn-custom" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#editGuestModal"
                                                data-guest-id="<?php echo $guest['guest_id']; ?>"
                                                data-name="<?php echo htmlspecialchars($guest['name']); ?>"
                                                data-email="<?php echo htmlspecialchars($guest['email']); ?>"
                                                data-rsvp-status="<?php echo $guest['rsvp_status']; ?>">
                                            Edit
                                        </button>
                                        <!-- Delete Form -->
                                        <form method="POST" action="" style="display: inline;">
                                            <input type="hidden" name="guest_id" value="<?php echo $guest['guest_id']; ?>">
                                            <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                                            <button type="submit" name="delete_guest" class="btn btn-custom btn-danger" 
                                                    onclick="return confirm('Are you sure you want to delete this guest?');">
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php
                            }
                            $stmt->close();
                            ?>
                        </tbody>
                    </table>
                    <a href="view_events.php" class="back-link">Back to My Events</a>
                <?php } ?>
            <?php } ?>  
        </div>
    </div>
</div>

<!-- Edit Guest Modal -->
<div class="modal fade" id="editGuestModal" tabindex="-1" aria-labelledby="editGuestModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editGuestModalLabel">Edit Guest</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="guest_id" id="edit_guest_id">
                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                    <div class="mb-3">
                        <label for="edit_name" class="form-label">Name</label>
                        <input type="text" class="form-control" id="edit_name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="edit_email" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_rsvp_status" class="form-label">RSVP Status</label>
                        <select class="form-select" id="edit_rsvp_status" name="rsvp_status" required>
                            <option value="Pending">Pending</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Declined">Declined</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-custom" data-bs-dismiss="modal">Close</button>
                    <button type="submit" name="edit_guest" class="btn btn-custom">Save changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Populate modal fields when Edit button is clicked
document.addEventListener('DOMContentLoaded', function () {
    const editModal = document.getElementById('editGuestModal');
    editModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const guestId = button.getAttribute('data-guest-id');
        const name = button.getAttribute('data-name');
        const email = button.getAttribute('data-email');
        const rsvpStatus = button.getAttribute('data-rsvp-status');

        const modal = editModal;
        modal.querySelector('#edit_guest_id').value = guestId;
        modal.querySelector('#edit_name').value = name;
        modal.querySelector('#edit_email').value = email;
        modal.querySelector('#edit_rsvp_status').value = rsvpStatus;
    });
});
</script>

<?php include ('includes/footer.html'); ?>