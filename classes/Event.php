<?php
require_once __DIR__ . '/../includes/connect_db.php';

class Event {
    protected $conn;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function validateLink($token) {
        $token = $this->conn->real_escape_string($token);
        $stmt = $this->conn->prepare("SELECT sl.link_id, sl.event_id, sl.mode, sl.expiry_date, 
                                     e.event_name, e.event_date, e.catering_details, 
                                     e.furniture_requirements, e.av_requirements, e.discount_percentage 
                                     FROM shared_links sl 
                                     JOIN events e ON sl.event_id = e.event_id 
                                     WHERE sl.token = ? AND sl.expiry_date > NOW()");
        if (!$stmt) {
            error_log("Prepare failed in validateLink: " . $this->conn->error);
            return null;
        }
        $stmt->bind_param("s", $token);
        if (!$stmt->execute()) {
            error_log("Execute failed in validateLink: " . $stmt->error);
            $stmt->close();
            return null;
        }
        $result = $stmt->get_result();
        $data = $result->fetch_assoc();
        $stmt->close();
        if (!$data) {
            error_log("No data found for token: $token");
        }
        return $data;
    }

    public function getGuestList($eventId) {
        $stmt = $this->conn->prepare("SELECT guest_id, name, email, rsvp_status FROM guests WHERE event_id = ?");
        $stmt->bind_param("i", $eventId);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function updateGuest($guestId, $rsvpStatus) {
        $guestId = (int)$guestId;
        $rsvpStatus = $this->conn->real_escape_string($rsvpStatus);
        $stmt = $this->conn->prepare("UPDATE guests SET rsvp_status = ? WHERE guest_id = ?");
        $stmt->bind_param("si", $rsvpStatus, $guestId);
        return $stmt->execute();
    }

    public function addGuest($eventId, $name, $email, $rsvpStatus = 'Pending') {
        $eventId = (int)$eventId;
        $name = $this->conn->real_escape_string($name);
        $email = $this->conn->real_escape_string($email);
        $rsvpStatus = $this->conn->real_escape_string($rsvpStatus);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            error_log("Invalid email in addGuest: $email");
            return false;
        }

        $stmt = $this->conn->prepare("INSERT INTO guests (event_id, name, email, rsvp_status) VALUES (?, ?, ?, ?)");
        if (!$stmt) {
            error_log("Prepare failed in addGuest: " . $this->conn->error);
            return false;
        }
        $stmt->bind_param("isss", $eventId, $name, $email, $rsvpStatus);
        if ($stmt->execute()) {
            error_log("Guest added: event_id=$eventId, email=$email");
            return true;
        } else {
            error_log("Insert failed in addGuest: " . $stmt->error);
            return false;
        }
    }

    public function deleteGuest($guestId) {
        $guestId = (int)$guestId;
        $stmt = $this->conn->prepare("DELETE FROM guests WHERE guest_id = ?");
        $stmt->bind_param("i", $guestId);
        return $stmt->execute();
    }
}

class GuestManager {
    protected $conn;
    protected $event;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->event = new Event($conn);
    }

    public function processGuestManagement($eventId, $action, $data) {
        switch ($action) {
            case 'update':
                return $this->event->updateGuest($data['guest_id'], $data['rsvp_status']);
            case 'add':
                return $this->event->addGuest($eventId, $data['name'], $data['email'], $data['rsvp_status']);
            case 'delete':
                return $this->event->deleteGuest($data['guest_id']);
            default:
                return false;
        }
    }
}
?>