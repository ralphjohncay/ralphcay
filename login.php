<?php
session_start();

// Check if the user is already logged in, and redirect to the appropriate dashboard
if (isset($_SESSION['user'])) {
    if ($_SESSION['user']['RoleID'] == 1 || $_SESSION['user']['RoleID'] == 2) {
        // Redirect to the Admin/Staff dashboard
        header("Location: dashboard.php");
    } elseif ($_SESSION['user']['RoleID'] == 3) {
        // Redirect to the Customer dashboard
        header("Location: customer_dashboard.php");
    }
    exit();
}

// Handle direct form submission (without AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    // Database connection
    $servername = "localhost";
    $db_username = "root";
    $db_password = ""; 
    $dbname = "inventory_db";
    
    // Create connection
    $conn = new mysqli($servername, $db_username, $db_password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        $error = "Connection failed: " . $conn->connect_error;
    } else {
        // Prepare SQL statement to retrieve user data
        $stmt = $conn->prepare("SELECT u.UserID, u.Username, u.Password, u.RoleID, r.RoleName 
                               FROM users u 
                               JOIN roles r ON u.RoleID = r.RoleID 
                               WHERE u.Username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['Password'])) {
                // Password is correct, create session
                $_SESSION['user'] = [
                    'UserID' => $user['UserID'],
                    'Username' => $user['Username'],
                    'RoleID' => $user['RoleID'],
                    'RoleName' => $user['RoleName']
                ];
                
                // ADD THE LOGIN HISTORY CODE RIGHT HERE
                $login_time = date('Y-m-d H:i:s');
                $stmt = $conn->prepare("INSERT INTO user_login_history (user_id, login_time) VALUES (?, ?)");
                $stmt->bind_param("is", $user['UserID'], $login_time);
                $stmt->execute();
                
                // Store the login history ID in the session
                $_SESSION['login_history_id'] = $conn->insert_id;
                
                // Redirect based on role
                if ($user['RoleID'] == 1 || $user['RoleID'] == 2) {
                    // Admin or Staff
                    header("Location: dashboard.php");
                } elseif ($user['RoleID'] == 3) {
                    // Customer
                    header("Location: customer_dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid password";
            }
        } else {
            $error = "Username not found";
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Geist+Sans:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --valid: hsl(140 80% 40%);
            --invalid: hsl(10 80% 40%);
            --input: hsl(0 0% 0%);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 100;
            background-color: hsl(0 0% 6%);
            color: hsl(0 0% 98%);
            font-family: "Geist Sans", "SF Pro", sans-serif;
        }

        .form-group {
            --active: 0;
            container-type: inline-size;
            flex: 1;
            margin-bottom: 1em;
        }

        form {
            width: 40ch;
        }

        input {
            --is-valid: 0;
            --is-invalid: 0;
            background: linear-gradient(var(--input), var(--input)) padding-box,
                linear-gradient(var(--invalid), var(--invalid))
                  calc((1 - var(--is-invalid)) * -100cqi) 0 / 100% 100% border-box,
                linear-gradient(var(--valid), var(--valid))
                  calc((1 - var(--is-valid)) * 100cqi) 0 / 100% 100% border-box,
                var(--input);
            border: 2px solid transparent;
            font-size: 1rem;
            background-repeat: no-repeat;
            max-width: 100%;
            font-family: "Geist Sans", "SF Pro", sans-serif;
            font-weight: 40;
            background-color: #3b4148;
            border-radius: 10px;
            color: #a9a9a9;
            padding: 0 16px;
            width: 100%;
            outline: 0;
            height: 50px;
        }

        label {
            margin-bottom: 0.5rem;
            display: inline-block;
            padding-left: 5px;
            opacity: calc(var(--active) + 0.45);
            transition: opacity 0.5s;
        }

        .form-group:focus-within {
            --active: 1;
        }

        input:invalid:not(:placeholder-shown):not(:focus-visible) {
            --is-invalid: 1;
        }

        input:valid {
            --is-valid: 1;
        }

        @media (prefers-reduced-motion: no-preference) {
            input {
                transition: background-position 0.5s;
            }
        }

        .submitbtn {
            background: #2a292c;
            border: 0;
            margin-top: 0.5rem;
            width: 100%;
            height: 45px;
            border-radius: 10px;
            color: white;
            cursor: pointer;
            transition: background 0.3s ease-in-out;
        }

        .submitbtn:hover {
            background: #404949;
        }

        h1 {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.5rem;
            font-weight: 400;
            font-size: 30px;
            color: #a9a9a9;
        }

        /* Error and Success Messages */
        .error-message {
            background-color: #ff4757;
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-message i {
            margin-right: 8px;
        }

        .success-message {
            background-color: #2ed573;
            color: white;
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .success-message i {
            margin-right: 8px;
        }

        /* Login Header */
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-logo {
            font-size: 2.5rem;
            color: #a9a9a9;
            margin-bottom: 10px;
        }

        .login-header p {
            color: #a9a9a9;
            font-size: 0.9rem;
            font-weight: 400;
        }

        /* Register Link */
        .register-link {
            text-align: center;
            margin-top: 25px;
        }

        .register-link a {
            color: #a9a9a9;
            text-decoration: none;
            font-weight: 400;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            font-size: 14px;
        }

        .register-link a:hover {
            color: white;
            text-decoration: underline;
        }

        .register-link i {
            margin-right: 8px;
        }

        /* Footer */
        .login-footer {
            text-align: center;
            margin-top: 25px;
            font-size: 12px;
            color: #a9a9a9;
            padding-top: 20px;
            border-top: 1px solid #3b4148;
        }

        /* Loading overlay styles */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(15, 15, 15, 0.8);
            backdrop-filter: blur(5px);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
        }

        .loading-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(169, 169, 169, 0.3);
            border-radius: 50%;
            border-top-color: #a9a9a9;
            animation: spin 1s ease-in-out infinite;
            margin-bottom: 15px;
        }

        .loading-text {
            color: #a9a9a9;
            font-size: 1.1rem;
            font-weight: 400;
            margin-top: 10px;
        }

        .loading-progress {
            width: 180px;
            height: 6px;
            background: rgba(169, 169, 169, 0.3);
            border-radius: 3px;
            margin-top: 15px;
            overflow: hidden;
            position: relative;
        }

        .loading-progress-bar {
            height: 100%;
            width: 0%;
            background: #a9a9a9;
            border-radius: 3px;
            transition: width 2s ease;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 480px) {
            form {
                width: 100%;
                max-width: 350px;
                margin: 0 10px;
            }
        }
    </style>
</head>
<body>
    <form id="loginForm" method="POST" action="login.php">
        <div class="login-header">
            <div class="login-logo">
                <i class="fas fa-box-open"></i>
            </div>
            <h1>Inventory System</h1>
            <p>Sign in to your account</p>
        </div>
        
        <!-- Display error message if there is any -->
        <?php if (isset($error)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Display success message if redirected from registration -->
        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i>
                <?php echo $_GET['success']; ?>
            </div>
        <?php endif; ?>
        
        <div class="form-group">
            <label for="username">Username</label>
            <input type="text" name="username" placeholder="Enter your username" id="username" autocomplete="off" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" placeholder="Enter your password" id="password" autocomplete="off" required>
        </div>
        
        <button type="submit" class="submitbtn">
            <i class="fas fa-sign-in-alt"></i> Login
        </button>
        
        <!-- Registration link -->
        <div class="register-link">
            <a href="register.php">
                <i class="fas fa-user-plus"></i> Don't have an account? Create one
            </a>
        </div>
        
        <div class="login-footer">
            <p>Inventory Management System &copy; <?php echo date('Y'); ?></p>
        </div>
    </form>

    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
        <div class="loading-text">Logging in...</div>
        <div class="loading-progress">
            <div class="loading-progress-bar" id="progressBar"></div>
        </div>
    </div>

    <script>
        // Handle form submission with loading animation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            // Prevent the form from submitting immediately
            e.preventDefault();
            
            // Store reference to the form
            const form = this;
            
            // Show loading overlay
            document.getElementById('loadingOverlay').classList.add('active');
            
            // Start progress bar animation
            const progressBar = document.getElementById('progressBar');
            progressBar.style.width = '0%';
            
            // Animate progress bar to 100% over 2 seconds
            setTimeout(() => {
                progressBar.style.width = '100%';
            }, 100);
            
            // Wait for 2 seconds before actually submitting the form
            setTimeout(() => {
                form.submit(); // Submit the form after 2 seconds
            }, 2000);
        });
    </script>
</body>
</html>