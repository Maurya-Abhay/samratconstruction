<?php session_start(); 

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta name="theme-color" content="#0d6efd">
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="icon" href="admin/assets/smrticon.png" type="image/png">
    <title>About Us - Construction Company</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --heading-color: #212529;
            --background-light: #f4f7f9;
            --shadow-subtle: 0 8px 20px rgba(0, 0, 0, 0.08);
            --shadow-premium: 0 15px 40px rgba(0, 0, 0, 0.1);
        }
        body {
            font-family: 'Poppins', sans-serif;
            background-color: var(--background-light);
        }

        /* Hero Section Styling */
        .hero-banner {
            background: linear-gradient(135deg, #0d6efd, #0056b3);
            color: #fff;
            padding: 100px 0;
            border-bottom-left-radius: 60px;
            border-bottom-right-radius: 60px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 50px;
        }

        /* About Main Section */
        .about-section-content {
            padding: 60px 0;
        }
        .about-image {
            border-radius: 12px;
            box-shadow: var(--shadow-premium);
            transition: transform 0.3s ease-in-out;
        }
        .about-image:hover {
            transform: scale(1.02);
        }
        .about-text h3 {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--heading-color);
            border-left: 5px solid var(--primary-color);
            padding-left: 15px;
            margin-bottom: 20px;
        }
        .about-text p {
            font-size: 1rem;
            color: var(--secondary-color);
            line-height: 1.7;
            margin-bottom: 20px;
        }
        .highlight-list {
            list-style: none;
            padding-left: 0;
            margin-top: 15px;
        }
        .highlight-list li {
            font-size: 1rem;
            color: var(--heading-color);
            margin-bottom: 12px;
            font-weight: 500;
        }
        .highlight-list li i {
            color: #198754; /* Success Green */
            font-size: 1.2rem;
            margin-right: 10px;
        }

        /* Call to Action Button */
        .btn-premium {
            background: linear-gradient(90deg, #198754, #0f5132);
            color: #fff;
            border: none;
            border-radius: 50px; /* Pill shape */
            padding: 12px 30px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(25, 135, 84, 0.5);
            transition: all 0.3s ease;
        }
        .btn-premium:hover {
            background: linear-gradient(90deg, #0f5132, #198754);
            box-shadow: 0 8px 20px rgba(25, 135, 84, 0.7);
            transform: translateY(-2px);
            color: #fff;
        }
        
        /* Stats/Fact Card Section */
        .fact-card {
            background: #fff;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow-subtle);
            transition: transform 0.3s ease;
            height: 100%;
        }
        .fact-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-premium);
        }
        .fact-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 15px;
        }
        .fact-count {
            font-size: 2.5rem;
            font-weight: 800;
            color: var(--heading-color);
            line-height: 1;
        }
        .fact-title {
            font-size: 1rem;
            color: var(--secondary-color);
            font-weight: 600;
            margin-top: 5px;
        }

    </style>
</head>
<body>

    <?php include "header.php"; ?>

    <div class="container-fluid hero-banner">
        <div class="container text-center">
            <h1 class="display-3 fw-bolder mb-3">Building Trust, Delivering Excellence</h1>
            <p class="lead text-white-50">
                Explore our journey, our commitment to quality, and the values that drive our construction projects.
            </p>
            <i class="bi bi-chevron-down mt-4 d-block fs-3 text-white-50"></i>
        </div>
    </div>

    <div class="container about-section-content">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 order-lg-2">
                <img class="img-fluid about-image" src="img/004.png" alt="Our Team Image" loading="lazy" />
            </div>
            
            <div class="col-lg-6 order-lg-1 about-text">
                <p class="text-uppercase text-primary fw-bold small mb-2">Since 2015</p>
                <h3 class="mb-4">The Experts in Sustainable Construction</h3>
                
                <p>With over a decade of dedication, our company has established itself as a leader in providing robust and sustainable construction solutions for residential, commercial, and industrial sectors.</p>

                <p>We combine innovative engineering with a client-centric approach, ensuring transparency and collaboration from the initial blueprint to the final handover. Your vision is our blueprint for success.</p>

                <ul class="highlight-list">
                    <li><i class="bi bi-check-circle-fill"></i> Experienced and skilled professionals delivering high standards.</li>
                    <li><i class="bi bi-check-circle-fill"></i> Timely project completion with transparent milestone tracking.</li>
                    <li><i class="bi bi-check-circle-fill"></i> Commitment to sustainable and eco-friendly building practices.</li>
                </ul>

                <a class="btn btn-premium mt-4" href="contacts.php">
                    <i class="bi bi-telephone-fill me-2"></i> Discuss Your Project
                </a>
            </div>
        </div>
    </div>
    
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold display-6 text-dark">Our Achievements in Numbers</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="fact-card">
                    <div class="fact-icon"><i class="bi bi-trophy-fill"></i></div>
                    <div class="fact-count">10+</div>
                    <div class="fact-title">Years of Experience</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="fact-card">
                    <div class="fact-icon"><i class="bi bi-building-check"></i></div>
                    <div class="fact-count">250+</div>
                    <div class="fact-title">Projects Completed</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="fact-card">
                    <div class="fact-icon"><i class="bi bi-people-fill"></i></div>
                    <div class="fact-count">100%</div>
                    <div class="fact-title">Client Satisfaction Rate</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="fact-card">
                    <div class="fact-icon"><i class="bi bi-helmet-fill"></i></div>
                    <div class="fact-count">5K+</div>
                    <div class="fact-title">Skilled Workforce</div>
                </div>
            </div>
        </div>
    </div>


    <?php include "footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>