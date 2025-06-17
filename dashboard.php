<?php
    require_once "includes/auth.php";
    require_once "includes/db.php"; // Added database connection
    $user = $_SESSION['user'];
    $productsStock = $pdo->query("
    SELECT 
        p.Name, 
        p.ProductID, 
        p.Category, 
        p.Price,
        COALESCE(SUM(s.QuantityAdded), 0) - COALESCE((SELECT SUM(QuantitySold) FROM sales WHERE ProductID = p.ProductID), 0) AS CurrentStock
    FROM 
        products p
    LEFT JOIN 
        stock s ON s.ProductID = p.ProductID
    GROUP BY 
        p.ProductID, p.Name, p.Category, p.Price
    ORDER BY 
        CurrentStock ASC
    LIMIT 6
    ")->fetchAll();
    // Fetch system settings
$settingsStmt = $pdo->query("SELECT * FROM system_settings LIMIT 1");
$settings = $settingsStmt->fetch(PDO::FETCH_ASSOC);

// Use the settings values or fallback to defaults if not found
$criticalStockThreshold = isset($settings['critical_stock_threshold']) ? intval($settings['critical_stock_threshold']) : 3;
$lowStockThreshold = isset($settings['low_stock_threshold']) ? intval($settings['low_stock_threshold']) : 5;

    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Dashboard - Inventory System</title>
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <style>
/* General Styles */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Roboto', sans-serif;
  }
  
  body {
    background-color: #f4f7fa;
    color: #333;
  }
  
  .dashboard {
    display: flex;
    min-height: 100vh;
  }
  
  /* Sidebar */
  .sidebar {
    width: 250px;
    background-color: #2c3e50;
    color: #fff;
    padding: 20px;
  }
  
  .sidebar .logo {
    font-size: 24px;
    font-weight: 700;
    margin-bottom: 30px;
  }
  
  .sidebar ul {
    list-style: none;
  }
  
  .sidebar ul li {
    margin: 15px 0;
  }
  
  .sidebar ul li a {
    color: #fff;
    text-decoration: none;
    display: flex;
    align-items: center;
  }
  
  .sidebar ul li a i {
    margin-right: 10px;
  }
  
  .sidebar ul li.active a {
    color: #1abc9c;
  }
  
  /* Main Content */
  .main-content {
    flex: 1;
    background-color: #f4f7fa;
  }
  
  /* Header */
  .header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .header-left h1 {
    font-size: 24px;
    font-weight: 700;
  }
  
  .header-right {
    display: flex;
    align-items: center;
  }
  
  .search-bar {
    position: relative;
    margin-right: 20px;
  }
  
  .search-bar input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 200px;
  }
  
  .search-bar i {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    color: #888;
  }
  
  .notifications {
    position: relative;
    margin-right: 20px;
    cursor: pointer;
  }
  
  .notifications .badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background-color: #e74c3c;
    color: #fff;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 12px;
  }
  
  .user-profile {
    position: relative;
    cursor: pointer;
  }
  
  .user-profile img {
    border-radius: 50%;
  }
  
  .user-profile .dropdown {
    display: none;
    position: absolute;
    right: 0;
    top: 40px;
    background-color: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    border-radius: 4px;
    padding: 10px;
    z-index: 1;
  }
  
  .user-profile .dropdown a {
    display: block;
    padding: 5px 0;
    color: #333;
    text-decoration: none;
  }
  
  .user-profile:hover .dropdown {
    display: block;
  }
  
  /* Content */
  .content {
    padding: 20px;
  }
  
  .card {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
  }
  
  .card h2 {
    margin-bottom: 20px;
  }
  
  .btn {
    padding: 8px 16px;
    background-color: #1abc9c;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }
  
  .btn:hover {
    background-color: #16a085;
  }
  
  table {
    width: 100%;
    border-collapse: collapse;
  }
  
  table th, table td {
    padding: 12px;
    text-align: left;
    border-bottom: 1px solid #ddd;
  }
  
  table th {
    background-color: #f8f9fa;
  }
  
  table tr:hover {
    background-color: #f1f1f1;
  }
  
  /* Modal */
  .modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    justify-content: center;
    align-items: center;
  }
  
  .modal-content {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    width: 400px;
    position: relative;
  }
  
  .modal .close {
    position: absolute;
    top: 10px;
    right: 10px;
    cursor: pointer;
    font-size: 20px;
  }
  
  .modal h2 {
    margin-bottom: 20px;
  }
  
  .modal form label {
    display: block;
    margin-bottom: 5px;
  }
  
  .modal form input {
    width: 100%;
    padding: 8px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
  }
  
  .modal form button {
    width: 100%;
    padding: 10px;
    background-color: #1abc9c;
    color: #fff;
    border: none;
    border-radius: 4px;
    cursor: pointer;
  }
  
  .modal form button:hover {
    background-color: #16a085;
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
                    <a href="dashboard.php" class="nav-item active">
                        <i class="nav-icon fas fa-home"></i> Dashboard
                    </a>
                </div>
                
                <?php if ($user['RoleName'] == 'Admin'): ?>
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
                    <a href="settings.php" class="nav-item">
                        <i class="nav-icon fas fa-cog"></i> Settings
                    </a>
                </div>
                <?php endif; ?>
                
                <?php if ($user['RoleName'] == 'Staff'): ?>
                <div class="nav-section">
                    <div class="nav-section-title">Operations</div>
                    <a href="sales/index.php" class="nav-item">
                        <i class="nav-icon fas fa-shopping-cart"></i> Sales
                    </a>
                    <a href="stock/index.php" class="nav-item">
                        <i class="nav-icon fas fa-warehouse"></i> Stock Management
                    </a>
                    <a href="reports/dashboard.php" class="nav-item">
                        <i class="nav-icon fas fa-chart-line"></i> Reports & Analytics
                    </a>
                </div>
                <?php endif; ?>
            </nav>
            
            <div class="sidebar-footer">
                <a href="#" class="logout-btn" id="logoutButton">
                    <i class="logout-icon fas fa-sign-out-alt"></i> Logout
                </a>
            </div>

            <!-- Loading overlay that will appear when logging out -->
            <div class="loading-overlay" id="loadingOverlay">
                <div class="glass-circle glass-circle-1"></div>
                <div class="glass-circle glass-circle-2"></div>
                <div class="loading-spinner"></div>
                <div class="loading-text">Logging out...</div>
                <div class="loading-progress">
                    <div class="loading-progress-bar" id="progressBar"></div>
                </div>
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
                    <h1 class="page-title">Dashboard</h1>
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
            <section class="welcome-section">
    <h2 class="welcome-title">Welcome back, <?= htmlspecialchars($user['Username']) ?>!</h2>
    <p class="welcome-subtitle">Here's what's happening with your inventory today.</p>
    
    <!-- Product Stock Section - displays all products regardless of stock level -->
    <?php if (!empty($productsStock)): ?>
<div class="stock-overview">    
<h3 class="stock-overview-title">
    <i class="fas fa-boxes"></i> Current Inventory Stocks Status 
    <span style="font-size: 0.8rem; margin-left: 10px; color: var(--text-light);">
        (Critical: ≤<?= $criticalStockThreshold ?>, Low: ≤<?= $lowStockThreshold ?>)
    </span>
</h3>
    <div class="stock-grid">
    <?php foreach ($productsStock as $index => $product): 
    $stockLevel = intval($product['CurrentStock']);
    
    // Determine the stock status using system settings
    if ($stockLevel <= $criticalStockThreshold) {
        $stockStatus = 'critical';
        $stockIcon = 'fa-exclamation-circle';
        $stockColor = '#ef4444';
    } elseif ($stockLevel <= $lowStockThreshold) {
        $stockStatus = 'warning';
        $stockIcon = 'fa-exclamation-triangle';
        $stockColor = '#f59e0b';
    } else {    
        $stockStatus = 'normal';
        $stockIcon = 'fa-check-circle';
        $stockColor = '#10b981';
    }
    
    // Make the first item larger
    $isFirst = ($index === 0);
?>
        <div class="stock-item <?= $stockStatus ?> <?= $isFirst ? 'featured' : '' ?>">
            <div class="stock-level-indicator" style="background-color: <?= $stockColor ?>;">
                <i class="fas <?= $stockIcon ?>"></i>
                <span><?= $stockLevel ?></span>
            </div>
            
            <div class="stock-details">
                <h4 class="stock-name"><?= htmlspecialchars($product['Name']) ?></h4>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="stock-overview-footer">
        <a href="products/index.php" class="view-all-btn">
            <i class="fas fa-list"></i> View All Products
        </a>
    </div>
</div>
<?php else: ?>
<div class="no-products-alert">
    <i class="fas fa-info-circle"></i>
    <p>No products found in the inventory system. <a href="products/add.php">Add your first product</a>.</p>
</div>
<?php endif; ?>
</section>

                
                <div class="dashboard-cards">
                    <?php if ($user['RoleName'] == 'Admin'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Products</h3>
                            <div class="card-icon card-icon-products">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                        <div class="card-content">
                            <p class="card-description">Manage your product catalog, add new items, update prices and more.</p>
                            <a href="products/index.php" class="card-action">Manage Products</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Suppliers</h3>
                            <div class="card-icon card-icon-suppliers">
                                <i class="fas fa-truck"></i>
                            </div>
                        </div>
                        <div class="card-content">
                            <p class="card-description">View and manage your suppliers, track deliveries and orders.</p>
                            <a href="suppliers/index.php" class="card-action">Manage Suppliers</a>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Stock Management</h3>
                            <div class="card-icon card-icon-stock">
                                <i class="fas fa-warehouse"></i>
                            </div>
                        </div>
                        <div class="card-content">
                            <p class="card-description">Monitor inventory levels, process stock adjustments and transfers.</p>
                            <a href="stock/index.php" class="card-action">Manage Stock</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Sales</h3>
                            <div class="card-icon card-icon-sales">
                                <i class="fas fa-shopping-cart"></i>
                            </div>
                        </div>
                        <div class="card-content">
                            <p class="card-description">Process sales transactions, view sales history and customer data.</p>
                            <a href="sales/index.php" class="card-action">View Sales</a>
                        </div>
                    </div>
                    
                    <?php if ($user['RoleName'] == 'Admin'): ?>
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Reports & Analytics</h3>
                            <div class="card-icon card-icon-products">
                                <i class="fas fa-chart-line"></i>
                            </div>
                        </div>
                        <div class="card-content">
                            <p class="card-description">Generate detailed reports on sales, inventory, and overall performance.</p>
                            <a href="reports/dashboard.php" class="card-action">View Reports</a>
                        </div>
                    </div>
                    
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">User Management</h3>
                            <div class="card-icon card-icon-suppliers">
                                <i class="fas fa-users-cog"></i>
                        </div>
                    </div>
                    <div class="card-content">
                        <p class="card-description">Manage system users, set permissions and user roles.</p>
                        <a href="user_management.php" class="card-action">Manage Users</a>
                    </div>
                </div>
                <?php endif; ?>
                
                <?php if ($user['RoleName'] == 'Staff'): ?>
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Reports</h3>
                        <div class="card-icon card-icon-products">
                            <i class="fas fa-chart-line"></i>
                        </div>
                    </div>
                    <div class="card-content">
                        <p class="card-description">View detailed reports on sales and inventory status.</p>
                        <a href="reports/dashboard.php" class="card-action">View Reports</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
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
            
            // Handle window resize to fix sidebar state on screen size change
            window.addEventListener('resize', function() {
                const sidebar = document.getElementById('sidebar');
                
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('open');
                }
            });
            
            // Script for logout button with loading animation
            document.getElementById('logoutButton').addEventListener('click', function(e) {
                e.preventDefault();
                
                // Show loading overlay
                const loadingOverlay = document.getElementById('loadingOverlay');
                loadingOverlay.classList.add('active');
                
                // Animate progress bar
                const progressBar = document.getElementById('progressBar');
                progressBar.style.width = '100%';
                
                // Wait for 2 seconds and then redirect
                setTimeout(function() {
                    window.location.href = 'logout.php';
                }, 2000);
            });
        </script>
    </body>
    </html>