<?php

require_once('../db/config.php');

session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../html/login.html'); 
    exit();
}



//Delete user if request is made
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM people WHERE id = ?");
    $stmt->bind_param("i", $user_id); // 'i' stands for integer
    if ($stmt->execute()) {
        header("Location: users.php");
        exit;
    } else {
        // Handle the error and log it if necessary
        error_log("Error deleting user: " . $stmt->error);
        echo "Error deleting user: " . $stmt->error;
    }
}


// Update user if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editUserId'])) {
    error_log("Edit request received");
    error_log(print_r($_POST, true));
    $user_id = (int)$_POST['editUserId'];
    $name = htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
    $email = htmlspecialchars($_POST['email'], ENT_QUOTES, 'UTF-8');

    // Prepare and execute the update query
    $stmt = $conn->prepare("UPDATE people SET fname = ?, lname = ?, email = ? WHERE id = ?");
    $nameParts = explode(" ", $name, 2); // Split full name into first and last name
    $fname = $nameParts[0];
    $lname = isset($nameParts[1]) ? $nameParts[1] : "";

    $stmt->bind_param("sssi", $fname, $lname, $email, $user_id);

    if ($stmt->execute()) {
        // Redirect after successful update
        header("Location: users.php");
        exit();
    } else {
        echo "Error updating user: " . $conn->error;
    }
}


// Fetch users from the database
$result = $conn->query("SELECT id, CONCAT(fname, ' ', lname) AS full_name, email, role, created_at FROM people");

$users = [];

while($row=$result->fetch_assoc()){
    $users[] = $row;
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="icon" href="https://img.icons8.com/?size=100&id=37401&format=png&color=000000" type="image/png">

</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="users.php">User Management</a></li>   
                <li><a href="quiz_management.php">Quiz Management</a></li>
                <li><a href="verse_management.php">Verse Management</a></li>
                <li><a href="logout.php">Logout</a></li>

            </ul>
        </nav>
    </header>

    <section id="user-management">
        <h2>All Users</h2>
        <table id="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Registration Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="table-body">
                <?php foreach ($users as $user): ?>
                    <tr id="user-<?php echo $user['id']; ?>">
                        <td><?php echo $user['id']; ?></td>
                        <td><?php echo $user['full_name']; ?></td>
                        <td><?php echo $user['email']; ?></td>
                        <td><?php echo $user['role']; ?></td>
                        <td><?php echo $user['created_at']; ?></td>
                        <td>
                            <button class="view" onclick="openViewModal(<?php echo $user['id']; ?>, '<?php echo $user['full_name']; ?>', '<?php echo $user['email']; ?>')">View</button>
                            <button class="edit" onclick="openEditModal(<?php echo $user['id']; ?>, '<?php echo $user['full_name']; ?>', '<?php echo $user['email']; ?>')">Edit</button>
                            <a href="users.php?delete=<?php echo $user['id']; ?>" onclick="return confirm('Are you sure you want to delete this user?')">
                                <button class="delete">Delete</button>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </section>

    <!-- Modals for View and Edit -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('viewModal')">&times;</span>
            <h2>User Details</h2>
            <p id="userDetails"></p>
        </div>
    </div>

    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2>Edit User</h2>
            <form id="editForm" method="POST" action="users.php">
                <div class="form-group">
                    <label for="editName">Name:</label>
                    <input type="text" id="editName" name="name" required>
                </div>
                <div class="form-group">
                    <label for="editEmail">Email:</label>
                    <input type="email" id="editEmail" name="email" required>
                    <span class="error" id="emailError"></span>
                </div>
                <input type="hidden" id="editUserId" name="editUserId">
                <button type="submit">Save</button>
            </form>
        </div>
    </div>

<script src="../js/users.js"></script>
</body>
</html>