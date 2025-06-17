<?php
include 'includes/auth.php';
include 'includes/db.php';

// Check if user ID is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid user ID.";
    header("Location: user_management.php");
    exit();
}

$userIdToDelete = (int)$_GET['id'];
$currentUserId = $_SESSION['user_id'] ?? 0;

// Prevent users from deleting themselves
if ($userIdToDelete === $currentUserId) {
    $_SESSION['error'] = "You cannot delete your own account.";
    header("Location: user_management.php");
    exit();
}

try {
    // Check if the user exists
    $stmt = $pdo->prepare("SELECT Username FROM users WHERE UserID = ?");
    $stmt->execute([$userIdToDelete]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $_SESSION['error'] = "User not found.";
        header("Location: user_management.php");
        exit();
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    // First delete all records from user_login_history for this user
    $stmt = $pdo->prepare("DELETE FROM user_login_history WHERE user_id = ?");
    $stmt->execute([$userIdToDelete]);
    
    // Now delete the user
    $stmt = $pdo->prepare("DELETE FROM users WHERE UserID = ?");
    $stmt->execute([$userIdToDelete]);
    
    // Commit transaction
    $pdo->commit();
    
    $_SESSION['success'] = "User '" . htmlspecialchars($user['Username']) . "' has been successfully deleted.";
    
} catch (PDOException $e) {
    // If any error occurs, roll back the transaction
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    $_SESSION['error'] = "Error deleting user: " . $e->getMessage();
}

// Redirect back to user management page
header("Location: user_management.php");
exit();
?>