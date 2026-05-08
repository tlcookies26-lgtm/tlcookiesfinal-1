<?php
include '../includes/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Allow both full admin (1) and partial admin (2)
    $check_admin = $conn->prepare("SELECT is_admin FROM users WHERE id = ?");
    $check_admin->execute([$user_id]);
    $fetch_admin = $check_admin->fetch(PDO::FETCH_ASSOC);

    if (!$fetch_admin || $fetch_admin['is_admin'] == 0) {
        $_SESSION['unauthorized'] = true;
        header("Location: ../pages/index.php");
        exit();
    }
} else {
    header("Location: login.php");
    exit();
}

if (isset($_POST['logout'])) {
    session_destroy();
    header('location: login.php');
    exit();
}

$success_msg = '';
$warning_msg = '';

// Handle stock update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_stock'])) {
    $product_id = intval($_POST['product_id']);
    $action     = $_POST['action'];         // 'set' or 'add'
    $amount     = intval($_POST['amount']);

    if ($amount < 0) {
        $warning_msg = "Amount cannot be negative.";
    } else {
        if ($action === 'set') {
            $stmt = $conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmt->execute([$amount, $product_id]);
            $success_msg = "Stock updated successfully! 🍪";
        } elseif ($action === 'add') {
            $stmt = $conn->prepare("UPDATE products SET stock = stock + ? WHERE id = ?");
            $stmt->execute([$amount, $product_id]);
            $success_msg = "Stock added successfully! 🍪";
        }
    }
}

// Fetch all products with stock
$select_products = $conn->prepare("SELECT id, name, images, price, stock FROM products ORDER BY name ASC");
$select_products->execute();
$products = $select_products->fetchAll(PDO::FETCH_ASSOC);

