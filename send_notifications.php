<?php
require_once __DIR__ . '/includes/connect_db.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set timezone
date_default_timezone_set('UTC');

// Function to send notifications
function sendEventNotifications($dbc) {
    // Trigger 1: Upcoming events (within 7 days)
    $upcomingDate = date('Y-m-d', strtotime('+7 days'));
    $stmt = $dbc->prepare("
        SELECT e.event_id, e.event_name, e.event_date, e.description, u.email, u.first_name
        FROM events e
        JOIN users u ON e.client_id = u.user_id
        WHERE e.event_date <= ? AND e.event_date >= CURDATE()
    ");
    $stmt->bind_param("s", $upcomingDate);
    $stmt->execute();
    $upcomingEvents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($upcomingEvents as $event) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'hemdadral@gmail.com';
            $mail->Password = 'ikzcyypfwtxpgybl';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('hemdadral@gmail.com', 'Xpert Events');
            $mail->addAddress($event['email']);
            $mail->isHTML(false);
            $mail->Subject = "Reminder: Upcoming Event - {$event['event_name']}";
            $mail->Body = "Hi {$event['first_name']},\n\nYour event '{$event['event_name']}' is scheduled for {$event['event_date']}.\n\nDetails:\n- Description: {$event['description']}\n\nBest,\nXpert Events";

            $mail->send();
            error_log("Sent upcoming event notification to {$event['email']} for event {$event['event_id']}");
        } catch (Exception $e) {
            error_log("Failed to send upcoming event notification to {$event['email']} for event {$event['event_id']}: {$mail->ErrorInfo}");
        }
    }

    // Trigger 2: RSVP updates
    $stmt = $dbc->prepare("
        SELECT g.guest_id, g.event_id, g.name, g.email, g.rsvp_status, e.event_name, u.email AS client_email, u.first_name
        FROM guests g
        JOIN events e ON g.event_id = e.event_id
        JOIN users u ON e.client_id = u.user_id
        WHERE g.rsvp_status IN ('Confirmed', 'Declined')
        AND g.rsvp_status_updated_at >= NOW() - INTERVAL 1 DAY
    ");
    $stmt->execute();
    $rsvpUpdates = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($rsvpUpdates as $rsvp) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'hemdadral@gmail.com';
            $mail->Password = 'ikzcyypfwtxpgybl';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('hemdadral@gmail.com', 'Xpert Events');
            $mail->addAddress($rsvp['client_email']);
            $mail->isHTML(false);
            $mail->Subject = "RSVP Update for {$rsvp['event_name']}";
            $mail->Body = "Hi {$rsvp['first_name']},\n\nGuest {$rsvp['name']} has updated their RSVP to '{$rsvp['rsvp_status']}' for your event '{$rsvp['event_name']}'.\n\nBest,\nXpert Events";

            $mail->send();
            error_log("Sent RSVP update notification to {$rsvp['client_email']} for guest {$rsvp['guest_id']}");
        } catch (Exception $e) {
            error_log("Failed to send RSVP update notification to {$rsvp['client_email']} for guest {$rsvp['guest_id']}: {$mail->ErrorInfo}");
        }
    }

    // Trigger 3: New event creation
    $stmt = $dbc->prepare("
        SELECT e.event_id, e.event_name, e.event_date, e.description, u.email, u.first_name
        FROM events e
        JOIN users u ON e.client_id = u.user_id
        WHERE e.created_at >= NOW() - INTERVAL 1 DAY
    ");
    $stmt->execute();
    $newEvents = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($newEvents as $event) {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'hemdadral@gmail.com';
            $mail->Password = 'ikzcyypfwtxpgybl';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            $mail->Port = 465;

            $mail->setFrom('hemdadral@gmail.com', 'Xpert Events');
            $mail->addAddress($event['email']);
            $mail->isHTML(false);
            $mail->Subject = "New Event Created - {$event['event_name']}";
            $mail->Body = "Hi {$event['first_name']},\n\nYour event '{$event['event_name']}' has been created for {$event['event_date']}.\n\nDetails:\n- Description: {$event['description']}\n\nBest,\nXpert Events";

            $mail->send();
            error_log("Sent new event notification to {$event['email']} for event {$event['event_id']}");
        } catch (Exception $e) {
            error_log("Failed to send new event notification to {$event['email']} for event {$event['event_id']}: {$mail->ErrorInfo}");
        }
    }
}

sendEventNotifications($dbc);
?>