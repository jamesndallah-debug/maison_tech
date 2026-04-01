<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If the user is not logged in, redirect to the login page.
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit;
}

include 'dp.php';

// Chairman is read-only (no POS access)
if (isset($_SESSION['role']) && $_SESSION['role'] === 'chairman') {
    header('Location: dashboard.php');
    exit;
}

// Handle Complete Sale
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_sale'])) {
    $cart_data = json_decode($_POST['cart_data'], true);
    $payment_method = $_POST['payment_method'] ?? 'Cash';
    if ($payment_method === 'Money Wallet') { $payment_method = 'Mobile Money Wallet'; } // Compatibility fix
    $payment_provider = $_POST['payment_provider'] ?? NULL;
    $user_id = $_SESSION['user_id'];
    $total_amount = 0;

    if (empty($cart_data)) {
        header("Location: sales.php?err=Cart+is+empty");
        exit;
    }

    foreach ($cart_data as $item) {
        $total_amount += $item['price'] * $item['quantity'];
    }

    $conn->begin_transaction();

    try {
        // 1. Create a new sale record
        $stmt = $conn->prepare("INSERT INTO sales (user_id, total_amount, payment_method, payment_provider) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("idss", $user_id, $total_amount, $payment_method, $payment_provider);
        $stmt->execute();
        $sale_id = $stmt->insert_id;
        $stmt->close();

        // 2. Process each cart item
        foreach ($cart_data as $item) {
            $product_id = $item['id'];
            $quantity = $item['quantity'];
            $price_per_unit = $item['price'];

            // Insert into sale_items
            $stmt = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, price_per_unit) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $sale_id, $product_id, $quantity, $price_per_unit);
            $stmt->execute();
            $stmt->close();

            // Decrease product quantity
            $stmt = $conn->prepare("UPDATE products SET quantity = quantity - ? WHERE id = ?");
            $stmt->bind_param("ii", $quantity, $product_id);
            $stmt->execute();
            $stmt->close();

            // Log stock movement
            $quantity_change = -$quantity;
            $stmt = $conn->prepare("INSERT INTO stock_movements (product_id, quantity_change, movement_type, user_id) VALUES (?, ?, 'Sale', ?)");
            $stmt->bind_param("iii", $product_id, $quantity_change, $user_id);
            $stmt->execute();
            $stmt->close();
        }

        // 3. Log activity
        $action = "Completed sale #{$sale_id} - Total: TSh " . number_format($total_amount, 0);
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action) VALUES (?, ?)");
        $log->bind_param("is", $user_id, $action);
        $log->execute();
        $log->close();

        $conn->commit();
        header("Location: receipt.php?id=" . $sale_id . "&autoprint=1");
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        header("Location: sales.php?err=" . urlencode($e->getMessage()));
        exit;
    }
}

// Only include the layout AFTER POST redirects are done
include 'header.php';
?>

<div class="header-actions">
    <h1>Point of Sale (POS)</h1>
</div>

<?php if(isset($_GET['err'])): ?>
    <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['err']); ?></div>
<?php endif; ?>

