<?php
require_once __DIR__ . '/includes/db.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_whatsapp = trim($_POST['customer_whatsapp'] ?? '');
    
    if (empty($customer_name) || empty($customer_whatsapp)) {
        $error = "Name and WhatsApp number are required.";
    } else {
        $products = $_POST['products'] ?? [];
        $total_amount = 0;
        
        foreach ($products as $index => $prod) {
            $total_amount += (float)($prod['price'] ?? 0) * (int)($prod['quantity'] ?? 1);
        }
        
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("INSERT INTO orders (customer_name, customer_whatsapp, total_amount) VALUES (?, ?, ?)");
            $stmt->execute([$customer_name, $customer_whatsapp, $total_amount]);
            $order_id = $db->lastInsertId();
            
            $stmt_item = $db->prepare("INSERT INTO order_items (order_id, image_path, product_name, size, quantity, price, deadline, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            
            $upload_dir = __DIR__ . '/assets/uploads/orders/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            foreach ($products as $index => $prod) {
                $image_path = '';
                if (isset($_FILES['products']['name'][$index]['image']) && $_FILES['products']['error'][$index]['image'] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['products']['tmp_name'][$index]['image'];
                    $ext = pathinfo($_FILES['products']['name'][$index]['image'], PATHINFO_EXTENSION);
                    $filename = uniqid('prod_') . '.' . $ext;
                    if (move_uploaded_file($tmp_name, $upload_dir . $filename)) {
                        $image_path = $filename;
                    }
                }
                
                $stmt_item->execute([
                    $order_id,
                    $image_path,
                    trim($prod['name'] ?? ''),
                    trim($prod['size'] ?? ''),
                    (int)($prod['quantity'] ?? 1),
                    (float)($prod['price'] ?? 0),
                    trim($prod['deadline'] ?? ''),
                    trim($prod['notes'] ?? '')
                ]);
            }
            
            $db->commit();
            $success = true;
        } catch (Exception $e) {
            $db->rollBack();
            $error = "Failed to submit order: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Order Form | SPORT STUFF</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= getBaseUrl() ?>/assets/css/style.css?v=<?= time() ?>">
</head>
<body>
    <header>
        <div class="header-content">
            <a href="<?= getBaseUrl() ?>" class="logo-container">
                <img src="<?= getBaseUrl() ?>/assets/uploads/logo.jpg" alt="SPORT STUFF" class="logo-img" onerror="this.style.display='none'">
                SPORT STUFF
            </a>
        </div>
    </header>

    <main class="container">
        <?php if ($success): ?>
            <div class="alert alert-success">
                <h3>Order Confirmed!</h3>
                <p>Your order details have been successfully recorded. Amez will be in touch with you via WhatsApp.</p>
                <a href="<?= getBaseUrl() ?>" class="btn btn-primary" style="margin-top: 1rem;">Submit Another Order</a>
            </div>
        <?php else: ?>
            
            <h1 class="page-title">Order Form</h1>
            <p class="page-subtitle">Please fill out your confirmed order details below.</p>

            <?php if ($error): ?>
                <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" id="orderForm">
                <div class="card">
                    <h3 class="section-title">Your Details</h3>
                    <div class="form-group">
                        <label>Full Name *</label>
                        <input type="text" name="customer_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>WhatsApp Number *</label>
                        <input type="text" name="customer_whatsapp" class="form-control" required placeholder="e.g. +1234567890">
                    </div>
                </div>

                <div class="card">
                    <div class="section-title">
                        <h3>Products</h3>
                        <button type="button" class="btn btn-sm btn-outline" id="addProductBtn">+ Add Product</button>
                    </div>
                    
                    <div id="productsContainer">
                        <!-- Product Item Template -->
                        <div class="product-item" data-index="0">
                            <div class="product-item-header">
                                <span>Product #1</span>
                            </div>
                            
                            <div class="form-group">
                                <label>Product Image</label>
                                <input type="file" name="products[0][image]" class="form-control" accept="image/*">
                            </div>
                            
                            <div class="form-group">
                                <label>Product Name/Details *</label>
                                <input type="text" name="products[0][name]" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Size *</label>
                                <div class="size-options">
                                    <label class="size-box"><input type="radio" name="products[0][size]" value="S" required><span>S</span></label>
                                    <label class="size-box"><input type="radio" name="products[0][size]" value="M"><span>M</span></label>
                                    <label class="size-box"><input type="radio" name="products[0][size]" value="L"><span>L</span></label>
                                    <label class="size-box"><input type="radio" name="products[0][size]" value="XL"><span>XL</span></label>
                                    <label class="size-box"><input type="radio" name="products[0][size]" value="2XL"><span>2XL</span></label>
                                    <label class="size-box"><input type="radio" name="products[0][size]" value="3XL"><span>3XL</span></label>
                                    <label class="size-box"><input type="radio" name="products[0][size]" value="4XL"><span>4XL</span></label>
                                </div>
                            </div>
                            
                            <div class="grid-2">
                                <div class="form-group">
                                    <label>Quantity *</label>
                                    <input type="number" name="products[0][quantity]" class="form-control" value="1" min="1" required>
                                </div>
                                <div class="form-group">
                                    <label>Price (per unit) *</label>
                                    <input type="number" step="0.01" name="products[0][price]" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label>Deadline (Given by Amez) *</label>
                                    <input type="date" name="products[0][deadline]" class="form-control" required>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label>Notes / Other Info</label>
                                <textarea name="products[0][notes]" class="form-control" placeholder="Any specific customization or details..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem;">Submit Order</button>
            </form>
        <?php endif; ?>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('productsContainer');
            const addBtn = document.getElementById('addProductBtn');
            let productCount = 1;

            addBtn.addEventListener('click', function() {
                const index = productCount++;
                const productHtml = `
                    <div class="product-item" data-index="${index}">
                        <div class="product-item-header">
                            <span>Product #${index + 1}</span>
                            <button type="button" class="remove-product" onclick="this.closest('.product-item').remove()">✕ Remove</button>
                        </div>
                        
                        <div class="form-group">
                            <label>Product Image</label>
                            <input type="file" name="products[${index}][image]" class="form-control" accept="image/*">
                        </div>
                        
                        <div class="form-group">
                            <label>Product Name/Details *</label>
                            <input type="text" name="products[${index}][name]" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Size *</label>
                            <div class="size-options">
                                <label class="size-box"><input type="radio" name="products[${index}][size]" value="S" required><span>S</span></label>
                                <label class="size-box"><input type="radio" name="products[${index}][size]" value="M"><span>M</span></label>
                                <label class="size-box"><input type="radio" name="products[${index}][size]" value="L"><span>L</span></label>
                                <label class="size-box"><input type="radio" name="products[${index}][size]" value="XL"><span>XL</span></label>
                                <label class="size-box"><input type="radio" name="products[${index}][size]" value="2XL"><span>2XL</span></label>
                                <label class="size-box"><input type="radio" name="products[${index}][size]" value="3XL"><span>3XL</span></label>
                                <label class="size-box"><input type="radio" name="products[${index}][size]" value="4XL"><span>4XL</span></label>
                            </div>
                        </div>
                        
                        <div class="grid-2">
                            <div class="form-group">
                                <label>Quantity *</label>
                                <input type="number" name="products[${index}][quantity]" class="form-control" value="1" min="1" required>
                            </div>
                            <div class="form-group">
                                <label>Price (per unit) *</label>
                                <input type="number" step="0.01" name="products[${index}][price]" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label>Deadline (Given by Amez) *</label>
                                <input type="date" name="products[${index}][deadline]" class="form-control" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Notes / Other Info</label>
                            <textarea name="products[${index}][notes]" class="form-control"></textarea>
                        </div>
                    </div>
                `;
                container.insertAdjacentHTML('beforeend', productHtml);
            });
        });
    </script>
</body>
</html>
