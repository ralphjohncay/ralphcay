<?php
session_start();

// Check if the user is already logged in, and redirect to the appropriate dashboard
if (isset($_SESSION['user'])) {
    header("Location: dashboard.php");
    exit();
}

// Database connection function
function getConnection() {
    $servername = "localhost";
    $username = "root";
    $password = ""; 
    $dbname = "inventory_db";
    
    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    $roleID = $_POST['role'];
    $registrationCode = isset($_POST['registration_code']) ? $_POST['registration_code'] : '';
    
    // Initialize errors array
    $errors = [];
    
    // Validate username (at least 4 characters)
    if (strlen($username) < 4) {
        $errors[] = "Username must be at least 4 characters long";
    }
    
    // Check if username already exists
    $conn = getConnection();
    $stmt = $conn->prepare("SELECT UserID FROM users WHERE Username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Username already exists";
    }
    $stmt->close();
    
    // Validate password (at least 8 characters, with at least one uppercase, one lowercase, and one number)
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    }
    
    // Check if passwords match
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match";   
    }
    
    // Validate registration codes based on role
    // Always check code when role requires it, regardless of whether field is visible in UI
    if ($roleID == 1) { // Admin role
        $adminCode = "ADMIN123"; // Admin registration code
        if ($registrationCode !== $adminCode) {
            $errors[] = "Invalid admin registration code";
        }
    } elseif ($roleID == 2) { // Staff role
        $staffCode = "STAFF456"; // Staff registration code
        if ($registrationCode !== $staffCode) {
            $errors[] = "Invalid staff registration code";
        }
    }
    // No registration code needed for customer/user role (3)
    
    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user into database
        $stmt = $conn->prepare("INSERT INTO users (Username, Password, RoleID) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $username, $hashedPassword, $roleID);
        
        if ($stmt->execute()) {
            // Registration successful, redirect to login page with success message
            header("Location: login.php?success=Registration successful! You can now log in.");
            exit();
        } else {
            $errors[] = "Registration failed: " . $conn->error;
        }
        $stmt->close();
    }
    
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Geist+Sans:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
:root {
  --valid: hsl(140 80% 40%);
  --invalid: hsl(10 80% 40%);
  --input: hsl(0 0% 0%);
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
  margin: 0;
  padding: 20px;
  box-sizing: border-box;
}

.register-container {
  width: 40ch;
  max-width: 100%;
  padding: 2rem;
  background: hsl(0 0% 8%);
  border-radius: 15px;
  box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
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

.register-logo {
  font-size: 2.2rem;
  color: #a9a9a9;
  margin-bottom: 1rem;
  text-align: center;
}

.register-header {
  text-align: center;
  margin-bottom: 2rem;
}

.register-header p {
  color: #a9a9a9;
  font-size: 1rem;
  font-weight: 200;
  margin-top: 0.5rem;
}

.form-group {
  --active: 0;
  container-type: inline-size;
  flex: 1;
  margin-bottom: 1.5rem;
  position: relative;
}

.form-group:focus-within {
  --active: 1;
}

form {
  width: 100%;
}

label {
  margin-bottom: 0.5rem;
  display: inline-block;
  padding-left: 5px;
  opacity: calc(var(--active) + 0.45);
  transition: opacity 0.5s;
  color: #a9a9a9;
  font-weight: 300;
}

input, select {
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
  font-weight: 300;
  background-color: #3b4148;
  border-radius: 10px;
  color: #a9a9a9;
  padding: 0 16px;
  width: 100%;
  outline: 0;
  height: 50px;
  box-sizing: border-box;
  transition: background-position 0.5s;
}

input:invalid:not(:placeholder-shown):not(:focus-visible) {
  --is-invalid: 1;
}

input:valid {
  --is-valid: 1;
}

input::placeholder {
  color: #6c757d;
}

select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23a9a9a9' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
  background-repeat: no-repeat;
  background-position: right 1rem center;
  background-size: 1em;
}

select option {
  background-color: #3b4148;
  color: #a9a9a9;
}

.input-group {
  position: relative;
}

.input-icon {
  position: absolute;
  top: 50%;
  left: 16px;
  transform: translateY(-50%);
  color: #a9a9a9;
  font-size: 16px;
  z-index: 1;
}

input[type="text"], input[type="password"] {
  padding-left: 45px;
}

select {
  padding-left: 45px;
}

.password-toggle {
  position: absolute;
  right: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: #a9a9a9;
  cursor: pointer;
  transition: color 0.2s;
  z-index: 10;
}

.password-toggle:hover {
  color: #ffffff;
}

