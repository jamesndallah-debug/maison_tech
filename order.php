<?php
include 'dp.php';

$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$product_name = '';
$product_price = 0;
$product_image = '';

if ($product_id > 0) {
    $p_res = $conn->query("SELECT name, price, image_url FROM products WHERE id = $product_id");
    if ($p_res && $p_res->num_rows > 0) {
        $p = $p_res->fetch_assoc();
        $product_name = $p['name'];
        $product_price = $p['price'];
        $product_image = $p['image_url'];
    }
}

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $client_name = trim($_POST['client_name']);
    $client_email = !empty(trim($_POST['client_email'])) ? trim($_POST['client_email']) : null;
    $client_phone = trim($_POST['client_phone']);
    $region = trim($_POST['region']);
    $order_type = $_POST['order_type'];
    $amount = (float)$_POST['amount'];
    $agency_fee = ($order_type == 'custom') ? ($amount * 0.15) : 0;
    $total_payable = $amount + $agency_fee;
    
    $product_id = ($order_type == 'catalog') ? (int)$_POST['product_id'] : null;
    $product_name_final = ($order_type == 'catalog') ? $_POST['catalog_product_name'] : $_POST['custom_product_name'];
    $product_description = trim($_POST['product_description']);

    $stmt = $conn->prepare("INSERT INTO client_orders (client_name, client_email, client_phone, product_id, product_name, product_description, region, order_type, amount, agency_fee, total_payable) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssissssddd", $client_name, $client_email, $client_phone, $product_id, $product_name_final, $product_description, $region, $order_type, $amount, $agency_fee, $total_payable);
    
    if ($stmt->execute()) {
        $message = "
        <div class='order-success-card text-center p-5 mb-5 rounded-4 shadow-lg animate__animated animate__zoomIn' style='background: white; border-top: 5px solid #ffd700;'>
            <div class='success-icon mb-4'>
                <i class='fas fa-check-circle fa-5x text-success'></i>
            </div>
            <h2 class='fw-bold mb-3'>Order Received!</h2>
            <p class='text-muted mb-4'>Thank you, " . htmlspecialchars($client_name) . ". Your order ID is <strong>#" . $conn->insert_id . "</strong>. Our team will contact you shortly to finalize the process.</p>
            <a href='shop.php' class='btn btn-gold btn-lg px-5'>Back to Shop</a>
        </div>";
    } else {
        $message = "<div class='alert alert-danger'>Error submitting order: " . $stmt->error . "</div>";
    }
    $stmt->close();
}

include 'public_header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

