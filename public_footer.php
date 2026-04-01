<footer class="mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h4 class="text-white mb-4">Maison Tech</h4>
                <p>Where Technology Feels at Home. Your reliable partner for sourcing tech from any region with ease and confidence.</p>
                <div class="social-links">
                    <a href="https://wa.me/255710726602" target="_blank" class="text-white me-3"><i class="fab fa-whatsapp"></i></a>
                    <a href="https://www.instagram.com/james.flores.jr?igsh=MjE1eTR1N3M4cTls&utm_source=qr" target="_blank" class="text-white me-3"><i class="fab fa-instagram"></i></a>
                    <a href="https://www.tiktok.com/@james_flores_the.3rd?_r=1&_t=ZS-94nHT0emWBd" target="_blank" class="text-white me-3"><i class="fab fa-tiktok"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <h4 class="text-white mb-4">Quick Links</h4>
                <ul class="list-unstyled">
                    <li><a href="index.php" class="text-white text-decoration-none mb-2 d-block">Home</a></li>
                    <li><a href="shop.php" class="text-white text-decoration-none mb-2 d-block">Shop Products</a></li>
                    <li><a href="about.php" class="text-white text-decoration-none mb-2 d-block">About Us</a></li>
                    <li><a href="order.php" class="text-white text-decoration-none mb-2 d-block">Make an Order</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h4 class="text-white mb-4">Contact Info</h4>
                <ul class="list-unstyled text-white">
                    <li class="mb-2"><i class="fas fa-map-marker-alt me-3"></i> Rwezaula Singida</li>
                    <li class="mb-2"><i class="fas fa-phone me-3"></i> +255 710 726 602, +255 767 202 7115</li>
                    <li class="mb-2"><i class="fas fa-envelope me-3"></i> jamesndallah@gmail.com</li>
                </ul>
            </div>
        </div>
        <hr class="mt-4 mb-4 border-light">
        <div class="text-center text-white-50">
            <small>&copy; <?php echo date('Y'); ?> Maison Tech. All Rights Reserved.</small>
            <div class="mt-2">
                <a href="login.php" class="text-white-50 text-decoration-none" style="font-size: 10px; opacity: 0.3;">Staff Portal</a>
            </div>
        </div>
    </div>
</footer>

<!-- Floating Social Buttons -->
<div class="floating-container">
    <a href="https://www.instagram.com/james.flores.jr?igsh=MjE1eTR1N3M4cTls&utm_source=qr" class="floating-btn instagram-float animate__animated animate__bounceInUp" target="_blank" style="animation-delay: 0.1s;">
        <i class="fab fa-instagram"></i>
        <span>Follow us</span>
    </a>
    <a href="https://www.tiktok.com/@james_flores_the.3rd?_r=1&_t=ZS-94nHT0emWBd" class="floating-btn tiktok-float animate__animated animate__bounceInUp" target="_blank" style="animation-delay: 0.2s;">
        <i class="fab fa-tiktok"></i>
        <span>Follow us</span>
    </a>
    <a href="https://wa.me/255710726602" class="floating-btn whatsapp-float animate__animated animate__bounceInUp" target="_blank" style="animation-delay: 0.3s;">
        <i class="fab fa-whatsapp"></i>
        <span>Chat with us</span>
    </a>
</div>

<style>
    .floating-container {
        position: fixed;
        bottom: 30px;
        right: 30px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        align-items: flex-end;
        z-index: 1000;
    }
    .floating-btn {
        color: white;
        padding: 12px 25px;
        border-radius: 50px;
        display: flex;
        align-items: center;
        gap: 10px;
        text-decoration: none;
        font-weight: 600;
        transition: 0.3s;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }
    .floating-btn:hover {
        color: white;
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
    }
    .whatsapp-float {
        background-color: #25d366;
        box-shadow: 0 10px 25px rgba(37, 211, 102, 0.3);
    }
    .whatsapp-float:hover {
        background-color: #128c7e;
    }
    .instagram-float {
        background: linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%);
        box-shadow: 0 10px 25px rgba(220, 39, 67, 0.3);
    }
    .tiktok-float {
        background-color: #000000;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
    }
    .floating-btn i {
        font-size: 1.5rem;
    }
    @media (max-width: 768px) {
        .floating-btn span {
            display: none;
        }
        .floating-btn {
            padding: 15px;
            border-radius: 50%;
        }
        .floating-container {
            bottom: 20px;
            right: 20px;
            gap: 10px;
        }
    }
</style>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>