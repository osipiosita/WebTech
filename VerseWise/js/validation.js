document.addEventListener("DOMContentLoaded", function() {
    const registerBtn = document.getElementById("register-button");

    function validateForm(event) {
        const firstName = document.getElementById("firstname").value.trim();  // Fixed to match your HTML
        const lastName = document.getElementById("lastname").value.trim();    // Added to match HTML
        const email = document.getElementById("email").value.trim();
        const password = document.getElementById("password").value;
        const confirmPassword = document.getElementById("confirm-password").value;

        // Check if fields are empty
        if (!firstName || !lastName || !email || !password || !confirmPassword) {
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

        // All validations passed, allow form submission
        return true;
    }

    // Add event listener to the form submit button
    const form = document.getElementById("register-form");

    form.addEventListener('submit', function(event) {
        // Call validateForm and prevent default if validation fails
        if (!validateForm(event)) {
            event.preventDefault();  // Prevent form submission if validation fails
            document.getElementById('error-messages').style.display = 'block';

        }
        else{
            document.getElementById('error-messages').style.display = 'none';

            document.getElementById('success').style.display = 'block';
        }
    });
});
