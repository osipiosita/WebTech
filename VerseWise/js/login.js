document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();

    // Disable the submit button to prevent double submission
    const submitButton = document.querySelector('button[type="submit"]');
    submitButton.disabled = true;

    // Create FormData object from the form
    const formData = new FormData(e.target);

    // Reset error messages
    const errorDiv = document.getElementById('error-messages');
    const successDiv = document.getElementById('success');
    errorDiv.textContent = '';
    errorDiv.style.display = 'none';
    successDiv.style.display = 'none';

    try {
        // Send login request
        const response = await fetch('../actions/login.php', {
            method: 'POST',
            body: formData
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const result = await response.json();

        if (result.success) {
            // Show success message
            successDiv.style.display = 'block';
            successDiv.textContent = result.message;
            
            // Store the redirect URL
            const redirectUrl = result.redirect;
            
            // Perform redirect immediately without setTimeout
            window.location.replace(redirectUrl);
            
            // If for some reason the redirect doesn't happen immediately, 
            // fall back to location.href after a short delay
            setTimeout(() => {
                if (window.location.href !== redirectUrl) {
                    window.location.href = redirectUrl;
                }
            }, 100);
        } else {
            // Show error message
            errorDiv.style.display = 'block';
            errorDiv.textContent = result.message;
            // Re-enable the submit button on error
            submitButton.disabled = false;
        }
    } catch (error) {
        console.error('Error:', error);
        errorDiv.style.display = 'block';
        errorDiv.textContent = 'An error occurred. Please try again.';
        // Re-enable the submit button on error
        submitButton.disabled = false;
    }
});

function validateForm(event) {
    const email = document.getElementById("email").value.trim();
    const password = document.getElementById("password").value;

    // Check if fields are empty
    if (!email || !password) {
        alert("All fields must be filled out.");
        event.preventDefault();
        return false;
    }

    // Validate email structure
    const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    if (!emailPattern.test(email)) {
        alert("Please enter a valid email address.");
        event.preventDefault();
        return false;
    }

    // Validate password length
    if (password.length < 8) {
        alert("Password must be at least 8 characters long.");
        event.preventDefault();
        return false;
    }

    return true;
}