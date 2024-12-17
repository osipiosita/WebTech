
const registerBtn = document.getElementById("register-button");

function validateForm(event) {
    const fname = document.getElementById("firstname").value.trim();
    const lname = document.getElementById("lastname").value.trim();
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;
    const confirmPassword = document.getElementById("confirm-password").value;

    // Check if fields are empty
    if (!fname || !lname || !email || !password || !confirmPassword) {
        alert("All fields must be filled out.");
        event.preventDefault();  // Prevent form submission
        return false;
    }

    // Validate email structure
    const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    if (!emailPattern.test(email)) {
        alert("Please enter a valid email address.");
        event.preventDefault();  // Prevent form submission
        return false;
    }

    // Validate password length
    if (password.length < 8) {
        alert("Password must be at least 8 characters long.");
        event.preventDefault();  // Prevent form submission
        return false;
    }

    // Validate if passwords are the same
    if (password !== confirmPassword) {
        alert("Passwords must match.");
        event.preventDefault();  // Prevent form submission
        return false;
    }

    // All validations passed
    return true;
}

// Add event listener to the button (pass function reference without parentheses)
registerBtn.addEventListener('click', validateForm);
document.getElementById('registration-form').addEventListener('submit', async (e) => {
    e.preventDefault(); // Prevent default form submission

    const form = e.target;
    const successMessage = document.getElementById('success');
    const errorMessage = document.getElementById('error-messages');
    const formData = new FormData(form);

    try {
        const response = await fetch('../actions/register.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Show success message
            successMessage.textContent = data.message;
            successMessage.style.display = 'block';
            errorMessage.style.display = 'none';
            form.reset();
            location.reload();
        } else {
            // Show error messages
            successMessage.style.display = 'none';
            if (data.errors) {
                alert(data.errors.join('\n')); // Show alert for errors
            } else {
                alert('Registration failed. Please try again.');
            }
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An unexpected error occurred. Please try again later.');
    }
});
