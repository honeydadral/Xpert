<?php
require_once __DIR__ . '/../includes/connect_db.php';

abstract class User {
    protected $conn;
    protected $username;
    protected $email;
    protected $password;
    protected $role;

    public function __construct($conn) {
        $this->conn = $conn;
    }

    public function login($username, $password, $role) {
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE Username = ? AND role = ?");
        $stmt->bind_param("ss", $username, $role);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user && password_verify($password, $user['pass'])) {
            session_start();
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['Username'];
            $_SESSION['role'] = $user['role'];
            if ($user['role'] == 'Sales Staff') {
                $_SESSION['manager_status'] = $user['manager_status'];
            }
            return true;
        }
        return false;
    }
}

class Admin extends User {
    public function addUser($username, $email, $password, $role, $managerStatus, $firstName, $lastName) {
        $username = $this->conn->real_escape_string($username);
        $email = $this->conn->real_escape_string($email);
        $firstName = $this->conn->real_escape_string($firstName);
        $lastName = $this->conn->real_escape_string($lastName);
    
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format.";
        }
    
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_row()[0] > 0) {
            return "Username already taken.";
        }
    
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $regDate = date('Y-m-d');
        $stmt = $this->conn->prepare("INSERT INTO users (first_name, last_name, email, role, manager_status, pass, reg_date, Username) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssisss", $firstName, $lastName, $email, $role, $managerStatus, $hashedPassword, $regDate, $username);
        if (!$stmt->execute()) {
            return "Error adding user: " . $stmt->error;
        }
    
        return "User account created successfully.";
    }

    public function editUser($userId, $username, $email, $password, $role, $managerStatus, $firstName, $lastName) {
        $userId = (int)$userId;
        $username = $this->conn->real_escape_string($username);
        $email = $this->conn->real_escape_string($email);
        $firstName = $this->conn->real_escape_string($firstName);
        $lastName = $this->conn->real_escape_string($lastName);
    
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return "Invalid email format.";
        }
    
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result->fetch_assoc()) {
            return "Invalid user selected.";
        }
    
        $stmt = $this->conn->prepare("SELECT COUNT(*) FROM users WHERE Username = ? AND user_id != ?");
        $stmt->bind_param("si", $username, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->fetch_row()[0] > 0) {
            return "Username already taken.";
        }
    
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, manager_status = ?, pass = ?, Username = ? WHERE user_id = ?");
            $stmt->bind_param("ssssissi", $firstName, $lastName, $email, $role, $managerStatus, $hashedPassword, $username, $userId);
        } else {
            $stmt = $this->conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, role = ?, manager_status = ?, Username = ? WHERE user_id = ?");
            $stmt->bind_param("ssssisi", $firstName, $lastName, $email, $role, $managerStatus, $username, $userId);
        }
    
        if (!$stmt->execute()) {
            return "Error updating user: " . $stmt->error;
        }
    
        return "User account updated successfully.";
    }

    public function deleteUser($userId) {
        $userId = (int)$userId;
        $stmt = $this->conn->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result->fetch_assoc()) {
            return "Invalid user selected.";
        }

        $stmt = $this->conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        return "User account updated successfully.";
    }

    public function getUsers() {
        $result = $this->conn->query("SELECT * FROM users");
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}
?>