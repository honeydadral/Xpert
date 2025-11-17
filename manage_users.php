<?php
session_start();
require_once __DIR__ . '/includes/connect_db.php';
require_once __DIR__ . '/classes/User.php';

include 'includes/header.php';
$page_title = 'Xpert - Admin Management';
// Check if Admin is logged in
if (!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    $_SESSION['error'] = "Access denied. Please log in as an Admin.";
    header("Location: login.php");
    exit();
}

$admin = new Admin($dbc);
$message = '';
$error = isset($_SESSION['error']) ? $_SESSION['error'] : '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        $userId = isset($_POST['user_id']) ? $_POST['user_id'] : null;
        $username = isset($_POST['username']) ? trim($_POST['username']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        $password = isset($_POST['password']) ? trim($_POST['password']) : '';
        $role = isset($_POST['role']) ? $_POST['role'] : 'Client';
        $managerStatus = isset($_POST['manager_status']) ? 1 : 0;
        $firstName = isset($_POST['first_name']) ? trim($_POST['first_name']) : '';
        $lastName = isset($_POST['last_name']) ? trim($_POST['last_name']) : '';

        switch ($action) {
            case 'add':
                $message = $admin->addUser($username, $email, $password, $role, $managerStatus, $firstName, $lastName);
                break;
            case 'edit':
                $message = $admin->editUser($userId, $username, $email, $password, $role, $managerStatus, $firstName, $lastName);
                break;
            case 'delete':
                $message = $admin->deleteUser($userId);
                break;
            default:
                $message = "Invalid action.";
        }
    }
    unset($_SESSION['error']);
}

$users = $admin->getUsers();
?>

<div class="container">
    <h2>Manage User Accounts</h2>
    <?php if ($error) { ?>
        <p class="message error"><?php echo htmlspecialchars($error); ?></p>
        <?php unset($_SESSION['error']); ?>
    <?php } ?>
    <?php if ($message) { ?>
        <p class="message <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
            <?php echo htmlspecialchars($message); ?>
        </p>
    <?php } ?>

    <!-- Add User Form -->
    <section aria-labelledby="add-user-heading">
        <div class="user-form">
            <h3 id="add-user-heading">Add New User</h3>
            <form action="manage_users.php" method="post">
                <input type="hidden" name="action" value="add">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" required>
                <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required>
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
                <label for="role">Role:</label>
                <select id="role" name="role">
                    <option value="Client">Client</option>
                    <option value="Sales Staff">Sales Staff</option>
                    <option value="Admin">Admin</option>
                </select>
                <label for="manager_status">
                    <input type="checkbox" id="manager_status" name="manager_status">
                    Manager Status (for Sales Staff)
                </label>
                <button class="btn-custom" type="submit">Add User</button>
            </form>
        </div>
    </section>

    <!-- User List -->
    <section aria-labelledby="user-list-heading">
        <div class="user-list mt-5">
            <h3 id="user-list-heading">Current Users</h3>
            <table>
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>First Name</th>
                        <th>Last Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Manager</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['user_id']); ?></td>
                            <td><?php echo htmlspecialchars($user['first_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['last_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['Username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                            <td><?php echo $user['manager_status'] ? 'Yes' : 'No'; ?></td>
                            <td>
                                <!-- Edit Button (Opens Modal) -->
                                <button class="btn-custom" data-bs-toggle="modal" data-bs-target="#editUserModal<?php echo $user['user_id']; ?>" aria-label="Edit user <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>">Edit</button>
                                <!-- Delete Form -->
                                <form action="manage_users.php" method="post" style="display:inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                                    <button class="btn-custom btn-delete" type="submit" onclick="return confirm('Are you sure you want to delete this user?');" aria-label="Delete user <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<!-- Edit User Modals (One per user) -->
<?php foreach ($users as $user) { ?>
<div class="modal fade" id="editUserModal<?php echo $user['user_id']; ?>" tabindex="-1" aria-labelledby="editUserModalLabel<?php echo $user['user_id']; ?>" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editUserModalLabel<?php echo $user['user_id']; ?>">Edit User: <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="manage_users.php" method="post">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($user['user_id']); ?>">
                    <label for="edit_first_name_<?php echo $user['user_id']; ?>">First Name:</label>
                    <input type="text" id="edit_first_name_<?php echo $user['user_id']; ?>" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    <label for="edit_last_name_<?php echo $user['user_id']; ?>">Last Name:</label>
                    <input type="text" id="edit_last_name_<?php echo $user['user_id']; ?>" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    <label for="edit_username_<?php echo $user['user_id']; ?>">Username:</label>
                    <input type="text" id="edit_username_<?php echo $user['user_id']; ?>" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
                    <label for="edit_email_<?php echo $user['user_id']; ?>">Email:</label>
                    <input type="email" id="edit_email_<?php echo $user['user_id']; ?>" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    <label for="edit_password_<?php echo $user['user_id']; ?>">New Password (optional):</label>
                    <input type="password" id="edit_password_<?php echo $user['user_id']; ?>" name="password" placeholder="Enter new password">
                    <label for="edit_role_<?php echo $user['user_id']; ?>">Role:</label>
                    <select id="edit_role_<?php echo $user['user_id']; ?>" name="role">
                        <option value="Client" <?php echo $user['role'] == 'Client' ? 'selected' : ''; ?>>Client</option>
                        <option value="Sales Staff" <?php echo $user['role'] == 'Sales Staff' ? 'selected' : ''; ?>>Sales Staff</option>
                        <option value="Admin" <?php echo $user['role'] == 'Admin' ? 'selected' : ''; ?>>Admin</option>
                    </select>
                    <label for="edit_manager_status_<?php echo $user['user_id']; ?>">
                        <input type="checkbox" id="edit_manager_status_<?php echo $user['user_id']; ?>" name="manager_status" <?php echo $user['manager_status'] ? 'checked' : ''; ?>>
                        Manager Status (for Sales Staff)
                    </label>
                    <button class="btn-custom" type="submit">Update User</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php } ?>

<?php
include 'includes/footer.html';
?>