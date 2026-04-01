<?php
include 'dp.php';
include 'public_header.php';

// Fetch some featured products
$featured_products = $conn->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id LIMIT 6");

// Fetch agency image from about_us
$about_res = $conn->query("SELECT agency_service_image FROM about_us WHERE id = 1");
$about_data = $about_res->fetch_assoc();
$agency_image = $about_data['agency_service_image'] ?? 'https://images.unsplash.com/photo-1553413077-190dd305871c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
?>

<!-- Hero Section -->
<section class="hero-section text-center text-white" style="background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('https://images.unsplash.com/photo-1518770660439-4636190af475?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80'); background-size: cover; background-position: center; padding: 150px 0;">
    <div class="container">
        <div class="mb-4">
            <img src="images/maison-tech-logo.png.jpeg" alt="Maison Tech Logo" style="width: 250px; height: auto; filter: drop-shadow(0 0 20px rgba(255,215,0,0.3));">
        </div>
        <h1 class="hero-title display-3 fw-bold mb-4">Maison Tech <span>Solutions</span></h1>
        <p class="lead mb-5 fs-4">Where Technology Feels at Home. Get your tech from any region with our trusted agency service.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="shop.php" class="btn btn-gold btn-lg shadow-sm">Explore Products</a>
            <a href="order.php" class="btn btn-outline-light btn-lg px-4 shadow-sm">Make Custom Order</a>
        </div>
    </div>
</section>

<!-- Agency Service Section -->
<section class="py-5 bg-light">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-md-6 mb-4 mb-md-0">
                <img src="<?php echo htmlspecialchars($agency_image); ?>" alt="Tech Sourcing" class="img-fluid rounded-4 shadow-lg">
            </div>
            <div class="col-md-6 px-lg-5">
                <h2 class="section-title text-start mb-4 fw-bold">Our Agency <span>Service</span></h2>
                <p class="lead mb-4 text-muted">Need technology that isn't available in your region? We act as your agent to source, verify, and ship the tech you need directly to your doorstep.</p>
                <ul class="list-unstyled mb-5">
                    <li class="mb-3 d-flex align-items-center">
                        <i class="fas fa-check-circle text-warning me-3 fs-4"></i>
                        <span>Source from any region globally.</span>
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <i class="fas fa-check-circle text-warning me-3 fs-4"></i>
                        <span>Secure payment and logistics management.</span>
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <i class="fas fa-check-circle text-warning me-3 fs-4"></i>
                        <span>Low 15% agency fee on all custom orders.</span>
                    </li>
                    <li class="mb-3 d-flex align-items-center">
                        <i class="fas fa-check-circle text-warning me-3 fs-4"></i>
                        <span>Professional tech consultation and verification.</span>
                    </li>
                </ul>
                <a href="order.php" class="btn btn-dark btn-lg px-4 shadow-sm">Start Your Order Now</a>
            </div>
        </div>
    </div>
</section>

<!-- Featured Products Section -->
<section class="py-5">
    <div class="container py-5">
        <h2 class="section-title text-center mb-5 fw-bold">Featured <span>Products</span></h2>
        <div class="row g-4">
            <?php if ($featured_products && $featured_products->num_rows > 0): ?>
                <?php while($product = $featured_products->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card product-card h-100 shadow-sm border-0">
                        <?php if (!empty($product['image'])): ?>
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top p-3 rounded-4" alt="<?php echo htmlspecialchars($product['name']); ?>">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/400x300?text=No+Image" class="card-img-top p-3 rounded-4" alt="No Image">
                        <?php endif; ?>
                        <div class="card-body p-4">
                            <span class="badge bg-warning text-dark mb-2 px-3 py-2 rounded-pill fw-bold"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <h5 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="card-text text-muted small mb-4"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <span class="product-price fs-4 fw-bold text-dark">$<?php echo number_format($product['price'], 2); ?></span>
                                <a href="order.php?product_id=<?php echo $product['id']; ?>" class="btn btn-gold btn-sm px-4 shadow-sm">Order Now</a>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted fs-5">No products currently available in our catalog. Check back soon!</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-5 pt-4">
            <a href="shop.php" class="btn btn-outline-dark btn-lg px-5 shadow-sm">View All Products</a>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="py-5 bg-dark text-white text-center">
    <div class="container py-5">
        <h2 class="mb-4 display-5 fw-bold">Ready to Get the Best Technology?</h2>
        <p class="lead mb-5 text-white-50 fs-4 px-lg-5">Whether you're looking for items in our catalog or need us to source custom tech from abroad, we're here to help.</p>
        <a href="order.php" class="btn btn-gold btn-lg px-5 py-3 shadow-lg fs-5 fw-bold">Make an Order Today</a>
    </div>
</section>

<?php include 'public_footer.php'; ?>