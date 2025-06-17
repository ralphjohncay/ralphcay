<?php
error_reporting(E_ALL); // Report all errors
ini_set('display_errors', 1); 
require_once "includes/auth.php";
require_once "includes/db.php";

// Check if user is admin
$user = $_SESSION['user'];
if ($user['RoleName'] != 'Admin') {
    header("Location: dashboard.php");
    exit;
}

// Handle form submission
$updateSuccess = false;
$updateError = false;
$message = '';

// Get current settings
$stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

// If no settings exist yet, create default values
if (!$settings) {
    $settings = [
        'company_name' => 'My Inventory System',
        'email' => 'admin@example.com',
        'low_stock_threshold' => 5,
        'critical_stock_threshold' => 3,
        'currency_symbol' => '$',
        'date_format' => 'Y-m-d',
        'timezone' => 'UTC'
    ];
    
    // Insert default settings
    $sql = "CREATE TABLE IF NOT EXISTS system_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_name VARCHAR(100),
        email VARCHAR(100),
        low_stock_threshold INT,
        critical_stock_threshold INT,
        currency_symbol VARCHAR(10),
        date_format VARCHAR(20),
        timezone VARCHAR(50),
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    
    $sql = "INSERT INTO system_settings 
            (company_name, email, low_stock_threshold, critical_stock_threshold, currency_symbol, date_format, timezone) 
            VALUES 
            (:company_name, :email, :low_stock_threshold, :critical_stock_threshold, :currency_symbol, :date_format, :timezone)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':company_name' => $settings['company_name'],
        ':email' => $settings['email'],
        ':low_stock_threshold' => $settings['low_stock_threshold'],
        ':critical_stock_threshold' => $settings['critical_stock_threshold'],
        ':currency_symbol' => $settings['currency_symbol'],
        ':date_format' => $settings['date_format'],
        ':timezone' => $settings['timezone']
    ]);
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update settings
        $sql = "UPDATE system_settings SET 
                company_name = :company_name,
                email = :email,
                low_stock_threshold = :low_stock_threshold,
                critical_stock_threshold = :critical_stock_threshold,
                currency_symbol = :currency_symbol,
                date_format = :date_format,
                timezone = :timezone
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            ':company_name' => $_POST['company_name'],
            ':email' => $_POST['email'],
            ':low_stock_threshold' => (int)$_POST['low_stock_threshold'],
            ':critical_stock_threshold' => (int)$_POST['critical_stock_threshold'],
            ':currency_symbol' => $_POST['currency_symbol'],
            ':date_format' => $_POST['date_format'],
            ':timezone' => $_POST['timezone'],
            ':id' => 1
        ]);
        
        if ($result) {
            $updateSuccess = true;
            $message = "Settings updated successfully!";
            
            // Refresh settings
            $stmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
            $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        }
    } catch (PDOException $e) {
        $updateError = true;
        $message = "Error updating settings: " . $e->getMessage();
    }
}

