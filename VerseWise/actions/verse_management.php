<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// Generate CSRF token if it doesn't exist
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once ('../db/config.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../html/login.html');
    exit();
}

// Function to sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

// Validate CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('CSRF token validation failed');
    }
}

try {
    // Handle verse addition
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        // Validate and sanitize inputs
        $verse_text = sanitizeInput($_POST['verse_text']);
        $reference = sanitizeInput($_POST['reference']);

        if (empty($verse_text) || empty($reference)) {
            throw new Exception('All fields are required');
        }


        // Insert the new verse using prepared statement
        $query = "INSERT INTO verses (verse_text, reference) VALUES (?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ss", $verse_text, $reference);
        $stmt->execute();
        $stmt->close();

        header("Location: verse_management.php");
        exit();
    }

    // Handle verse editing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['verse_id'])) {
        // Validate and sanitize inputs
        $verse_id = filter_var($_POST['verse_id'], FILTER_VALIDATE_INT);
        $verse_text = sanitizeInput($_POST['verse_text']);
        $reference = sanitizeInput($_POST['reference']);

        if (!$verse_id) {
            throw new Exception('Invalid verse ID');
        }

        // Update the verse in the database
        $query = "UPDATE verses SET verse_text = ?, reference = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssi", $verse_text, $reference, $verse_id);
        $stmt->execute();
        $stmt->close();

        header("Location: verse_management.php");
        exit();
    }

    // Handle verse deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['verse_id'])) {
        $verse_id = filter_var($_POST['verse_id'], FILTER_VALIDATE_INT);
        
        if (!$verse_id) {
            throw new Exception('Invalid verse ID');
        }

        $query = "DELETE FROM verses WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $verse_id);
        $stmt->execute();
        $stmt->close();

        header("Location: verse_management.php");
        exit();
    }

    // Fetch all verses from the database
    $stmt = $conn->prepare("SELECT * FROM verses");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $verses = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    // Log the error and show user-friendly message
    error_log($e->getMessage());
    $error_message = "An error occurred. Please try again later.";
} finally {
    $conn->close(); 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verse Management</title>
    <link rel="stylesheet" href="../css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="icon" href="https://img.icons8.com/?size=100&id=37401&format=png&color=000000" type="image/png">

</head>
<body>
    <header>
        <nav>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="users.php">User Management</a></li>
                <li><a href="verse_management.php">Verse Management</a></li>
                <li><a href="quiz_management.php">Quiz Management</a></li>                
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </nav>
    </header>

    <?php if (isset($error_message)): ?>
        <div class="error-message">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <section id="verse-management">
        <button id="add-verse" type="button"><i class="fas fa-plus"></i></button>

        <table id="verse-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Verse Text</th>
                    <th>Reference</th>
                    <th>Date Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="verse-table-body">
                <?php foreach ($verses as $verse):?>
                    <tr id="Verse-<?php echo htmlspecialchars($verse['id']); ?>">
                        <td><?php echo htmlspecialchars($verse['id']); ?></td>
                        <td><?php echo htmlspecialchars($verse['verse_text']); ?></td>
                        <td><?php echo htmlspecialchars($verse['reference']); ?></td>

                        <td><?php echo htmlspecialchars($verse['date']); ?></td>
                        <td>
                            <button type="button" onclick="viewVerse(<?php echo htmlspecialchars(json_encode($verse), ENT_QUOTES, 'UTF-8'); ?>)" class="view">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" onclick="openForm(<?php echo htmlspecialchars($verse['id']); ?>)" class="edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" onclick="deleteVerse(<?php echo htmlspecialchars($verse['id']); ?>)" class="delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div id="verse-form-container" style="display:none;">
            <h3 id="form-title">Add New Verse</h3>
            <form id="verse-form" method="POST" action="verse_management.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="add">
                <input type="hidden" id="verse-id" name="verse_id">

                <div class="form-group">
                    <label for="verse_text">Verse Text:</label>
                    <input type="text" id="verse_text" name="verse_text">
                </div>
                
                <div class="form-group">
                    <label for="type">Reference:</label>
                    <input type="text" id="reference" name="reference">
                </div>

                

                <div class="form-actions">
                    <button type="submit" class="submit-btn"><i class="fas fa-plus"></i></button>
                    <button type="button" onclick="closeRecipe()" class="cancel-btn"><i class="fas fa-times"></i></button>
                </div>
            </form>
        </div>

        <div id="view-verse-modal" class="modal" style="display:none;">
            <div class="modal-content">
                <h3>Recipe Details</h3>
                <div id="verse-details"></div>
                <button type="button" onclick="closeViewModal()" class="close-btn">Close</button>
            </div>
        </div>
    </section>

    <script>
        // View Recipe
        function viewVerse(verse) {
            const details = `
                <p><strong>ID:</strong> ${escapeHtml(verse.id)}</p>
                <p><strong>Verse Text:</strong> ${escapeHtml(verse.verse_text)}</p>
                <p><strong>Reference:</strong> ${escapeHtml(verse.reference)}</p>
                <p><strong>Created At:</strong> ${escapeHtml(verse.date)}</p>
            `;
            document.getElementById('verse-details').innerHTML = details;
            document.getElementById('view-verse-modal').style.display = 'block';
        }


        // Escape HTML to prevent XSS in JavaScript
        function escapeHtml(unsafe) {
            if (typeof unsafe !== 'string') {
                return unsafe; // If not a string, return as is
            }
            return unsafe
                .replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
        }


        // Toggle add/edit recipe form
        document.getElementById('add-verse').onclick = function() {
            openForm();
        };

        // Open the form for adding/editing a recipe
        function openForm(id = null) {
            const form = document.getElementById('verse-form-container');
            const formTitle = document.getElementById('form-title');
            const verseForm = document.getElementById('verse-form');
            
            form.style.display = 'block';
            formTitle.textContent = id ? 'Edit Verse' : 'Add New Verse';

            if (id) {
                const verse = <?php echo json_encode($verses); ?>.find(r => r.id === id);
                if (verse) {
                    document.getElementById('verse-id').value = verse.id;
                    document.getElementById('verse_text').value = verse.verse_text;
                    document.getElementById('reference').value = verse.reference;
                    document.querySelector('[name="action"]').value = 'edit';
                }
            } else {
                verseForm.reset();
                document.querySelector('[name="action"]').value = 'add';
                document.getElementById('verse-id').value = '';
            }
        }

        // Close View Modal
        function closeViewModal() {
            document.getElementById('view-verse-modal').style.display = 'none';
        }

        // Close Recipe Form
        function closeRecipe() {
            document.getElementById('verse-form-container').style.display = 'none';
        }

        // Delete Recipe function
        function deleteVerse(verseId) {
            if (confirm("Are you sure you want to delete this ?")) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('verse_id', verseId);
                formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

                // Show loading state
                const deleteButton = document.querySelector(`#Verse-${verseId} .delete`);
                deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                deleteButton.disabled = true;

                // Send the delete request to the server
                fetch('verse_management.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.text();
                })
                .then(() => {
                    // On success, remove the recipe from the table
                    document.getElementById('Verse-' + verseId).remove();
                })
                .catch(error => {
                    console.error("Error deleting recipe:", error);
                    alert("Error deleting recipe. Please try again.");
                    // Reset the delete button
                    deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteButton.disabled = false;
                });
            }
        }
    </script>
</body>
</html>