.password-strength {
  margin-top: 0.5rem;
  font-size: 0.8rem;
  color: #a9a9a9;
}

.password-strength-meter {
  height: 6px;
  width: 100%;
  background: #3b4148;
  border-radius: 3px;
  margin-top: 5px;
  position: relative;
  overflow: hidden;
}

.password-strength-meter::before {
  content: '';
  position: absolute;
  left: 0;
  height: 100%;
  width: 0%;
  border-radius: 3px;
  background: linear-gradient(90deg, var(--invalid) 0%, hsl(45, 100%, 50%) 50%, var(--valid) 100%);
  transition: width 0.3s ease;
}

.password-strength-meter.weak::before {
  width: 33.33%;
}

.password-strength-meter.medium::before {
  width: 66.66%;
}

.password-strength-meter.strong::before {
  width: 100%;
}

.code-container {
  margin-top: 1rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.05);
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
}

#codeHelperText {
  color: #a9a9a9 !important;
  margin-top: 0.5rem;
  display: block;
  font-size: 0.8rem;
  font-weight: 200;
}

button, .submitbtn {
  background: #2a292c;
  border: 0;
  margin-top: 0.5rem;
  width: 100%;
  height: 45px;
  border-radius: 10px;
  color: white;
  cursor: pointer;
  transition: background 0.3s ease-in-out;
  font-family: "Geist Sans", "SF Pro", sans-serif;
  font-weight: 400;
  font-size: 1rem;
  display: flex;
  align-items: center;
  justify-content: center;
  text-transform: uppercase;
  letter-spacing: 1px;
}

button:hover, .submitbtn:hover {
  background: #404949;
}

button i {
  margin-right: 0.5rem;
}

.error-message {
  padding: 1rem;
  border-radius: 10px;
  margin-bottom: 1.5rem;
  font-size: 0.875rem;
  background-color: rgba(220, 53, 69, 0.1);
  color: var(--invalid);
  border-left: 4px solid var(--invalid);
}

.error-list {
  list-style-type: none;
  padding: 0;
  text-align: left;
  margin-top: 0.5rem;
}

.error-list li {
  margin-bottom: 0.5rem;
  display: flex;
  align-items: center;
  font-weight: 300;
}

.error-list li:before {
  content: "\f071";
  font-family: "Font Awesome 5 Free";
  font-weight: 900;
  margin-right: 0.5rem;
  color: var(--invalid);
}

.login-link {
  text-align: center;
  margin-top: 1.5rem;
}

.login-link a {
  color: #a9a9a9;
  text-decoration: none;
  font-weight: 300;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
}

.login-link a:hover {
  color: #ffffff;
  text-decoration: underline;
}

.login-link i {
  margin-right: 0.4rem;
}

.register-footer {
  text-align: center;
  margin-top: 2rem;
  font-size: 0.875rem;
  color: #a9a9a9;
  padding-top: 1.5rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  font-weight: 200;
}

@media (max-width: 480px) {
  .register-container {
    padding: 1.5rem;
    margin: 10px;
  }
  
  body {
    padding: 10px;
  }
}