// Get available timezones
$timezones = DateTimeZone::listIdentifiers();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-hover: #4f46e5;
            --primary-light: #eef2ff;
            --secondary-color: #8b5cf6;
            --text-color: #1f2937;
            --text-light: #6b7280;
            --text-xlight: #9ca3af;
            --bg-light: #f9fafb;
            --bg-white: #ffffff;
            --bg-dark: #111827;
            --border-color: #e5e7eb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --rounded: 0.5rem;
            --rounded-lg: 1rem;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Nunito', sans-serif;
        }
        
        body {
            background-color: var(--bg-light);
            color: var(--text-color);
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar Styles */
        .sidebar {
            width: 280px;
            background: var(--bg-white);
            border-right: 1px solid var(--border-color);
            display: flex;
            flex-direction: column;
            position: fixed;
            top: 0;
            left: 0;
            bottom: 0;
            z-index: 40;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }
        
        .sidebar-logo {
            padding: 1.5rem;
            display: flex;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }
        
        .logo-icon {
            font-size: 1.75rem;
            color: var(--primary-color);
            margin-right: 0.75rem;
        }
        
        .logo-text {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .nav-items {
            padding: 1.5rem 0;
            flex-grow: 1;
            overflow-y: auto;
        }
        
        .nav-section {
            margin-bottom: 1.5rem;
        }
        
        .nav-section-title {
            padding: 0 1.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--text-light);
            margin-bottom: 0.5rem;
            letter-spacing: 0.05em;
        }
        
        .nav-item {
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: var(--text-color);
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
        }
        
        .nav-item:hover {
            background: var(--primary-light);
            color: var(--primary-color);
            border-left-color: var(--primary-color);
        }
        
        .nav-item.active {
            background: var(--primary-light);
            color: var(--primary-color);
            border-left-color: var(--primary-color);
            font-weight: 600;
        }
        
        .nav-icon {
            margin-right: 0.75rem;
            font-size: 1.1rem;
            min-width: 1.5rem;
            text-align: center;
        }
        
        .sidebar-footer {
            padding: 1rem 1.5rem;
            border-top: 1px solid var(--border-color);
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            padding: 0.75rem 1rem;
            background: var(--bg-light);
            color: var(--text-color);
            border: none;
            border-radius: var(--rounded);
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.2s ease;
            width: 100%;
            justify-content: center;
        }
        
        .logout-btn:hover {
            background: #fee2e2;
            color: var(--danger-color);
        }
        
        .logout-icon {
            margin-right: 0.5rem;
        }
        
        /* Main Content Styles */
        .main-content {
            flex-grow: 1;
            margin-left: 280px;
            display: flex;
            flex-direction: column;
        }
        
        .topbar {
            padding: 1rem 2rem;
            background: var(--bg-white);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 30;
            box-shadow: var(--shadow-sm);
        }
        
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: var(--text-color);
            font-size: 1.25rem;
            cursor: pointer;
        }
        
        .page-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-color);
        }
        
        .topbar-right {
            display: flex;
            align-items: center;
        }
        
        .notification-bell {
            position: relative;
            margin-right: 1.5rem;
            color: var(--text-light);
            font-size: 1.25rem;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        
        .notification-bell:hover {
            color: var(--primary-color);
        }
        
        .notification-indicator {
            position: absolute;
            top: -5px;
            right: -5px;
            background: var(--danger-color);
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            cursor: pointer;
            position: relative;
            padding: 0.5rem;
            border-radius: var(--rounded);
            transition: background 0.2s ease;
        }
        
        .user-profile:hover {
            background: var(--bg-light);
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            margin-right: 0.75rem;
        }
        
        .user-info {
            display: flex;
            flex-direction: column;
        }
        
        .user-name {
            font-weight: 600;
            color: var(--text-color);
        }
        
        .user-role {
            font-size: 0.75rem;
            color: var(--text-light);
        }
        
        .dropdown-icon {
            margin-left: 0.5rem;
            color: var(--text-light);
            transition: transform 0.2s ease;
        }
        
        .user-profile:hover .dropdown-icon {
            transform: rotate(180deg);
        }
        
        /* Content Styles */
        .content {
            padding: 2rem;
            flex-grow: 1;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .page-subtitle {
            color: var(--text-light);
            font-size: 1rem;
        }
        
        /* Settings Form Styles */
        .settings-panel {
            background: var(--bg-white);
            border-radius: var(--rounded-lg);
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .panel-header {
            background: var(--primary-light);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .panel-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--primary-color);
            display: flex;
            align-items: center;
        }
        
        .panel-title i {
            margin-right: 0.75rem;
        }
        
        .panel-body {
            padding: 1.5rem;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-color);
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--rounded);
            background: var(--bg-light);
            color: var(--text-color);
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--rounded);
            background: var(--bg-light);
            color: var(--text-color);
            font-size: 1rem;
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%236b7280'%3E%3Cpath d='M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6 1.41-1.41z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.5rem;
        }
        
        .form-select:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }
        
        .form-hint {
            font-size: 0.875rem;
            color: var(--text-light);
            margin-top: 0.5rem;
        }
        
        .form-submit {
            padding: 1.5rem;
            background: var(--bg-light);
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 1.5rem;
            border-radius: var(--rounded);
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            border: none;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-hover);
        }
        
        .btn-reset {
            background: var(--bg-white);
            color: var(--text-color);
            border: 1px solid var(--border-color);
            margin-right: 1rem;
        }
        
        .btn-reset:hover {
            background: var(--border-color);
        }
        
        .btn i {
            margin-right: 0.5rem;
        }
        
        /* Alert styles */
        .alert {
            padding: 1rem;
            border-radius: var(--rounded);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border: 1px solid rgba(239, 68, 68, 0.2);
        }
        
        .alert i {
            font-size: 1.25rem;
            margin-right: 0.75rem;
        }
        
        /* Media queries */
        @media (max-width: 1024px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                box-shadow: var(--shadow-lg);
            }
            
            .sidebar.open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .menu-toggle {
                display: flex;
                margin-right: 1rem;
            }
            
            .topbar {
                padding: 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .content {
                padding: 1.5rem 1rem;
            }
            
            .user-info {
                display: none;
            }
            
            .form-submit {
                flex-direction: column;
            }
            
            .btn-reset {
                margin-right: 0;
                margin-bottom: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-logo">
            <div class="logo-icon"><i class="fas fa-box-open"></i></div>
            <div class="logo-text">Inventory System</div>
        </div>
        
        <nav class="nav-items">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="dashboard.php" class="nav-item">
                    <i class="nav-icon fas fa-home"></i> Dashboard
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Inventory</div>
                <a href="products/index.php" class="nav-item">
                    <i class="nav-icon fas fa-box"></i> Products
                </a>
                <a href="suppliers/index.php" class="nav-item">
                    <i class="nav-icon fas fa-truck"></i> Suppliers
                </a>
                <a href="stock/index.php" class="nav-item">
                    <i class="nav-icon fas fa-warehouse"></i> Stock Management
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Sales & Reports</div>
                <a href="sales/index.php" class="nav-item">
                    <i class="nav-icon fas fa-shopping-cart"></i> Sales
                </a>
                <a href="reports/dashboard.php" class="nav-item">
                    <i class="nav-icon fas fa-chart-line"></i> Reports & Analytics
                </a>
            </div>
            
            <div class="nav-section">
                <div class="nav-section-title">Administration</div>
                <a href="user_management.php" class="nav-item">
                    <i class="nav-icon fas fa-users-cog"></i> User Management
                </a>
                <a href="settings.php" class="nav-item active">
                    <i class="nav-icon fas fa-cog"></i> Settings
                </a>
            </div>
        </nav>
        
        <div class="sidebar-footer">
            <a href="#" class="logout-btn" id="logoutButton">
                <i class="logout-icon fas fa-sign-out-alt"></i> Logout
            </a>
        </div>
    </aside>
    
    <!-- Main Content -->
    <main class="main-content">
        <!-- Top Navigation Bar -->
        <div class="topbar">
            <div class="topbar-left">
                <button id="menuToggle" class="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">Settings</h1>
            </div>
            
            <div class="topbar-right">
                <div class="notification-bell">
                    <i class="fas fa-bell"></i>
                    <span class="notification-indicator">3</span>
                </div>
                
                <div class="user-profile">
                    <div class="avatar">
                        <?php echo strtoupper(substr($user['Username'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <span class="user-name"><?= htmlspecialchars($user['Username']) ?></span>
                        <span class="user-role"><?= $user['RoleName'] ?></span>
                    </div>
                    <i class="dropdown-icon fas fa-chevron-down"></i>
                </div>
            </div>
        </div>
        
        <div class="content">
            <div class="page-header">
                <h1 class="page-title">System Settings</h1>
                <p class="page-subtitle">Configure your inventory system preferences and settings.</p>
            </div>
            
            <?php if ($updateSuccess): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <span><?= $message ?></span>
            </div>
            <?php endif; ?>
            
            <?php if ($updateError): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <span><?= $message ?></span>
            </div>
            <?php endif; ?>
            
            <form action="settings.php" method="POST">
                <div class="settings-panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-building"></i> Company Information
                        </h2>
                    </div>
                    <div class="panel-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="company_name" class="form-label">Company Name</label>
                                <input type="text" id="company_name" name="company_name" class="form-control" value="<?= htmlspecialchars($settings['company_name']) ?>" required>
                                <div class="form-hint">This name will appear in reports and invoices</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Support Email</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($settings['email']) ?>" required>
                                <div class="form-hint">Used for notifications and customer service</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="settings-panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-warehouse"></i> Inventory Settings
                        </h2>
                    </div>
                    <div class="panel-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="low_stock_threshold" class="form-label">Low Stock Threshold</label>
                                <input type="number" id="low_stock_threshold" name="low_stock_threshold" class="form-control" value="<?= $settings['low_stock_threshold'] ?>" min="1" required>
                                <div class="form-hint">Products with quantity below this will be marked as "low stock"</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="critical_stock_threshold" class="form-label">Critical Stock Threshold</label>
                                <input type="number" id="critical_stock_threshold" name="critical_stock_threshold" class="form-control" value="<?= $settings['critical_stock_threshold'] ?>" min="0" required>
                                <div class="form-hint">Products with quantity below this will be marked as "critical stock"</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="settings-panel">
                    <div class="panel-header">
                        <h2 class="panel-title">
                            <i class="fas fa-globe"></i> System Preferences
                        </h2>
                    </div>
                    <div class="panel-body">
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="currency_symbol" class="form-label">Currency Symbol</label>
                                <input type="text" id="currency_symbol" name="currency_symbol" class="form-control" value="<?= htmlspecialchars($settings['currency_symbol']) ?>" maxlength="5" required>
                                <div class="form-hint">Symbol to display for prices (e.g., $, €, £)</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="date_format" class="form-label">Date Format</label>
                                <select id="date_format" name="date_format" class="form-select">
                                    <option value="Y-m-d" <?= $settings['date_format'] == 'Y-m-d' ? 'selected' : '' ?>>YYYY-MM-DD</option>
                                    <option value="m/d/Y" <?= $settings['date_format'] == 'm/d/Y' ? 'selected' : '' ?>>MM/DD/YYYY</option>
                                    <option value="d/m/Y" <?= $settings['date_format'] == 'd/m/Y' ? 'selected' : '' ?>>DD/MM/YYYY</option>
                                    <option value="d.m.Y" <?= $settings['date_format'] == 'd.m.Y' ? 'selected' : '' ?>>DD.MM.YYYY</option>
                                </select>
                                <div class="form-hint">Format for displaying dates</div>
                            </div>
                            
                            <div class="form-group">
                                <label for="timezone" class="form-label">Timezone</label>
                                <select id="timezone" name="timezone" class="form-select">
                                    <?php foreach ($timezones as $tz): ?>
                                    <option value="<?= $tz ?>" <?= $settings['timezone'] == $tz ? 'selected' : '' ?>><?= $tz ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-hint">System timezone for date and time calculations</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-submit">
                        <button type="reset" class="btn btn-reset">
                            <i class="fas fa-undo"></i> Reset Changes
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </main>
    
    <script>
        // Toggle Sidebar on Mobile
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('open');
        });
        
        // Close sidebar when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuToggle = document.getElementById('menuToggle');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target) && sidebar.classList.contains('open')) {
                    sidebar.classList.remove('open');
                }
            }
        });
        
     // Handle logout functionality
     document.getElementById('logoutButton').addEventListener('click', function(e) {
            e.preventDefault();
            
            // Confirm before logging out
            if (confirm('Are you sure you want to log out?')) {
                window.location.href = 'logout.php';
            }
        });
        
        // Validate form before submission
        document.querySelector('form').addEventListener('submit', function(e) {
            const lowStockThreshold = parseInt(document.getElementById('low_stock_threshold').value);
            const criticalStockThreshold = parseInt(document.getElementById('critical_stock_threshold').value);
            
            // Ensure critical threshold is lower than low stock threshold
            if (criticalStockThreshold >= lowStockThreshold) {
                e.preventDefault();
                alert('Critical stock threshold must be lower than low stock threshold.');
                return false;
            }
            
            // Ensure values are positive
            if (lowStockThreshold <= 0 || criticalStockThreshold < 0) {
                e.preventDefault();
                alert('Stock thresholds must be positive values.');
                return false;
            }
            
            return true;
        });
        
        // User dropdown menu functionality
        const userProfile = document.querySelector('.user-profile');
        userProfile.addEventListener('click', function() {
            // This would typically show a dropdown menu
            // For simplicity, we're just logging this action
            console.log('User profile clicked');
            
            // In a real implementation, you would toggle a dropdown menu here
            // const dropdown = document.createElement('div');
            // dropdown.classList.add('user-dropdown');
            // dropdown.innerHTML = '<a href="profile.php">My Profile</a><a href="logout.php">Logout</a>';
            // this.appendChild(dropdown);
        });
        
        // Notification bell functionality
        const notificationBell = document.querySelector('.notification-bell');
        notificationBell.addEventListener('click', function() {
            // This would typically show notifications
            console.log('Notification bell clicked');
            
            // In a real implementation, you would show notifications
            // const notifications = document.createElement('div');
            // notifications.classList.add('notifications-dropdown');
            // notifications.innerHTML = '<div class="notification-item">New order received</div>';
            // this.appendChild(notifications);
        });
        
        // Initialize tooltips or other UI components if needed
        function initializeComponents() {
            // This function could be used to initialize any UI components
            console.log('Settings page initialized');
        }
        
        // Call initialization on page load
        window.addEventListener('DOMContentLoaded', initializeComponents);
    </script>
</body>
</html> 