<?php
include 'dp.php';
include 'public_header.php';

$category_filter = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = "WHERE 1=1";
if ($category_filter > 0) {
    $where .= " AND p.category_id = $category_filter";
}
if (!empty($search_query)) {
    $where .= " AND (p.name LIKE '%$search_query%' OR p.description LIKE '%$search_query%')";
}

// Pagination Logic
$items_per_page = 12;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $items_per_page;

// Get total count for pagination
$total_res = $conn->query("SELECT COUNT(*) as total FROM products p $where");
$total_count = $total_res->fetch_assoc()['total'];
$total_pages = ceil($total_count / $items_per_page);

$products = $conn->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id $where ORDER BY p.name ASC LIMIT $items_per_page OFFSET $offset");
$categories = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>

<style>
    .bg-gold { background-color: #ffd700 !important; }
    .border-gold { border-color: #ffd700 !important; }
    .page-link:focus { box-shadow: 0 0 0 0.25rem rgba(255, 215, 0, 0.25); }
</style>

<div class="container py-5">
    <div class="row mb-5 align-items-center">
        <div class="col-md-6">
            <h1 class="fw-bold">Our <span>Products</span></h1>
            <p class="text-muted">Browse our extensive collection of technology solutions.</p>
        </div>
        <div class="col-md-6">
            <form action="shop.php" method="GET" class="d-flex gap-2">
                <input type="text" name="search" class="form-control" placeholder="Search products..." value="<?php echo htmlspecialchars($search_query); ?>">
                <button type="submit" class="btn btn-dark px-4">Search</button>
            </form>
        </div>
    </div>

    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="card border-0 shadow-sm p-3 rounded-4">
                <h5 class="fw-bold mb-4">Categories</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <a href="shop.php" class="text-decoration-none <?php echo $category_filter == 0 ? 'text-warning fw-bold' : 'text-dark'; ?>">All Categories</a>
                    </li>
                    <?php while($cat = $categories->fetch_assoc()): ?>
                    <li class="mb-2">
                        <a href="shop.php?category=<?php echo $cat['id']; ?>" class="text-decoration-none <?php echo $category_filter == $cat['id'] ? 'text-warning fw-bold' : 'text-dark'; ?>">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="col-lg-9">
            <div class="row g-4">
                <?php if ($products && $products->num_rows > 0): ?>
                    <?php while($product = $products->fetch_assoc()): ?>
                    <div class="col-md-6 col-xl-4">
                        <div class="card product-card h-100 shadow-sm border-0">
                            <?php if (!empty($product['image'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top p-3 rounded-4" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/400x300?text=No+Image" class="card-img-top p-3 rounded-4" alt="No Image">
                            <?php endif; ?>
                            <div class="card-body p-4 d-flex flex-column">
                                <span class="badge bg-warning text-dark mb-2 align-self-start rounded-pill fw-bold"><?php echo htmlspecialchars($product['category_name']); ?></span>
                                <h5 class="card-title fw-bold mb-3"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted small mb-4"><?php echo htmlspecialchars(substr($product['description'], 0, 80)) . '...'; ?></p>
                                <div class="mt-auto d-flex justify-content-between align-items-center">
                                    <span class="product-price fs-4 fw-bold text-dark">TSh <?php echo number_format($product['price'], 0); ?></span>
                                    <a href="order.php?product_id=<?php echo $product['id']; ?>" class="btn btn-gold btn-sm px-4">Order Now</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-search fa-4x text-muted mb-4"></i>
                        <h3 class="text-muted">No products found</h3>
                        <p class="text-muted">Try adjusting your search or filters.</p>
                        <a href="shop.php" class="btn btn-outline-dark mt-3">Clear All Filters</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Pagination UI -->
            <?php if ($total_pages > 1): ?>
                <nav class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php 
                        // Function to build query string for pagination links
                        function build_page_url($page, $current_cat, $current_search) {
                            $params = ['page' => $page];
                            if ($current_cat > 0) $params['category'] = $current_cat;
                            if (!empty($current_search)) $params['search'] = $current_search;
                            return 'shop.php?' . http_build_query($params);
                        }
                        ?>

                        <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo build_page_url($current_page - 1, $category_filter, $search_query); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?php echo $i == $current_page ? 'active' : ''; ?>">
                                <a class="page-link <?php echo $i == $current_page ? 'bg-gold border-gold text-dark' : 'text-dark'; ?>" href="<?php echo build_page_url($i, $category_filter, $search_query); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                            <a class="page-link" href="<?php echo build_page_url($current_page + 1, $category_filter, $search_query); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'public_footer.php'; ?>