<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maison Tech - Where Technology Feels at Home</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #111;
            --secondary-color: #ffd700; /* Gold */
            --accent-color: #f5f5f5;
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #fff;
            color: #333;
        }
        .navbar {
            background-color: var(--primary-color);
            padding: 1rem 2rem;
        }
        .navbar-brand {
            color: var(--secondary-color) !important;
            font-weight: 700;
            font-size: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .navbar-brand img {
            width: 80px;
            height: auto;
        }
        .nav-link {
            color: #fff !important;
            font-weight: 500;
            transition: 0.3s;
        }
        .nav-link:hover {
            color: var(--secondary-color) !important;
        }
        .btn-gold {
            background-color: var(--secondary-color);
            color: var(--primary-color);
            font-weight: 600;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 5px;
            transition: 0.3s;
        }
        .btn-gold:hover {
            background-color: #e6c200;
            transform: translateY(-2px);
        }
        .hero-section {
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('images/hero-bg.jpg');
            background-size: cover;
            background-position: center;
            color: #fff;
            padding: 100px 0;
            text-align: center;
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        .hero-title span {
            color: var(--secondary-color);
        }
        .section-title {
            font-weight: 700;
            margin-bottom: 3rem;
            position: relative;
            padding-bottom: 15px;
        }
        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background-color: var(--secondary-color);
        }
        .product-card {
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: 0.3s;
            border-radius: 15px;
            overflow: hidden;
            height: 100%;
        }
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.15);
        }
        .product-card img {
            height: 200px;
            object-fit: cover;
        }
        .product-price {
            color: var(--secondary-color);
            font-weight: 700;
            font-size: 1.2rem;
        }
        footer {
            background-color: var(--primary-color);
            color: #fff;
            padding: 50px 0 20px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="images/maison-tech-logo.png.jpeg" alt="Maison Tech">
            MAISON TECH
        </a>
        <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="fas fa-bars"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-center">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="shop.php">Products</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About Us</a></li>
                <li class="nav-item"><a class="nav-link" href="order.php">Place Order</a></li>
            </ul>
        </div>
    </div>
</nav>