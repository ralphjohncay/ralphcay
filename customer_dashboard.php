<?php
session_start();
require_once "includes/db.php";

// Check if user is logged in and is a customer
if (!isset($_SESSION['user']) || $_SESSION['user']['RoleID'] != 3) {
    header("Location: login.php");
    exit();
}

// Get user information
$user = $_SESSION['user'];
$username = $user['Username'];
$userID = $user['UserID'];

// Handle product search and filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Get all categories for filter dropdown
$categoryQuery = "SELECT DISTINCT Category FROM products ORDER BY Category";
$categoryStmt = $pdo->query($categoryQuery);
$categories = $categoryStmt->fetchAll(PDO::FETCH_COLUMN);

// Build product query with filters
$productQuery = "SELECT p.ProductID, p.Name, p.Category, p.Price, 
                COALESCE(SUM(s.QuantityAdded), 0) - COALESCE((SELECT SUM(QuantitySold) FROM sales WHERE ProductID = p.ProductID), 0) as CurrentStock
                FROM products p
                LEFT JOIN stock s ON p.ProductID = s.ProductID";

$whereConditions = [];
$params = [];

if (!empty($search)) {
    $whereConditions[] = "p.Name LIKE :search";
    $params['search'] = "%$search%";
}

if (!empty($category)) {
    $whereConditions[] = "p.Category = :category";
    $params['category'] = $category;
}

if (!empty($whereConditions)) {
    $productQuery .= " WHERE " . implode(" AND ", $whereConditions);
}

$productQuery .= " GROUP BY p.ProductID HAVING CurrentStock > 0 ORDER BY p.Name";

$productStmt = $pdo->prepare($productQuery);
$productStmt->execute($params);
$products = $productStmt->fetchAll();

// Handle product purchase
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['purchase'])) {
    $productID = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    
    // Check if product exists and has enough stock
    $stockCheckQuery = "SELECT p.ProductID, p.Name, p.Price, 
                        COALESCE(SUM(s.QuantityAdded), 0) - COALESCE((SELECT SUM(QuantitySold) FROM sales WHERE ProductID = p.ProductID), 0) as CurrentStock
                        FROM products p
                        LEFT JOIN stock s ON p.ProductID = s.ProductID
                        WHERE p.ProductID = :productID
                        GROUP BY p.ProductID";
    
    $stockStmt = $pdo->prepare($stockCheckQuery);
    $stockStmt->execute(['productID' => $productID]);
    $product = $stockStmt->fetch();
    
    if ($product && $product['CurrentStock'] >= $quantity && $quantity > 0) {
        // Calculate total amount
        $totalAmount = $product['Price'] * $quantity;
        
        // Insert sale record
        $saleQuery = "INSERT INTO sales (ProductID, QuantitySold, SaleDate, TotalAmount) 
                      VALUES (:productID, :quantity, NOW(), :totalAmount)";
        
        $saleStmt = $pdo->prepare($saleQuery);
        $saleResult = $saleStmt->execute([
            'productID' => $productID,
            'quantity' => $quantity,
            'totalAmount' => $totalAmount
        ]);
        
        if ($saleResult) {
            $purchaseSuccess = "Successfully purchased {$quantity} {$product['Name']} for $" . number_format($totalAmount, 2);
        } else {
            $purchaseError = "Error processing your purchase. Please try again.";
        }
    } else {
        $purchaseError = "Invalid product or insufficient stock.";
    }
    
    // Refresh product list after purchase
    $productStmt->execute($params);
    $products = $productStmt->fetchAll();
}

// Get purchase history for this user
$historyQuery = "SELECT s.SaleID, p.Name as ProductName, s.QuantitySold, s.SaleDate, s.TotalAmount  
                FROM sales s
                JOIN products p ON s.ProductID = p.ProductID
                ORDER BY s.SaleDate DESC
                LIMIT 5";
                
