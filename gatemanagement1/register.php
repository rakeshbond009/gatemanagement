<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Gate Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>Register Account</h1>
            <?php
            if (isset($_GET['error'])) {
                $error = $_GET['error'];
                echo '<div class="alert error">';
                switch ($error) {
                    case 'username_exists':
                        echo 'Username already exists!';
                        break;
                    case 'email_exists':
                        echo 'Email already exists!';
                        break;
                    case 'password_mismatch':
                        echo 'Passwords do not match!';
                        break;
                    case 'invalid_input':
                        echo 'Please fill all required fields!';
                        break;
                    default:
                        echo 'An error occurred during registration!';
                }
                echo '</div>';
            }
            if (isset($_GET['success'])) {
                echo '<div class="alert success">Registration successful! You can now login.</div>';
            }
            ?>
            <form id="registerForm" action="api/register.php" method="POST" onsubmit="return validateForm()">
                <div class="form-group">
                    <input type="text" name="full_name" placeholder="Full Name" required>
                </div>
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="email" name="email" placeholder="Email" required>
                </div>
                <div class="form-group">
                    <input type="tel" name="phone" placeholder="Phone Number" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <div class="form-group">
                    <select name="role" required>
                        <option value="resident">Resident</option>
                        <option value="security">Security Staff</option>
                    </select>
                </div>
                <button type="submit">Register</button>
                <p class="text-center">
                    Already have an account? <a href="index.php">Login here</a>
                </p>
            </form>
        </div>
    </div>
    <script>
    function validateForm() {
        const form = document.getElementById('registerForm');
        const password = form.querySelector('input[name="password"]').value;
        const confirmPassword = form.querySelector('input[name="confirm_password"]').value;
        const phone = form.querySelector('input[name="phone"]').value;
        
        // Password validation
        if (password !== confirmPassword) {
            showNotification('Passwords do not match!', 'error');
            return false;
        }
        
        if (password.length < 6) {
            showNotification('Password must be at least 6 characters long!', 'error');
            return false;
        }
        
        // Phone validation
        const phoneRegex = /^\d{10}$/;
        if (!phoneRegex.test(phone)) {
            showNotification('Please enter a valid 10-digit phone number!', 'error');
            return false;
        }
        
        return true;
    }

    function showNotification(message, type) {
        const notification = document.createElement('div');
        notification.className = `notification ${type}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    </script>
    <script src="assets/js/main.js"></script>
</body>
</html>
