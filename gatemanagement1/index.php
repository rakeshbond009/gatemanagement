<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gate Management System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-box">
            <h1>Gate Management System</h1>
            <?php
            if (isset($_GET['error'])) {
                echo '<div class="alert error">';
                switch ($_GET['error']) {
                    case 'invalid_credentials':
                        echo 'Invalid username or password!';
                        break;
                    case 'inactive':
                        echo 'Your account is inactive. Please contact the administrator.';
                        break;
                    case 'not_logged_in':
                        echo 'Please log in to access the system.';
                        break;
                    default:
                        echo 'An error occurred. Please try again.';
                }
                echo '</div>';
            }
            if (isset($_GET['success'])) {
                echo '<div class="alert success">';
                switch ($_GET['success']) {
                    case 'registration':
                        echo 'Registration successful! Please wait for admin approval.';
                        break;
                    case 'logout':
                        echo 'You have been successfully logged out.';
                        break;
                    default:
                        echo 'Operation successful!';
                }
                echo '</div>';
            }
            ?>
            <form id="loginForm" onsubmit="return handleLogin(event)">
                <div class="form-group">
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="form-group">
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <div class="form-group">
                    <select name="role" required>
                        <option value="">Select Role</option>
                        <option value="resident">Resident</option>
                        <option value="security">Security</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <button type="submit">Login</button>
                <p class="text-center">
                    Don't have an account? <a href="register.php">Register here</a>
                </p>
            </form>
        </div>
    </div>
    
    <script>
    async function handleLogin(event) {
        event.preventDefault();
        
        const form = document.getElementById('loginForm');
        const formData = {
            username: form.querySelector('input[name="username"]').value.trim(),
            password: form.querySelector('input[name="password"]').value,
            role: form.querySelector('select[name="role"]').value
        };
        
        if (!formData.username || !formData.password || !formData.role) {
            alert('Please fill in all fields!');
            return false;
        }
        
        try {
            const response = await fetch('api/auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.location.href = data.redirect;
            } else {
                alert(data.message || 'Login failed. Please try again.');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('An error occurred while logging in. Please try again.');
        }
        
        return false;
    }
    </script>
</body>
</html>