<div class="pos-grid">
    <div class="product-selection">
        <div class="form-card">
            <h2><i class="fas fa-search"></i> Search Products</h2>
            <div class="search-box">
                <input type="text" id="product-search-input" class="form-control" placeholder="Search by name or category...">
                <div id="product-search-results" class="search-results"></div>
            </div>
        </div>
        
        <div class="quick-items">
            <h3>Quick Categories</h3>
            <div class="quick-buttons">
                <?php 
                    $all_cats = $conn->query("SELECT * FROM categories ORDER BY name ASC");
                    if ($all_cats && $all_cats->num_rows > 0):
                        while($c = $all_cats->fetch_assoc()):
                ?>
                    <button class="quick-btn" onclick="filterByCategory(<?php echo $c['id']; ?>)">
                        <i class="fas fa-tag"></i> <?php echo htmlspecialchars($c['name']); ?>
                    </button>
                <?php 
                        endwhile;
                    else:
                ?>
                    <p class="text-muted" style="font-size: 12px;">No categories found. <a href="categories.php">Add some here</a>.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="cart-section">
        <div class="form-card cart-card">
            <div class="cart-header">
                <h2><i class="fas fa-shopping-cart"></i> Current Cart</h2>
                <button class="btn-clear" onclick="clearCart()">Clear All</button>
            </div>
            
            <div class="cart-items-wrapper">
                <table id="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Items added here -->
                    </tbody>
                </table>
            </div>

            <div class="cart-footer">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span id="subtotal">TSh 0.00</span>
                </div>
                <div class="summary-row total">
                    <span>Total Amount</span>
                    <span id="cart-total">TSh 0.00</span>
                </div>
                
                <form action="sales.php" method="post" id="sale-form" autocomplete="off">
                    <div class="payment-method-selector">
                        <label>Payment Method:</label>
                        <select name="payment_method" id="payment_method" class="form-control" onchange="toggleProviderDropdown()" required>
                            <option value="Cash">Cash</option>
                            <option value="Mobile Money Wallet">Mobile Money Wallet</option>
                            <option value="Bank">Bank</option>
                        </select>
                    </div>

                    <div id="wallet-provider-selector" class="payment-provider-selector" style="display: none; margin-bottom: 16px;">
                        <label>Wallet Provider:</label>
                        <select name="payment_provider_wallet" id="payment_provider_wallet" class="form-control">
                            <option value="M-Pesa">M-Pesa</option>
                            <option value="Mixx By Yass">Mixx By Yass</option>
                            <option value="Halotel">Halotel</option>
                            <option value="Airtel Money">Airtel Money</option>
                            <option value="Selcom">Selcom</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div id="bank-provider-selector" class="payment-provider-selector" style="display: none; margin-bottom: 16px;">
                        <label>Bank Name:</label>
                        <select name="payment_provider_bank" id="payment_provider_bank" class="form-control">
                            <option value="CRDB">CRDB</option>
                            <option value="NMB">NMB</option>
                            <option value="TPB">TPB</option>
                            <option value="NBC">NBC</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <input type="hidden" name="payment_provider" id="payment_provider_final">
                    <input type="hidden" name="cart_data" id="cart_data">
                    <button type="submit" name="complete_sale" class="btn btn-primary btn-block" onclick="prepareSaleSubmit()">
                        <i class="fas fa-check-circle"></i> Complete Sale
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
let cart = [];

document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('product-search-input');
    const searchResults = document.getElementById('product-search-results');

    searchInput.addEventListener('input', function() {
        const query = this.value;
        if (query.length < 2) {
            searchResults.style.display = 'none';
            return;
        }

        fetch(`search_products.php?q=${encodeURIComponent(query)}`)
            .then(res => res.json())
            .then(data => {
                searchResults.innerHTML = '';
                if (data.length === 0) {
                    searchResults.innerHTML = '<div class="no-results">No products found</div>';
                } else {
                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'search-item';
                        div.innerHTML = `
                            <div class="p-info">
                                <span class="p-name">${p.name}</span>
                                <span class="p-price">$${p.price}</span>
                            </div>
                            <span class="p-stock">${p.quantity} in stock</span>
                        `;
                        div.onclick = () => addToCart(p);
                        searchResults.appendChild(div);
                    });
                }
                searchResults.style.display = 'block';
            });
    });

    document.addEventListener('click', (e) => {
        if (!searchInput.contains(e.target)) searchResults.style.display = 'none';
    });
});

function filterByCategory(catId) {
    const searchResults = document.getElementById('product-search-results');
    
    fetch(`search_products.php?cat_id=${catId}`)
        .then(res => res.json())
        .then(data => {
            searchResults.innerHTML = '';
            if (data.length === 0) {
                searchResults.innerHTML = '<div class="no-results">No products in this category</div>';
            } else {
                data.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'search-item';
                    div.innerHTML = `
                        <div class="p-info">
                            <span class="p-name">${p.name}</span>
                            <span class="p-price">$${p.price}</span>
                        </div>
                        <span class="p-stock">${p.quantity} in stock</span>
                    `;
                    div.onclick = () => addToCart(p);
                    searchResults.appendChild(div);
                });
            }
            searchResults.style.display = 'block';
        });
}

