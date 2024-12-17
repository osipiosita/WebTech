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
    // Handle quiz addition
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
        // Validate and sanitize inputs
        $question = sanitizeInput($_POST['question']);
        $correct_answer = sanitizeInput($_POST['correct_answer']);
        $reference = sanitizeInput($_POST['reference']);

        // Validate choices
        $choices = isset($_POST['choices']) ? $_POST['choices'] : [];
        $choices = array_map('sanitizeInput', $choices);

        if (empty($question) || empty($correct_answer) || empty($choices)) {
            throw new Exception('All fields are required');
        }

        // Convert choices to JSON
        $choices_json = json_encode($choices);

        // Insert the new quiz using prepared statement
        $query = "INSERT INTO quizzes (question, correct_answer, reference, choices) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssss", $question, $correct_answer, $reference, $choices_json);
        $stmt->execute();
        $stmt->close();

        header("Location: quiz_management.php");
        exit();
    }

    // Handle quiz editing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit' && isset($_POST['quiz_id'])) {
        // Validate and sanitize inputs
        $quiz_id = filter_var($_POST['quiz_id'], FILTER_VALIDATE_INT);
        $question = sanitizeInput($_POST['question']);
        $correct_answer = sanitizeInput($_POST['correct_answer']);
        $reference = sanitizeInput($_POST['reference']);

        // Validate choices
        $choices = isset($_POST['choices']) ? $_POST['choices'] : [];
        $choices = array_map('sanitizeInput', $choices);

        if (!$quiz_id) {
            throw new Exception('Invalid quiz ID');
        }

        // Convert choices to JSON
        $choices_json = json_encode($choices);

        // Update the quiz in the database
        $query = "UPDATE quizzes SET question = ?, correct_answer = ?, reference = ?, choices = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("ssssi", $question, $correct_answer, $reference, $choices_json, $quiz_id);
        $stmt->execute();
        $stmt->close();

        header("Location: quiz_management.php");
        exit();
    }

    // Handle quiz deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete' && isset($_POST['quiz_id'])) {
        $quiz_id = filter_var($_POST['quiz_id'], FILTER_VALIDATE_INT);
        
        if (!$quiz_id) {
            throw new Exception('Invalid quiz ID');
        }

        $query = "DELETE FROM quizzes WHERE id = ?";
        $stmt = $conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $conn->error);
        }
        $stmt->bind_param("i", $quiz_id);
        $stmt->execute();
        $stmt->close();

        header("Location: quiz_management.php");
        exit();
    }

    // Fetch all quizzes from the database
    $stmt = $conn->prepare("SELECT * FROM quizzes");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $quizzes = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

} catch (Exception $e) {
    // Log the error and show user-friendly message
    error_log($e->getMessage());
    $error_message = "An error occurred. Please try again later.";
} finally {
    $conn->close(); // Close the database connection
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Management</title>
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

    <section id="quiz-management">
        <button id="add-quiz" type="button"><i class="fas fa-plus"></i></button>

        <table id="quiz-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Question</th>
                    <th>Correct Answer</th>
                    <th>Reference</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody id="quiz-table-body">
                <?php foreach ($quizzes as $quiz):?>
                    <tr id="Quiz-<?php echo htmlspecialchars($quiz['id']); ?>">
                        <td><?php echo htmlspecialchars($quiz['id']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['question']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['correct_answer']); ?></td>
                        <td><?php echo htmlspecialchars($quiz['reference']); ?></td>
                        <td>
                            <button type="button" onclick="viewQuiz(<?php echo htmlspecialchars(json_encode($quiz), ENT_QUOTES, 'UTF-8'); ?>)" class="view">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button type="button" onclick="openForm(<?php echo htmlspecialchars($quiz['id']); ?>)" class="edit">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button type="button" onclick="deleteQuiz(<?php echo htmlspecialchars($quiz['id']); ?>)" class="delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div id="quiz-form-container" style="display:none;">
            <h3 id="form-title">Add New Quiz</h3>
            <form id="quiz-form" method="POST" action="quiz_management.php">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <input type="hidden" name="action" value="add">
                <input type="hidden" id="quiz-id" name="quiz_id">

                <div class="form-group">
                    <label for="question">Question:</label>
                    <input type="text" id="question" name="question" required>
                </div>
                
                <div class="form-group">
                    <label for="correct_answer">Correct Answer:</label>
                    <input type="text" id="correct_answer" name="correct_answer" required>
                </div>

                <div class="form-group">
                    <label for="reference">Reference:</label>
                    <input type="text" id="reference" name="reference">
                </div>

                <div id="choices-container">
                    <label>Choices:</label>
                    <div class="choice-group">
                        <input type="text" name="choices[]" required>
                        <input type="text" name="choices[]" required>
                        <input type="text" name="choices[]" required>
                        <input type="text" name="choices[]" required>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="submit-btn"><i class="fas fa-plus"></i></button>
                    <button type="button" onclick="closeQuiz()" class="cancel-btn"><i class="fas fa-times"></i></button>
                </div>
            </form>
        </div>

        <div id="view-quiz-modal" class="modal" style="display:none;">
            <div class="modal-content">
                <h3>Quiz Details</h3>
                <div id="quiz-details"></div>
                <button type="button" onclick="closeViewModal()" class="close-btn">Close</button>
            </div>
        </div>
    </section>

    <script>
        // View Quiz
        function viewQuiz(quiz) {
            const choices = JSON.parse(quiz.choices);
            const details = `
                <p><strong>ID:</strong> ${escapeHtml(quiz.id)}</p>
                <p><strong>Question:</strong> ${escapeHtml(quiz.question)}</p>
                <p><strong>Correct Answer:</strong> ${escapeHtml(quiz.correct_answer)}</p>
                <p><strong>Reference:</strong> ${escapeHtml(quiz.reference)}</p>
                <p><strong>Choices:</strong></p>
                <ul>
                    ${choices.map(choice => `<li>${escapeHtml(choice)}</li>`).join('')}
                </ul>
            `;
            document.getElementById('quiz-details').innerHTML = details;
            document.getElementById('view-quiz-modal').style.display = 'block';
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

        // Toggle add/edit quiz form
        document.getElementById('add-quiz').onclick = function() {
            openForm();
        };

        // Open the form for adding/editing a quiz
        function openForm(id = null) {
            const form = document.getElementById('quiz-form-container');
            const formTitle = document.getElementById('form-title');
            const quizForm = document.getElementById('quiz-form');
            
            form.style.display = 'block';
            formTitle.textContent = id ? 'Edit Quiz' : 'Add New Quiz';

            if (id) {
                const quiz = <?php echo json_encode($quizzes); ?>.find(q => q.id === id);
                if (quiz) {
                    document.getElementById('quiz-id').value = quiz.id;
                    document.getElementById('question').value = quiz.question;
                    document.getElementById('correct_answer').value = quiz.correct_answer;
                    document.getElementById('reference').value = quiz.reference;

                    // Populate choices
                    const choices = JSON.parse(quiz.choices);
                    const choiceInputs = document.querySelectorAll('[name="choices[]"]');
                    choices.forEach((choice, index) => {
                        if (choiceInputs[index]) {
                            choiceInputs[index].value = choice;
                        }
                    });

                    document.querySelector('[name="action"]').value = 'edit';
                }
            } else {
                quizForm.reset();
                document.querySelector('[name="action"]').value = 'add';
                document.getElementById('quiz-id').value = '';
            }
        }

        // Close View Modal
        function closeViewModal() {
            document.getElementById('view-quiz-modal').style.display = 'none';
        }

        // Close Quiz Form
        function closeQuiz() {
            document.getElementById('quiz-form-container').style.display = 'none';
        }

        // Delete Quiz function
        function deleteQuiz(quizId) {
            if (confirm("Are you sure you want to delete this quiz?")) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('quiz_id', quizId);
                formData.append('csrf_token', '<?php echo $_SESSION['csrf_token']; ?>');

                // Show loading state
                const deleteButton = document.querySelector(`#Quiz-${quizId} .delete`);
                deleteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                deleteButton.disabled = true;

                // Send the delete request to the server
                fetch('quiz_management.php', {
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
                    // On success, remove the quiz from the table
                    document.getElementById('Quiz-' + quizId).remove();
                })
                .catch(error => {
                    console.error("Error deleting quiz:", error);
                    alert("Error deleting quiz. Please try again.");
                    // Reset the delete button
                    deleteButton.innerHTML = '<i class="fas fa-trash"></i>';
                    deleteButton.disabled = false;
                });
            }
        }
    </script>
</body>
</html>