@media (prefers-reduced-motion: no-preference) {
  input, select {
    transition: background-position 0.5s;
  }
}
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <div class="register-logo">
                <i class="fas fa-box-open"></i>
            </div>
            <h1>Create an Account</h1>
            <p>Join the Inventory Management System</p>
        </div>
        
        <!-- Display error messages if there are any -->
        <?php if (!empty($errors)): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i>
                <strong>Please fix the following errors:</strong>
                <ul class="error-list">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form id="registerForm" method="POST" action="register.php">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <i class="input-icon fas fa-user"></i>
                    <input type="text" id="username" name="username" placeholder="Choose a username" required
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" id="password" name="password" placeholder="Create a password" required>
                    <i class="password-toggle fas fa-eye" id="togglePassword"></i>
                </div>
                <div class="password-strength">
                    <span id="passwordStrengthText">Password strength: Not entered</span>
                    <div class="password-strength-meter" id="passwordStrengthMeter"></div>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-group">
                    <i class="input-icon fas fa-lock"></i>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required>
                    <i class="password-toggle fas fa-eye" id="toggleConfirmPassword"></i>
                </div>
            </div>
            
            <div class="form-group">
                <label for="role">Account Type</label>
                <div class="input-group">
                    <i class="input-icon fas fa-user-shield"></i>
                    <select id="role" name="role" required>
                        <option value="" disabled selected>Select account type</option>
                        <option value="3" <?php echo (isset($_POST['role']) && $_POST['role'] == '3') ? 'selected' : ''; ?>>Customer</option>
                        <option value="2" <?php echo (isset($_POST['role']) && $_POST['role'] == '2') ? 'selected' : ''; ?>>Staff</option>
                        <option value="1" <?php echo (isset($_POST['role']) && $_POST['role'] == '1') ? 'selected' : ''; ?>>Admin</option>
                    </select>
                </div>
            </div>
            
            <!-- Use a single registration code input with single ID but change its container -->
            <div id="codeContainer" class="code-container" style="display: none;">
                <div class="form-group">
                    <label for="registration_code" id="codeLabel">Registration Code</label>
                    <div class="input-group">
                        <i class="input-icon fas fa-key"></i>
                        <input type="password" id="registration_code" name="registration_code" placeholder="Enter registration code">
                        <i class="password-toggle fas fa-eye" id="toggleRegistrationCode"></i>
                    </div>
                    <small id="codeHelperText">
                        <i class="fas fa-info-circle"></i> <span id="codeHelperSpan">Registration requires a special code.</span>
                    </small>
                </div>
            </div>
            
            <button type="submit" id="registerButton" class="submitbtn"><i class="fas fa-user-plus"></i> Create Account</button>
        </form>
        
        <div class="login-link">
            <a href="login.php"><i class="fas fa-sign-in-alt"></i> Already have an account? Login</a>
        </div>
        
        <div class="register-footer">
            <p>Inventory Management System &copy; <?php echo date('Y'); ?></p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            togglePasswordVisibility('password', this);
        });
        
        document.getElementById('toggleConfirmPassword').addEventListener('click', function() {
            togglePasswordVisibility('confirm_password', this);
        });
        
        document.getElementById('toggleRegistrationCode').addEventListener('click', function() {
            togglePasswordVisibility('registration_code', this);
        });
        
        function togglePasswordVisibility(inputId, icon) {
            const passwordInput = document.getElementById(inputId);
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        // Show/hide registration code field based on role selection
        document.getElementById('role').addEventListener('change', function() {
            const codeContainer = document.getElementById('codeContainer');
            const codeInput = document.getElementById('registration_code');
            const codeLabel = document.getElementById('codeLabel');
            const codeHelperSpan = document.getElementById('codeHelperSpan');
            
            // Clear any previous input value when changing roles
            codeInput.value = '';
            
            // Hide code container by default
            codeContainer.style.display = 'none';
            
            // Remove required attribute 
            codeInput.removeAttribute('required');
            
            if (this.value === '1') { // Admin role
                codeLabel.textContent = 'Admin Registration Code';
                codeHelperSpan.textContent = 'Admin registration requires a special code. Please contact your system administrator if you don\'t have this code.';
                codeInput.placeholder = 'Enter admin registration code';
                codeContainer.style.display = 'block';
                codeInput.setAttribute('required', 'required');
            } else if (this.value === '2') { // Staff role
                codeLabel.textContent = 'Staff Registration Code';
                codeHelperSpan.textContent = 'Staff registration requires a special code. Please contact your manager if you don\'t have this code.';
                codeInput.placeholder = 'Enter staff registration code';
                codeContainer.style.display = 'block';
                codeInput.setAttribute('required', 'required');
            }
            // Customer/User role (3) doesn't need any code
        });
        
        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const meter = document.getElementById('passwordStrengthMeter');
            const strengthText = document.getElementById('passwordStrengthText');
            
            // Remove previous classes
            meter.className = 'password-strength-meter';
            
            if (password.length === 0) {
                strengthText.textContent = 'Password strength: Not entered';
                return;
            }
            
            let strength = 0;
            
            // Criteria for strength
            if (password.length >= 8) strength += 1;
            if (password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[a-z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^A-Za-z0-9]/)) strength += 1;
            
            // Update meter and text based on strength
            if (strength <= 2) {
                meter.classList.add('weak');
                strengthText.textContent = 'Password strength: Weak';
            } else if (strength <= 4) {
                meter.classList.add('medium');
                strengthText.textContent = 'Password strength: Medium';
            } else {
                meter.classList.add('strong');
                strengthText.textContent = 'Password strength: Strong';
            }
        });
        
        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword === '') {
                this.setCustomValidity('');
            } else if (confirmPassword !== password) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });
        
        // Set initial state based on selected role (in case of form resubmission)
        window.addEventListener('DOMContentLoaded', function() {
            const roleSelect = document.getElementById('role');
            if (roleSelect.value) {
                // Trigger the change event to set up the form correctly
                const event = new Event('change');
                roleSelect.dispatchEvent(event);
            }
        });
    </script>
</body>
</html>