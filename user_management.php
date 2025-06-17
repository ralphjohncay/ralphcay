<?php
include 'includes/auth.php';
include 'includes/db.php';

// Fetch all users
try {
    $stmt = $pdo->query("SELECT UserID, Username, RoleID FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching users: " . $e->getMessage());
}

// Fetch login history (most recent first)
try {
    $stmt = $pdo->query("
        SELECT h.id, h.user_id, h.login_time, h.logout_time, u.Username 
        FROM user_login_history h
        JOIN users u ON h.user_id = u.UserID
        ORDER BY h.login_time DESC
        LIMIT 50
    ");
    $login_history = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error fetching login history: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>User Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container mt-5">
    <h2 class="mb-4">User Management</h2>
    
    <div class="d-flex justify-content-between mb-3">
        <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
        <a href="register.php" class="btn btn-primary">Add New User</a>
    </div>

    <ul class="nav nav-tabs mb-4" id="myTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab" aria-controls="users" aria-selected="true">Users</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="login-history-tab" data-bs-toggle="tab" data-bs-target="#login-history" type="button" role="tab" aria-controls="login-history" aria-selected="false">Login History</button>
        </li>
    </ul>
    
    <div class="tab-content" id="myTabContent">
        <div class="tab-pane fade show active" id="users" role="tabpanel" aria-labelledby="users-tab">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>User ID</th>
                        <th>Username</th>
                        <th>Role ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($users): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($user['UserID']); ?></td>
                                <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                <td><?php echo htmlspecialchars($user['RoleID']); ?></td>
                                <td>
                                    <a href="edit_user.php?id=<?php echo $user['UserID']; ?>" class="btn btn-sm btn-warning">Edit</a>
                                    <a href="delete_user.php?id=<?php echo $user['UserID']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center">No users found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="tab-pane fade" id="login-history" role="tabpanel" aria-labelledby="login-history-tab">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Login Time</th>
                        <th>Logout Time</th>
                        <th>Session Duration</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($login_history): ?>
                        <?php foreach ($login_history as $entry): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($entry['id']); ?></td>
                                <td><?php echo htmlspecialchars($entry['Username']); ?></td>
                                <td><?php echo htmlspecialchars($entry['login_time']); ?></td>
                                <td>
                                    <?php 
                                    if ($entry['logout_time']) {
                                        echo htmlspecialchars($entry['logout_time']);
                                    } else {
                                        echo '<span class="badge bg-success">Active</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if ($entry['logout_time']) {
                                        $login = new DateTime($entry['login_time']);
                                        $logout = new DateTime($entry['logout_time']);
                                        $duration = $logout->diff($login);
                                        
                                        if ($duration->d > 0) {
                                            echo $duration->format('%d days, %h hrs, %i mins');
                                        } else if ($duration->h > 0) {
                                            echo $duration->format('%h hrs, %i mins, %s secs');
                                        } else {
                                            echo $duration->format('%i mins, %s secs');
                                        }
                                    } else {
                                        $login = new DateTime($entry['login_time']);
                                        $now = new DateTime();
                                        $duration = $now->diff($login);
                                        
                                        if ($duration->d > 0) {
                                            echo $duration->format('%d days, %h hrs, %i mins');
                                        } else if ($duration->h > 0) {
                                            echo $duration->format('%h hrs, %i mins, %s secs');
                                        } else {
                                            echo $duration->format('%i mins, %s secs');
                                        }
                                        
                                        echo ' <span class="badge bg-warning text-dark">Ongoing</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center">No login history found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>