<style>
    .order-page-container {
        background: #f8f9fa;
        min-height: 100vh;
        padding-top: 50px;
        padding-bottom: 80px;
    }
    .order-card {
        border: none;
        border-radius: 20px;
        overflow: hidden;
        background: white;
    }
    .form-section-title {
        font-size: 1.25rem;
        font-weight: 700;
        margin-bottom: 1.5rem;
        color: #111;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .form-section-title i {
        color: #ffd700;
    }
    .input-group-modern {
        position: relative;
        margin-bottom: 1.5rem;
    }
    .input-group-modern i {
        position: absolute;
        left: 15px;
        top: 50%;
        transform: translateY(-50%);
        color: #94a3b8;
        z-index: 10;
    }
    .input-group-modern .form-control {
        padding-left: 45px;
        padding-top: 12px;
        padding-bottom: 12px;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        background: #fcfdfe;
        transition: 0.3s;
    }
    .input-group-modern .form-control:focus {
        border-color: #ffd700;
        box-shadow: 0 0 0 4px rgba(255, 215, 0, 0.1);
        background: white;
    }
    
    /* Order Type Selector */
    .order-type-selector {
        display: flex;
        gap: 20px;
        margin-bottom: 2rem;
    }
    .type-card {
        flex: 1;
        cursor: pointer;
        padding: 20px;
        border: 2px solid #f1f5f9;
        border-radius: 15px;
        text-align: center;
        transition: 0.3s;
        background: #fff;
    }
    .type-card i {
        font-size: 2rem;
        margin-bottom: 10px;
        color: #94a3b8;
        transition: 0.3s;
    }
    .type-card h6 {
        font-weight: 700;
        margin-bottom: 5px;
        color: #475569;
    }
    .type-card p {
        font-size: 0.8rem;
        margin-bottom: 0;
        color: #94a3b8;
    }
    .type-card.active {
        border-color: #ffd700;
        background: rgba(255, 215, 0, 0.05);
    }
    .type-card.active i {
        color: #ffd700;
    }
    .type-card.active h6 {
        color: #111;
    }

    /* Summary Card */
    .summary-card {
        background: #111;
        color: white;
        border-radius: 20px;
        padding: 30px;
        position: sticky;
        top: 100px;
    }
    .summary-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        font-size: 0.95rem;
    }
    .summary-total {
        border-top: 1px solid rgba(255,255,255,0.1);
        padding-top: 20px;
        margin-top: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .summary-total h4 {
        color: #ffd700;
        font-weight: 800;
        margin-bottom: 0;
    }
    .product-preview {
        display: flex;
        gap: 15px;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 20px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .product-preview img {
        width: 60px;
        height: 60px;
        object-fit: cover;
        border-radius: 10px;
    }
    .product-preview h6 {
        margin-bottom: 0;
        font-weight: 700;
    }
</style>

<div class="order-page-container">
    <div class="container">
        <?php if ($message): ?>
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <?php echo $message; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-5">
                <!-- Left Column: Form -->
                <div class="col-lg-8">
                    <div class="order-card shadow-sm p-4 p-md-5 animate__animated animate__fadeInLeft">
                        <div class="mb-5">
                            <h1 class="fw-bold mb-2">Complete Your Order</h1>
                            <p class="text-muted">Where technology feels at home. Let's get your tech ready.</p>
                        </div>

                        <form action="order.php" method="POST" id="order-form">
                            <!-- Section 1: Contact Information -->
                            <div class="form-section mb-5">
                                <h5 class="form-section-title"><i class="fas fa-user-circle"></i> Personal Details</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="input-group-modern">
                                            <i class="fas fa-user"></i>
                                            <input type="text" name="client_name" class="form-control" placeholder="Full Name" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group-modern">
                                            <i class="fas fa-phone"></i>
                                            <input type="text" name="client_phone" class="form-control" placeholder="Phone Number" required>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group-modern">
                                            <i class="fas fa-envelope"></i>
                                            <input type="email" name="client_email" class="form-control" placeholder="Email Address (Optional)">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="input-group-modern">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <input type="text" name="region" class="form-control" placeholder="Your Region / City" required>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Section 2: Order Type -->
                            <div class="form-section mb-5">
                                <h5 class="form-section-title"><i class="fas fa-shopping-bag"></i> Order Information</h5>
                                
                                <div class="order-type-selector">
                                    <div class="type-card <?php echo ($product_id > 0) ? 'active' : ''; ?>" id="cardCatalog" onclick="selectType('catalog')">
                                        <input type="radio" name="order_type" value="catalog" id="typeCatalog" style="display:none" <?php echo ($product_id > 0) ? 'checked' : ''; ?>>
                                        <i class="fas fa-store"></i>
                                        <h6>From Catalog</h6>
                                        <p>Choose existing products</p>
                                    </div>
                                    <div class="type-card <?php echo ($product_id == 0) ? 'active' : ''; ?>" id="cardCustom" onclick="selectType('custom')">
                                        <input type="radio" name="order_type" value="custom" id="typeCustom" style="display:none" <?php echo ($product_id == 0) ? 'checked' : ''; ?>>
                                        <i class="fas fa-globe"></i>
                                        <h6>Custom Sourcing</h6>
                                        <p>Items from other regions</p>
                                    </div>
                                </div>

                                <!-- Catalog Fields -->
                                <div id="catalog-fields" class="animate__animated animate__fadeIn" style="display: <?php echo ($product_id > 0) ? 'block' : 'none'; ?>">
                                    <div class="input-group-modern">
                                        <i class="fas fa-box"></i>
                                        <input type="hidden" name="product_id" value="<?php echo $product_id; ?>">
                                        <input type="text" name="catalog_product_name" class="form-control" value="<?php echo htmlspecialchars($product_name); ?>" readonly id="catalog-product-name">
                                    </div>
                                </div>

                                <!-- Custom Fields -->
                                <div id="custom-fields" class="animate__animated animate__fadeIn" style="display: <?php echo ($product_id == 0) ? 'block' : 'none'; ?>">
                                    <div class="input-group-modern">
                                        <i class="fas fa-pencil-alt"></i>
                                        <input type="text" name="custom_product_name" class="form-control" placeholder="What product do you need?" id="custom-product-name">
                                    </div>
                                    <div class="input-group-modern">
                                        <i class="fas fa-align-left" style="top: 20px; transform: none;"></i>
                                        <textarea name="product_description" class="form-control" rows="4" placeholder="Detailed description (Color, Specs, Region to source from...)"></textarea>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <label class="form-label fw-bold text-muted small mb-2">Estimated Item Price ($)</label>
                                    <div class="input-group-modern">
                                        <i class="fas fa-dollar-sign"></i>
                                        <input type="number" step="0.01" name="amount" class="form-control" id="order-amount" value="<?php echo $product_price; ?>" oninput="calculateFees()">
                                    </div>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-gold btn-lg w-100 py-3 fw-bold shadow-lg">
                                <i class="fas fa-paper-plane me-2"></i> Submit Order
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Right Column: Summary -->
                <div class="col-lg-4 animate__animated animate__fadeInRight">
                    <div class="summary-card shadow-lg">
                        <h4 class="fw-bold mb-4">Order Summary</h4>
                        
                        <div class="product-preview" id="summary-product-preview">
                            <img src="<?php echo !empty($product_image) ? $product_image : 'https://via.placeholder.com/60'; ?>" alt="Product" id="summary-img">
                            <div>
                                <h6 id="summary-name"><?php echo $product_id > 0 ? htmlspecialchars($product_name) : 'Custom Item'; ?></h6>
                                <span class="text-muted small" id="summary-type"><?php echo $product_id > 0 ? 'Catalog Product' : 'Custom Sourcing'; ?></span>
                            </div>
                        </div>

                        <div class="summary-body">
                            <div class="summary-item">
                                <span>Item Price</span>
                                <span id="sum-amount">TSh <?php echo number_format($product_price, 0); ?></span>
                            </div>
                            <div class="summary-item">
                                <span id="sum-fee-label">Agency Fee (0%)</span>
                                <span id="sum-fee">TSh 0</span>
                            </div>
                            <div class="summary-item">
                                <span>Shipping Info</span>
                                <span class="text-warning">Calculated later</span>
                            </div>
                            
                            <div class="summary-total">
                                <div>
                                    <span class="text-muted small d-block">Estimated Total</span>
                                    <h4><span id="sum-total">TSh <?php echo number_format($product_price, 0); ?></span></h4>
                                </div>
                                <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" title="Final price will be confirmed after review."></i>
                            </div>
                        </div>

                        <div class="mt-4 p-3 rounded-3" style="background: rgba(255,215,0,0.1);">
                            <p class="small mb-0 text-white-50">
                                <i class="fas fa-shield-alt text-warning me-2"></i> 
                                Secure and professional sourcing guaranteed.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function selectType(type) {
    document.getElementById('typeCatalog').checked = (type === 'catalog');
    document.getElementById('typeCustom').checked = (type === 'custom');
    
    document.getElementById('cardCatalog').classList.toggle('active', type === 'catalog');
    document.getElementById('cardCustom').classList.toggle('active', type === 'custom');
    
    toggleOrderFields();
}

function toggleOrderFields() {
    const isCatalog = document.getElementById('typeCatalog').checked;
    const catalogFields = document.getElementById('catalog-fields');
    const customFields = document.getElementById('custom-fields');
    const customInput = document.getElementById('custom-product-name');
    
    if (isCatalog) {
        catalogFields.style.display = 'block';
        customFields.style.display = 'none';
        customInput.required = false;
        document.getElementById('summary-type').innerText = 'Catalog Product';
        document.getElementById('summary-name').innerText = document.getElementById('catalog-product-name').value || 'Selected Product';
    } else {
        catalogFields.style.display = 'none';
        customFields.style.display = 'block';
        customInput.required = true;
        document.getElementById('summary-type').innerText = 'Custom Sourcing';
        document.getElementById('summary-name').innerText = 'Custom Sourcing Item';
    }
    
    calculateFees();
}

function calculateFees() {
    const isCustom = document.getElementById('typeCustom').checked;
    const amount = parseFloat(document.getElementById('order-amount').value) || 0;
    const agencyFee = isCustom ? (amount * 0.15) : 0;
    const total = amount + agencyFee;
    
    // Update summary labels
    document.getElementById('sum-fee-label').innerText = isCustom ? 'Agency Fee (15%)' : 'Agency Fee (0%)';
    
    // Update displays
    document.getElementById('sum-amount').innerText = '$' + amount.toFixed(2);
    document.getElementById('sum-fee').innerText = '$' + agencyFee.toFixed(2);
    document.getElementById('sum-total').innerText = '$' + total.toFixed(2);
}

// Update custom name in summary as user types
document.getElementById('custom-product-name').addEventListener('input', function() {
    if (document.getElementById('typeCustom').checked) {
        document.getElementById('summary-name').innerText = this.value || 'Custom Sourcing Item';
    }
});

window.onload = function() {
    calculateFees();
    
    // Tooltip init
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
}
</script>

<?php include 'public_footer.php'; ?>