$historyStmt = $pdo->query($historyQuery);
$recentSales = $historyStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Inventory System</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
     :root {
        --primary-color: #3A3F47;
    --primary-hover: #2E3339;
    --text-color: #2A2F36;
    --light-text: #6E7278;
    --border-color: #D1D5DB;
    --background: #F3F4F6;
    --card-bg: #FFFFFF;
    --error-bg: #FEE2E2;
    --error-text: #B91C1C;
    --success-bg: #DCFCE7;
    --success-text: #15803D;
    --shadow: 0 10px 15px -3px rgba(75, 85, 99, 0.1), 0 4px 6px -2px rgba(75, 85, 99, 0.05);
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Nunito', sans-serif;
}

body {
    background-color: var(--background);
    color: var(--text-color);
    min-height: 100vh;
}

.container {
    width: 100%;
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

/* Header styling */
header {
    background: linear-gradient(135deg, #ffb6c1, #ff9dbb);
    color: white;
    padding: 1rem 0;
    box-shadow: 0 4px 10px rgba(255, 157, 187, 0.3);
    margin-bottom: 2rem;
}

.header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0 1.5rem;
}

.brand {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.5rem;
    font-weight: 700;
    text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.1);
}

.user-info {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background-color: rgba(255, 255, 255, 0.3);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.user-details {
    display: flex;
    flex-direction: column;
}

.user-name {
    font-weight: 600;
}

.user-role {
    font-size: 0.8rem;
    opacity: 0.9;
}

.header-buttons {
    display: flex;
    gap: 1rem;
}

.btn-header {
    background-color: rgba(255, 255, 255, 0.2);
    color: white;
    border: none;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.2s;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-header:hover {
    background-color: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

/* Card styling */
.card {
    background-color: var(--card-bg);
    border-radius: 1rem;
    box-shadow: 0 5px 15px rgba(255, 157, 187, 0.15);
    overflow: hidden;
    margin-bottom: 1.5rem;
    border: 1px solid var(--border-color);
}

.card-header {
    padding: 1.25rem;
    background-color: rgba(255, 182, 193, 0.1);
    border-bottom: 1px solid var(--border-color);
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.card-title {
    font-size: 1.25rem;
    font-weight: 700;
    color: var(--primary-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.card-body {
    padding: 1.5rem;
}

/* Product grid */
.filter-container {
    display: flex;
    gap: 1rem;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
}

.search-container {
    flex: 1;
    min-width: 300px;
    position: relative;
}

.search-input {
    width: 100%;
    padding: 0.75rem 1rem 0.75rem 2.5rem;
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    font-size: 0.95rem;
    box-shadow: 0 2px 5px rgba(255, 157, 187, 0.1);
    transition: box-shadow 0.3s;
}

.search-input:focus {
    outline: none;
    box-shadow: 0 2px 8px rgba(255, 157, 187, 0.2);
    border-color: var(--primary-color);
}

.search-icon {
    position: absolute;
    left: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--light-text);
}

.filter-select {
    padding: 0.75rem 1rem;
    border: 1px solid var(--border-color);
    border-radius: 0.75rem;
    font-size: 0.95rem;
    min-width: 200px;
    box-shadow: 0 2px 5px rgba(255, 157, 187, 0.1);
    color: var(--text-color);
}

.filter-select:focus {
    outline: none;
    box-shadow: 0 2px 8px rgba(255, 157, 187, 0.2);
    border-color: var(--primary-color);
}

.filter-button {
    background-color: var(--primary-color);
    color: white;
    border: none;
    padding: 0.75rem 1.5rem;
    border-radius: 0.75rem;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.2s;
    font-weight: 600;
    box-shadow: 0 2px 5px rgba(255, 157, 187, 0.2);
}

.filter-button:hover {
    background-color: var(--primary-hover);
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(255, 157, 187, 0.3);
}

.product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 1.5rem;
}

.product-card {
    background-color: var(--card-bg);
    border-radius: 1rem;
    box-shadow: 0 5px 15px rgba(255, 157, 187, 0.1);
    overflow: hidden;
    border: 1px solid var(--border-color);
    transition: transform 0.3s, box-shadow 0.3s;
}

.product-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 20px rgba(255, 157, 187, 0.2);
}

.product-image {
    height: 150px;
    background-color: #fff5f8;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--primary-color);
    border-bottom: 1px solid var(--border-color);
}

.product-details {
    padding: 1.25rem;
}

.product-name {
    font-weight: 700;
    margin-bottom: 0.25rem;
    color: var(--text-color);
    font-size: 1.1rem;
}

.product-category {
    color: var(--light-text);
    font-size: 0.85rem;
    margin-bottom: 0.5rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.product-price {
    font-weight: 700;
    color: var(--primary-color);
    font-size: 1.25rem;
    margin-bottom: 0.5rem;
}

.product-stock {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    font-size: 0.9rem;
}

.stock-badge {
    background-color: var(--primary-color);
    color: white;
    padding: 0.25rem 0.5rem;
    border-radius: 0.5rem;
    font-weight: 600;
    box-shadow: 0 2px 4px rgba(255, 157, 187, 0.2);
}

.product-actions {
    margin-top: 1rem;
}

.purchase-form {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.quantity-input {
    padding: 0.5rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    text-align: center;
    font-weight: 600;
    color: var(--text-color);
}

.quantity-input:focus {
    outline: none;
    border-color: var(--primary-color);
}

.purchase-button {
    background-color: var(--success-color);
    color: #2d6a5e;
    border: none;
    padding: 0.6rem;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: background-color 0.2s, transform 0.2s;
    font-weight: 600;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    box-shadow: 0 2px 5px rgba(159, 216, 203, 0.3);
}

.purchase-button:hover {
    background-color: #8eccbe;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(159, 216, 203, 0.4);
}

/* Alert styles */
.alert {
    padding: 1rem;
    border-radius: 0.75rem;
    margin-bottom: 1.5rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.5rem;
    box-shadow: 0 3px 10px rgba(0, 0, 0, 0.05);
}

.alert-success {
    background-color: rgba(159, 216, 203, 0.1);
    border: 1px solid rgba(159, 216, 203, 0.3);
    color: #2d6a5e;
}

.alert-error {
    background-color: rgba(255, 138, 138, 0.1);
    border: 1px solid rgba(255, 138, 138, 0.3);
    color: #a93232;
}

/* Table styles */
table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    border-radius: 0.75rem;
    overflow: hidden;
    box-shadow: 0 3px 10px rgba(255, 157, 187, 0.1);
}

th, td {
    padding: 0.85rem 1rem;
    text-align: left;
    border-bottom: 1px solid var(--border-color);
}

th {
    background-color: rgba(255, 182, 193, 0.1);
    font-weight: 600;
    color: var(--primary-color);
}

tr:last-child td {
    border-bottom: none;
}

tr:hover {
    background-color: rgba(255, 182, 193, 0.05);
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-weight: 600;
    font-size: 0.75rem;
    text-align: center;
    display: inline-block;
}

.badge-success {
    background-color: rgba(159, 216, 203, 0.1);
    color: #2d6a5e;
}

.badge-pending {
    background-color: rgba(255, 193, 110, 0.1);
    color: #b37423;
}

/* Pagination */
.pagination {
    display: flex;
    justify-content: center;
    gap: 0.5rem;
    margin-top: 1.5rem;
}

.page-link {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--border-color);
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    color: var(--text-color);
}

.page-link:hover {
    background-color: rgba(255, 182, 193, 0.1);
    border-color: var(--primary-color);
}

.page-link.active {
    background-color: var(--primary-color);
    color: white;
    border-color: var(--primary-color);
}

/* Responsive styles */
@media (max-width: 768px) {
    .header-content {
        flex-direction: column;
        gap: 1rem;
        padding: 1rem 0;
    }
    
    .user-info {
        margin-bottom: 0.5rem;
    }
    
    .product-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    }
    
    .filter-container {
        flex-direction: column;
    }
    
    .search-container {
        width: 100%;
    }
    
    .filter-select, .filter-button {
        width: 100%;
    }
}

/* Extra animations and enhancements */
@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

.brand i {
    color: white;
    animation: pulse 2s infinite;
}

.product-card:active {
    transform: scale(0.98);
}

.quantity-input::-webkit-inner-spin-button, 
.quantity-input::-webkit-outer-spin-button { 
    opacity: 1;
}
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="brand">
                    <i class="fas fa-box-open"></i>
                    <span>Shoppingers</span>
                </div>
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?= htmlspecialchars($username) ?></span>
                        <span class="user-role">Customer</span>
                    </div>
                </div>
                <div class="header-buttons">
                    <a href="logout.php" class="btn-header">
                        <i class="fas fa-sign-out-alt"></i>
                        Log Out
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-store"></i>
                    Available Products
                </h2>
            </div>
            <div class="card-body">
                <?php if (isset($purchaseSuccess)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?= $purchaseSuccess ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($purchaseError)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?= $purchaseError ?>
                    </div>
                <?php endif; ?>
                
                <form method="get" action="" class="filter-container">
                    <div class="search-container">
                        <i class="fas fa-search search-icon"></i>
                        <input type="text" name="search" class="search-input" placeholder="Search products..." value="<?= htmlspecialchars($search) ?>">
                    </div>
                    
                    <select name="category" class="filter-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <button type="submit" class="filter-button">
                        <i class="fas fa-filter"></i>
                        Filter
                    </button>
                </form>
                
                <div class="product-grid">
                    <?php if (count($products) > 0): ?>
                        <?php foreach ($products as $product): ?>
                            <div class="product-card">
                                <div class="product-image">
                                    <?php
                                    // Display different icons based on category
                                    $category = strtolower($product['Category']);
                                    $icon = 'box';
                                    
                                    if (strpos($category, 'electronics') !== false) {
                                        $icon = 'laptop';
                                    } elseif (strpos($category, 'clothing') !== false) {
                                        $icon = 'tshirt';
                                    } elseif (strpos($category, 'food') !== false) {
                                        $icon = 'utensils';
                                    } elseif (strpos($category, 'book') !== false) {
                                        $icon = 'book';
                                    } elseif (strpos($category, 'furniture') !== false) {
                                        $icon = 'chair';
                                    }
                                    ?>
                                    <i class="fas fa-<?= $icon ?>"></i>
                                </div>
                                <div class="product-details">
                                    <h3 class="product-name"><?= htmlspecialchars($product['Name']) ?></h3>
                                    <div class="product-category">
                                        <i class="fas fa-tag"></i>
                                        <?= htmlspecialchars($product['Category']) ?>
                                    </div>
                                    <div class="product-price">
                                    ₱<?= number_format($product['Price'], 2) ?>
                                    </div>
                                    <div class="product-stock">
                                        <span>Available:</span>
                                        <span class="stock-badge"><?= number_format($product['CurrentStock']) ?></span>
                                    </div>
                                    <div class="product-actions">
                                        <form method="post" class="purchase-form">
                                            <input type="hidden" name="product_id" value="<?= $product['ProductID'] ?>">
                                            <input type="number" name="quantity" class="quantity-input" min="1" max="<?= $product['CurrentStock'] ?>" value="1" required>
                                            <button type="submit" name="purchase" class="purchase-button">
                                                <i class="fas fa-shopping-cart"></i>
                                                Purchase
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="alert alert-error" style="grid-column: 1 / -1;">
                            <i class="fas fa-exclamation-circle"></i>
                            No products available matching your criteria.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title">
                    <i class="fas fa-history"></i>
                    Recent Purchase History
                </h2>
            </div>
            <div class="card-body">
                <table>
                    <thead>
                        <tr>
                            <th>Sale ID</th>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Date</th>
                            <th>Total Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($recentSales) > 0): ?>
                            <?php foreach ($recentSales as $sale): ?>
                                <tr>
                                    <td><?= $sale['SaleID'] ?></td>
                                    <td><?= htmlspecialchars($sale['ProductName']) ?></td>
                                    <td><?= number_format($sale['QuantitySold']) ?></td>
                                    <td><?= date('M d, Y H:i', strtotime($sale['SaleDate'])) ?></td>
                                    <td>₱<?= number_format($sale['TotalAmount'], 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" style="text-align: center;">No purchase history available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Enable quantity validation
        document.querySelectorAll('.quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const max = parseInt(this.getAttribute('max'));
                const value = parseInt(this.value);
                
                if (value > max) {
                    this.value = max;
                }
                
                if (value < 1) {
                    this.value = 1;
                }
            });
        });
    </script>
</body>
</html>