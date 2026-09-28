<?php
require_once __DIR__ . '/../includes/db.php';

// Handle mark as done
if (isset($_GET['mark_done']) && is_numeric($_GET['mark_done'])) {
    $id = $_GET['mark_done'];
    $stmt = $db->prepare("UPDATE orders SET status = 'done' WHERE id = ?");
    $stmt->execute([$id]);
    header("Location: " . getBaseUrl() . "/admin/");
    exit;
}

$view = $_GET['view'] ?? 'ongoing';
$status_filter = ($view === 'history') ? 'done' : 'ongoing';

// Get orders, sort by the earliest deadline among their items if ongoing, else by created_at DESC
if ($status_filter === 'ongoing') {
    $query = "
        SELECT o.*, 
        (SELECT MIN(deadline) FROM order_items WHERE order_id = o.id) as earliest_deadline
        FROM orders o 
        WHERE o.status = 'ongoing' 
        ORDER BY earliest_deadline ASC, o.created_at ASC
    ";
} else {
    $query = "
        SELECT * FROM orders 
        WHERE status = 'done' 
        ORDER BY created_at DESC
    ";
}

$stmt = $db->query($query);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Amez Dashboard | SPORT STUFF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
    <header>
        <div class="header-content">
            <a href="<?= getBaseUrl() ?>/admin/" class="logo-container">
                <img src="<?= getBaseUrl() ?>/assets/uploads/logo.jpg" alt="SPORT STUFF" class="logo-img" onerror="this.style.display='none'">
                Amez Dashboard
            </a>
            <div class="nav-links">
                <a href="?view=ongoing" class="<?= $view === 'ongoing' ? 'active' : '' ?>">Ongoing</a>
                <a href="?view=history" class="<?= $view === 'history' ? 'active' : '' ?>">History</a>
                <a href="<?= getBaseUrl() ?>" target="_blank">View Form</a>
            </div>
        </div>
    </header>

    <main class="dashboard-container">
        <div class="dashboard-header">
            <h2><?= $view === 'ongoing' ? 'Ongoing Orders' : 'Completed Orders' ?></h2>
            <span style="color: var(--text-muted);"><?= count($orders) ?> order(s) found</span>
        </div>

        <?php if (count($orders) === 0): ?>
            <div class="card" style="text-align: center; padding: 3rem 1rem;">
                <p style="color: var(--text-muted); font-size: 1.1rem;">No orders found here.</p>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): 
                // Fetch items for this order
                $stmt_items = $db->prepare("SELECT * FROM order_items WHERE order_id = ? ORDER BY deadline ASC");
                $stmt_items->execute([$order['id']]);
                $items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);
            ?>
                <div class="order-card">
                    <div class="order-header">
                        <div class="order-info">
                            <h3>Order #<?= $order['id'] ?> - <?= htmlspecialchars($order['customer_name']) ?></h3>
                            <div class="order-meta">
                                Date: <?= date('M j, Y g:i A', strtotime($order['created_at'])) ?>
                            </div>
                        </div>
                        <div class="order-actions">
                            <?php 
                            // Format WhatsApp number to start with country code if needed, assuming international format without + or 00 for api link
                            $wa = preg_replace('/[^0-9]/', '', $order['customer_whatsapp']);
                            ?>
                            <a href="https://wa.me/<?= $wa ?>" target="_blank" class="btn btn-sm btn-outline">WhatsApp</a>
                            
                            <?php if ($order['status'] === 'ongoing'): ?>
                                <a href="?mark_done=<?= $order['id'] ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark this order as Done?');">Mark Done</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="order-products">
                        <?php foreach ($items as $item): ?>
                            <div class="dashboard-product">
                                <?php if ($item['image_path']): ?>
                                    <img src="<?= getBaseUrl() ?>/assets/uploads/orders/<?= htmlspecialchars($item['image_path']) ?>" alt="Product" class="product-img">
                                <?php else: ?>
                                    <div class="product-img" style="display:flex; align-items:center; justify-content:center; background:#eee; color:#aaa; font-size:0.8rem;">No Img</div>
                                <?php endif; ?>
                                
                                <div class="product-details">
                                    <h4><?= htmlspecialchars($item['product_name']) ?> <span style="font-weight:normal; color:var(--text-muted);">x<?= $item['quantity'] ?></span></h4>
                                    <div class="product-meta">
                                        <?php if ($item['size']) echo "Size: " . htmlspecialchars($item['size']) . " &bull; "; ?>
                                        Price: ₹<?= number_format($item['price'], 2) ?>
                                    </div>
                                    <?php if ($item['notes']): ?>
                                        <div class="product-notes">
                                            <?= nl2br(htmlspecialchars($item['notes'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="text-align: right;">
                                    <div class="deadline-badge">Due: <?= date('M j, Y', strtotime($item['deadline'])) ?></div>
                                    <div style="margin-top:0.5rem; font-weight:600;">
                                        ₹<?= number_format($item['price'] * $item['quantity'], 2) ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="order-total">
                        <span>Total Amount</span>
                        <span>₹<?= number_format($order['total_amount'], 2) ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>
