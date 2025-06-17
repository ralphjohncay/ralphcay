<?php
session_start();
require_once "includes/db.php";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = hash("sha256", $_POST['password']); // Same hash as used in insert

    // Query to check if the user exists and matches the credentials
    $stmt = $pdo->prepare("SELECT users.*, roles.RoleName FROM users 
                           JOIN roles ON users.RoleID = roles.RoleID 
                           WHERE Username = ? AND Password = ?");
    $stmt->execute([$username, $password]);

    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch();
        
        // Set session variables based on user data and role
        $_SESSION['user_id'] = $user['UserID'];
        $_SESSION['username'] = $user['Username'];
        $_SESSION['role'] = $user['RoleName'];
        $_SESSION['role_id'] = $user['RoleID'];

        // Redirect based on the role
        if ($user['RoleName'] == 'Admin') {
            // Redirect to Admin Dashboard if user is Admin
            header("Location: dashboard.php");
        } else if ($user['RoleName'] == 'Staff') {
            // Redirect to Staff Dashboard if user is Staff
            header("Location: dashboard.php");
        } else if ($user['RoleName'] == 'User') {
            // Redirect to Customer Dashboard if user is Customer
            header("Location: customer_dashboard.php");
        } else {
            // Default redirect for any other role
            header("Location: index.php");
        }
        exit(); // Ensure the script stops after redirect
    } else {
        // Invalid credentials
        header("Location: login.php?error=Invalid credentials");
        exit();
    }
}
?>