function quickAdd(query) {
    fetch(`search_products.php?q=${encodeURIComponent(query)}`)
        .then(res => res.json())
        .then(data => {
            if (data.length === 1) {
                addToCart(data[0]);
            } else if (data.length > 1) {
                // If multiple found, show search results
                const searchResults = document.getElementById('product-search-results');
                searchResults.innerHTML = '';
                data.forEach(p => {
                    const div = document.createElement('div');
                    div.className = 'search-item';
                    div.innerHTML = `
                        <div class="p-info">
                            <span class="p-name">${p.name}</span>
                            <span class="p-price">$${p.price}</span>
                        </div>
                        <span class="p-stock">${p.quantity} in stock</span>
                    `;
                    div.onclick = () => addToCart(p);
                    searchResults.appendChild(div);
                });
                searchResults.style.display = 'block';
            } else {
                alert(`No product found for "${query}"`);
            }
        });
}

function addToCart(p) {
    const existing = cart.find(item => item.id == p.id);
    if (existing) {
        if (existing.quantity < p.quantity) {
            existing.quantity++;
        } else {
            alert('Not enough stock!');
        }
    } else {
        cart.push({ id: p.id, name: p.name, price: parseFloat(p.price), quantity: 1, max: p.quantity });
    }
    renderCart();
}

function removeFromCart(id) {
    cart = cart.filter(item => item.id != id);
    renderCart();
}

function updateQty(id, delta) {
    const item = cart.find(i => i.id == id);
    if (item) {
        const newQty = item.quantity + delta;
        if (newQty > 0 && newQty <= item.max) {
            item.quantity = newQty;
            renderCart();
        }
    }
}

function clearCart() {
    if (confirm('Clear entire cart?')) {
        cart = [];
        renderCart();
    }
}

function renderCart() {
    const tbody = document.querySelector('#cart-table tbody');
    tbody.innerHTML = '';
    let total = 0;

    cart.forEach(item => {
        const rowTotal = item.price * item.quantity;
        total += rowTotal;
        tbody.innerHTML += `
            <tr>
                <td>
                    <div class="cart-p-name">${item.name}</div>
                    <div class="cart-p-price">TSh ${item.price.toFixed(2)}</div>
                </td>
                <td>
                    <div class="qty-control">
                        <button onclick="updateQty(${item.id}, -1)">-</button>
                        <span>${item.quantity}</span>
                        <button onclick="updateQty(${item.id}, 1)">+</button>
                    </div>
                </td>
                <td>TSh ${rowTotal.toFixed(2)}</td>
                <td><button class="btn-remove" onclick="removeFromCart(${item.id})">&times;</button></td>
            </tr>
        `;
    });

    document.getElementById('subtotal').innerText = `TSh ${total.toFixed(2)}`;
    document.getElementById('cart-total').innerText = `TSh ${total.toFixed(2)}`;
    document.getElementById('cart_data').value = JSON.stringify(cart);
}

function toggleProviderDropdown() {
    const method = document.getElementById('payment_method').value;
    const walletDiv = document.getElementById('wallet-provider-selector');
    const bankDiv = document.getElementById('bank-provider-selector');

    walletDiv.style.display = (method === 'Mobile Money Wallet') ? 'block' : 'none';
    bankDiv.style.display = (method === 'Bank') ? 'block' : 'none';
}

function prepareSaleSubmit() {
    const method = document.getElementById('payment_method').value;
    let provider = '';

    if (method === 'Mobile Money Wallet') {
        provider = document.getElementById('payment_provider_wallet').value;
    } else if (method === 'Bank') {
        provider = document.getElementById('payment_provider_bank').value;
    }

    document.getElementById('payment_provider_final').value = provider;
}
</script>

