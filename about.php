<?php
include 'dp.php';
include 'public_header.php';

// Fetch about us content
$about_us = $conn->query("SELECT * FROM about_us LIMIT 1")->fetch_assoc();
// Fetch official profiles
$officials = $conn->query("SELECT * FROM official_profiles ORDER BY id ASC");
?>

<div class="container py-5">
    <div class="row align-items-center mb-5 pb-5">
        <div class="col-md-6 mb-4 mb-md-0">
            <h1 class="display-4 fw-bold mb-4">About <span>Maison Tech</span></h1>
            <p class="lead text-muted mb-4"><?php echo nl2br(htmlspecialchars($about_us['description'] ?? 'Maison Tech is your premier destination for high-quality technology solutions.')); ?></p>
            <div class="row g-4 mb-4">
                <div class="col-sm-6">
                    <div class="p-4 bg-light rounded-4 h-100 shadow-sm border-0">
                        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-eye text-warning me-2"></i> Our Vision</h5>
                        <p class="small text-muted mb-0"><?php echo htmlspecialchars($about_us['vision'] ?? 'To be the leading tech provider in the region.'); ?></p>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="p-4 bg-light rounded-4 h-100 shadow-sm border-0">
                        <h5 class="fw-bold text-dark mb-3"><i class="fas fa-bullseye text-warning me-2"></i> Our Mission</h5>
                        <p class="small text-muted mb-0"><?php echo htmlspecialchars($about_us['mission'] ?? 'To make technology feel at home for everyone.'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6 px-lg-5">
            <div class="position-relative">
                <img src="<?php echo htmlspecialchars($about_us['office_image'] ?? 'https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'); ?>" alt="Our Office" class="img-fluid rounded-4 shadow-lg">
                <div class="bg-gold position-absolute bottom-0 end-0 p-4 rounded-4 shadow-lg d-none d-lg-block translate-middle-y translate-middle-x">
                    <h2 class="fw-bold text-dark mb-0">10+ Years</h2>
                    <p class="text-dark-50 fw-bold small mb-0">Industry Experience</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Official Profiles Section -->
    <div class="py-5">
        <h2 class="section-title text-center mb-5 fw-bold">Our <span>Leadership</span> Team</h2>
        <div class="row g-4 justify-content-center">
            <?php if ($officials && $officials->num_rows > 0): ?>
                <?php while($official = $officials->fetch_assoc()): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="card product-card text-center border-0 shadow-sm p-4 h-100 rounded-4">
                        <div class="mb-4">
                            <?php if (!empty($official['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($official['image_path']); ?>" class="rounded-circle shadow-sm" style="width: 150px; height: 150px; object-fit: cover;" alt="<?php echo htmlspecialchars($official['name']); ?>">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/150x150?text=Official" class="rounded-circle shadow-sm" style="width: 150px; height: 150px; object-fit: cover;" alt="No Image">
                            <?php endif; ?>
                        </div>
                        <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($official['name']); ?></h5>
                        <p class="text-warning small fw-bold mb-3"><?php echo htmlspecialchars($official['position']); ?></p>
                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($official['bio']); ?></p>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php else: ?>
                <!-- Default profiles if none added yet -->
                <div class="col-lg-3 col-md-6">
                    <div class="card product-card text-center border-0 shadow-sm p-4 h-100 rounded-4">
                        <div class="mb-4">
                            <img src="https://via.placeholder.com/150x150?text=CEO" class="rounded-circle shadow-sm" style="width: 150px; height: 150px; object-fit: cover;" alt="CEO">
                        </div>
                        <h5 class="fw-bold mb-1">John Doe</h5>
                        <p class="text-warning small fw-bold mb-3">CEO & Founder</p>
                        <p class="text-muted small mb-0">Tech visionary with 15 years of industry experience.</p>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="card product-card text-center border-0 shadow-sm p-4 h-100 rounded-4">
                        <div class="mb-4">
                            <img src="https://via.placeholder.com/150x150?text=CTO" class="rounded-circle shadow-sm" style="width: 150px; height: 150px; object-fit: cover;" alt="CTO">
                        </div>
                        <h5 class="fw-bold mb-1">Jane Smith</h5>
                        <p class="text-warning small fw-bold mb-3">CTO</p>
                        <p class="text-muted small mb-0">Expert in software architecture and hardware solutions.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'public_footer.php'; ?>