$LOW_STOCK_THRESHOLD = 10;
?>
<?php $page = 'stock'; ?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Manage Stock</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <style>
        .title2 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
            background: var(--cookie-cream);
            border-radius: 50px;
            margin-bottom: 2rem;
        }

        .title2 .btn {
            background-color: var(--cookie-brown);
            color: #fff;
            padding: 1rem 2rem;
            font-size: 1.4rem;
            border-radius: 30px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .title2 .btn:hover {
            background-color: var(--cookie-chocolate);
        }

        .title2 span {
            color: var(--cookie-chocolate);
            font-size: 1.8rem;
            font-weight: bold;
        }

        .stock-table {
            width: 100%;
            border-collapse: collapse;
            background: #fff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(139,69,19,0.1);
        }

        .stock-table thead {
            background: var(--cookie-brown);
            color: #fff;
        }

        .stock-table th {
            padding: 1.4rem 1.6rem;
            font-size: 1.4rem;
            text-align: left;
            letter-spacing: 0.5px;
        }

        .stock-table td {
            padding: 1.2rem 1.6rem;
            font-size: 1.4rem;
            border-bottom: 1px solid #f0e6d6;
            vertical-align: middle;
        }

        .stock-table tr:last-child td {
            border-bottom: none;
        }

        .stock-table tr:hover td {
            background: #fdf6ee;
        }

        .product-cell {
            display: flex;
            align-items: center;
            gap: 1.2rem;
        }

        .product-cell img {
            width: 52px;
            height: 52px;
            object-fit: cover;
            border-radius: 50%;
            border: 2px solid var(--cookie-tan);
        }

        .stock-badge {
            display: inline-block;
            padding: 0.4rem 1.2rem;
            border-radius: 20px;
            font-size: 1.3rem;
            font-weight: bold;
        }

        .badge-ok      { background: #d4edda; color: #155724; }
        .badge-low     { background: #fff3cd; color: #856404; }
        .badge-empty   { background: #f8d7da; color: #721c24; }

        .stock-form {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }

        .stock-form select {
            padding: 0.6rem 1rem;
            border: 2px solid var(--cookie-tan);
            border-radius: 10px;
            font-size: 1.3rem;
            background: #fff;
            cursor: pointer;
        }

        .stock-form input[type="number"] {
            width: 90px;
            padding: 0.6rem 1rem;
            border: 2px solid var(--cookie-tan);
            border-radius: 10px;
            font-size: 1.3rem;
        }

        .stock-form button {
            padding: 0.6rem 1.4rem;
            background: var(--cookie-brown);
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 1.3rem;
            cursor: pointer;
            transition: background 0.2s;
        }

        .stock-form button:hover {
            background: var(--cookie-chocolate);
        }

        .summary-bar {
            display: flex;
            gap: 1.5rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }

        .summary-card {
            flex: 1;
            min-width: 140px;
            background: #fff;
            border-radius: 14px;
            padding: 1.4rem 2rem;
            box-shadow: 0 2px 10px rgba(139,69,19,0.08);
            text-align: center;
            border-top: 4px solid var(--cookie-brown);
        }

        .summary-card.warn { border-top-color: #f6c000; }
        .summary-card.danger { border-top-color: #dc3545; }

        .summary-card .count {
            font-size: 3rem;
            font-weight: bold;
            color: var(--cookie-chocolate);
        }

        .summary-card .label {
            font-size: 1.2rem;
            color: #888;
            margin-top: 0.3rem;
        }

        @media (max-width: 768px) {
            .stock-table thead { display: none; }
            .stock-table td {
                display: block;
                padding: 0.8rem 1.2rem;
            }
            .stock-table tr {
                display: block;
                margin-bottom: 1.5rem;
                border: 2px solid var(--cookie-tan);
                border-radius: 12px;
                overflow: hidden;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/aHeader.php'; ?>

    <div class="main">
        <div class="title2">
            <a href="view_products.php" class="btn"><i class="bx bx-list-ul"></i> View Products</a>
            <span>/ Manage Stock</span>
        </div>

        <?php
        $total   = count($products);
        $low     = count(array_filter($products, fn($p) => $p['stock'] > 0 && $p['stock'] <= $LOW_STOCK_THRESHOLD));
        $empty   = count(array_filter($products, fn($p) => $p['stock'] == 0));
        $instock = $total - $low - $empty;
        ?>

        <div class="summary-bar">
            <div class="summary-card">
                <div class="count"><?= $total ?></div>
                <div class="label">Total Products</div>
            </div>
            <div class="summary-card">
                <div class="count"><?= $instock ?></div>
                <div class="label">In Stock</div>
            </div>
            <div class="summary-card warn">
                <div class="count"><?= $low ?></div>
                <div class="label">Low Stock (≤<?= $LOW_STOCK_THRESHOLD ?>)</div>
            </div>
            <div class="summary-card danger">
                <div class="count"><?= $empty ?></div>
                <div class="label">Out of Stock</div>
            </div>
        </div>

        <table class="stock-table">
            <thead>
                <tr>
                    <th>Cookie</th>
                    <th>Price</th>
                    <th>Current Stock</th>
                    <th>Status</th>
                    <th>Update Stock</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $p): ?>
                <?php
                    $stock = $p['stock'];
                    if ($stock == 0) {
                        $badgeClass = 'badge-empty';
                        $badgeText  = 'Out of Stock';
                    } elseif ($stock <= $LOW_STOCK_THRESHOLD) {
                        $badgeClass = 'badge-low';
                        $badgeText  = 'Low Stock';
                    } else {
                        $badgeClass = 'badge-ok';
                        $badgeText  = 'In Stock';
                    }
                ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <img src="../admin/<?= htmlspecialchars($p['images']) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                            <strong><?= htmlspecialchars($p['name']) ?></strong>
                        </div>
                    </td>
                    <td>₱<?= number_format($p['price'], 2) ?></td>
                    <td><strong><?= $stock ?></strong> pcs</td>
                    <td><span class="stock-badge <?= $badgeClass ?>"><?= $badgeText ?></span></td>
                    <td>
                        <form class="stock-form" method="POST">
                            <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                            <select name="action">
                                <option value="add">Add</option>
                                <option value="set">Set to</option>
                            </select>
                            <input type="number" name="amount" min="0" value="0" required>
                            <button type="submit" name="update_stock"><i class="bx bx-save"></i> Save</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($products)): ?>
                <tr>
                    <td colspan="5" style="text-align:center; padding: 3rem; color: #aaa;">
                        No products found. <a href="shop.php" style="color: var(--cookie-brown);">Add some cookies first!</a>
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../assets/js/script.js"></script>
    <?php include '../includes/alert.php'; ?>

    <script>
    <?php if ($success_msg): ?>
        Swal.fire({ icon: 'success', title: 'Updated!', text: <?= json_encode($success_msg) ?>, timer: 2000, showConfirmButton: false });
    <?php elseif ($warning_msg): ?>
        Swal.fire({ icon: 'warning', title: 'Warning', text: <?= json_encode($warning_msg) ?> });
    <?php endif; ?>
    </script>
</body>
</html>