<style>
    .pos-grid { 
        display: grid; 
        grid-template-columns: 1fr 400px; 
        gap: 24px; 
        align-items: start;
    }
    .search-box { position: relative; }
    .search-results {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 100;
        display: none;
        max-height: 300px;
        overflow-y: auto;
    }
    .search-item {
        padding: 12px 16px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .search-item:hover { background: #f8fafc; }
    .p-name { display: block; font-weight: 600; color: #1e293b; }
    .p-price { color: #007bff; font-weight: 500; font-size: 14px; }
    .p-stock { font-size: 12px; color: #64748b; }

    .quick-categories { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 16px; }
    .cat-btn {
        padding: 8px 16px;
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        font-size: 13px;
        color: #475569;
        cursor: pointer;
        transition: all 0.2s;
    }
    .cat-btn:hover { background: #007bff; color: white; border-color: #007bff; }

    .quick-items { margin-top: 24px; }
    .quick-items h3 { font-size: 14px; color: #64748b; margin-bottom: 12px; font-weight: 700; text-transform: uppercase; }
    .quick-buttons { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 12px; }
    .quick-btn {
        padding: 12px 8px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        color: #1e293b;
        cursor: pointer;
        transition: all 0.2s;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 6px;
        text-align: center;
    }
    .quick-btn:hover { background: #e2e8f0; border-color: #cbd5e1; transform: translateY(-2px); }
    .quick-btn i { color: #007bff; font-size: 18px; }

    .cart-card { 
        height: auto; 
        min-height: 400px;
        max-height: calc(100vh - 160px); 
        display: flex; 
        flex-direction: column; 
        padding: 0; 
    }
    .cart-header { padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
    .cart-header h2 { margin: 0; font-size: 18px; }
    .btn-clear { background: none; border: none; color: #ef4444; font-size: 12px; cursor: pointer; font-weight: 500; }
    
    .cart-items-wrapper { flex: 1; overflow-y: auto; padding: 0 20px; }
    .cart-footer { padding: 20px; background: #f8fafc; border-top: 1px solid #f1f5f9; }
    .summary-row { display: flex; justify-content: space-between; margin-bottom: 8px; color: #64748b; font-size: 14px; }
    .summary-row.total { font-size: 18px; font-weight: 700; color: #1e293b; margin-top: 12px; padding-top: 12px; border-top: 2px dashed #e2e8f0; }
    
    .qty-control { display: flex; align-items: center; gap: 8px; background: #f1f5f9; border-radius: 4px; padding: 4px; }
    .qty-control button { width: 24px; height: 24px; border: none; background: white; border-radius: 4px; cursor: pointer; font-weight: 700; }
    .qty-control span { min-width: 20px; text-align: center; font-size: 13px; font-weight: 600; }
    
    .cart-p-name { font-weight: 600; font-size: 14px; }
    .cart-p-price { font-size: 12px; color: #64748b; }
    .btn-remove { background: none; border: none; color: #94a3b8; font-size: 20px; cursor: pointer; }
    .btn-remove:hover { color: #ef4444; }
    .btn-block { width: 100%; padding: 14px; font-size: 16px; margin-top: 16px; }

    .payment-method-selector { margin-bottom: 16px; }
    .payment-method-selector label { font-size: 13px; font-weight: 600; color: #64748b; margin-bottom: 6px; display: block; }
    .payment-method-selector select { background: #fff; border: 2px solid #e2e8f0; font-weight: 600; }

    /* POS Mobile Responsiveness */
    @media (max-width: 1200px) {
        .pos-grid {
            grid-template-columns: 1fr;
        }
        .cart-card {
            max-height: none;
            height: auto;
        }
    }

    @media (max-width: 768px) {
        .quick-buttons {
            grid-template-columns: repeat(auto-fill, minmax(90px, 1fr));
        }
        .quick-btn {
            padding: 10px 5px;
            font-size: 12px;
        }
        .cart-header h2 {
            font-size: 16px;
        }
    }
</style>

<?php
$conn->close();
include 'footer.php'; 
?>