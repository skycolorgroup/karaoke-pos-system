<?php
session_start();
require_once 'config/database.php';

// If user is already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $name = trim($_POST['name'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? 'staff';

    // Validation
    if (empty($username) || empty($email) || empty($name) || empty($password) || empty($confirm_password)) {
        $error = 'All fields are required';
    } elseif (strlen($username) < 4) {
        $error = 'Username must be at least 4 characters';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email format';
    } else {
        try {
            // Check if username already exists
            $sql = "SELECT user_id FROM users WHERE username = :username";
            $stmt = $db->prepare($sql);
            $stmt->execute([':username' => $username]);
            
            if ($stmt->fetch()) {
                $error = 'Username already exists';
            } else {
                // Check if email already exists
                $sql = "SELECT user_id FROM users WHERE email = :email";
                $stmt = $db->prepare($sql);
                $stmt->execute([':email' => $email]);
                
                if ($stmt->fetch()) {
                    $error = 'Email already registered';
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_BCRYPT);

                    // Insert new user
                    $sql = "INSERT INTO users (username, email, password, name, phone, role, status) 
                            VALUES (:username, :email, :password, :name, :phone, :role, 'active')";
                    $stmt = $db->prepare($sql);
                    
                    if ($stmt->execute([
                        ':username' => $username,
                        ':email' => $email,
                        ':password' => $hashed_password,
                        ':name' => $name,
                        ':phone' => $phone,
                        ':role' => $role
                    ])) {
                        $success = 'Registration successful! Redirecting to login...';
                        header('refresh:2;url=login.php');
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Karaoke POS</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .register-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 500px;
        }

        .register-container h1 {
            text-align: center;
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }

        .register-container .subtitle {
            text-align: center;
            color: #999;
            margin-bottom: 30px;
            font-size: 14px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #333;
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 14px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #c62828;
        }

        .success {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid #2e7d32;
        }

        .password-requirements {
            background: #f5f5f5;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #666;
            border-left: 4px solid #ff9800;
        }

        .password-requirements ul {
            margin-left: 20px;
            margin-top: 8px;
        }

        .password-requirements li {
            margin: 4px 0;
        }

        .register-btn {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            font-size: 16px;
            transition: transform 0.2s ease;
        }

        .register-btn:hover {
            transform: translateY(-2px);
        }

        .register-btn:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            color: #666;
            font-size: 14px;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        .role-info {
            background: #e3f2fd;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 12px;
            color: #1565c0;
            border-left: 4px solid #2196f3;
        }

        .role-info strong {
            display: block;
            margin-bottom: 8px;
        }

        .role-info ul {
            margin-left: 20px;
        }

        .role-info li {
            margin: 4px 0;
        }

        input[type="password"] {
            letter-spacing: 2px;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle .toggle-btn {
            position: absolute;
            right: 12px;
            top: 40px;
            background: none;
            border: none;
            cursor: pointer;
            color: #667eea;
            font-size: 14px;
            font-weight: 600;
        }

        @media (max-width: 600px) {
            .register-container {
                padding: 20px;
            }

            .register-container h1 {
                font-size: 24px;
            }

            .form-row {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <h1>🎤 Karaoke POS</h1>
        <p class="subtitle">Create Account</p>

        <?php if ($error): ?>
            <div class="error">❌ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success">✓ <?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <form method="POST" id="registerForm">
                <div class="password-requirements">
                    <strong>📋 Registration Requirements:</strong>
                    <ul>
                        <li>Username: minimum 4 characters</li>
                        <li>Password: minimum 6 characters</li>
                        <li>Email: valid email address</li>
                        <li>Role: Staff role for new users</li>
                    </ul>
                </div>

                <div class="form-group">
                    <label for="name">Full Name *</label>
                    <input type="text" id="name" name="name" required placeholder="John Doe">
                </div>

                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required placeholder="john@example.com">
                </div>

                <div class="form-group">
                    <label for="username">Username *</label>
                    <input type="text" id="username" name="username" required placeholder="johndoe" minlength="4">
                </div>

                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" placeholder="+63 912 345 6789">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <div class="password-toggle">
                            <input type="password" id="password" name="password" required placeholder="••••••" minlength="6">
                            <button type="button" class="toggle-btn" onclick="togglePassword('password')">Show</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <div class="password-toggle">
                            <input type="password" id="confirm_password" name="confirm_password" required placeholder="••••••" minlength="6">
                            <button type="button" class="toggle-btn" onclick="togglePassword('confirm_password')">Show</button>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="role">Role *</label>
                    <select id="role" name="role" required>
                        <option value="staff">Staff (Server)</option>
                        <option value="cashier">Cashier</option>
                        <option value="manager">Manager</option>
                    </select>
                </div>

                <div class="role-info">
                    <strong>📝 Role Descriptions:</strong>
                    <ul>
                        <li><strong>Staff:</strong> Take orders, manage tables</li>
                        <li><strong>Cashier:</strong> Process payments, generate invoices</li>
                        <li><strong>Manager:</strong> Manage staff, inventory, reports</li>
                    </ul>
                </div>

                <button type="submit" class="register-btn">Create Account</button>
            </form>

            <div class="login-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const button = event.target;
            
            if (field.type === 'password') {
                field.type = 'text';
                button.textContent = 'Hide';
            } else {
                field.type = 'password';
                button.textContent = 'Show';
            }
        }

        // Form validation
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Password must be at least 6 characters!');
                return false;
            }
        });

        // Real-time username availability check
        document.getElementById('username').addEventListener('blur', function() {
            const username = this.value;
            
            if (username.length >= 4) {
                fetch('api/check-username.php?username=' + encodeURIComponent(username))
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            this.style.borderColor = '#f44336';
                            alert('Username already taken!');
                        } else {
                            this.style.borderColor = '#4caf50';
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });

        // Real-time email availability check
        document.getElementById('email').addEventListener('blur', function() {
            const email = this.value;
            
            if (email.includes('@')) {
                fetch('api/check-email.php?email=' + encodeURIComponent(email))
                    .then(response => response.json())
                    .then(data => {
                        if (data.exists) {
                            this.style.borderColor = '#f44336';
                            alert('Email already registered!');
                        } else {
                            this.style.borderColor = '#4caf50';
                        }
                    })
                    .catch(error => console.error('Error:', error));
            }
        });
    </script>
</body>
</html>
