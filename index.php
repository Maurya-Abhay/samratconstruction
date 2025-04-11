<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="css//bootstrap.min.css">
    <script src="js/bootstrap.bundle.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
.logo-container {
    width: 200px;
    height: 60px;
    overflow: hidden;
    background-color: #000;
}

.logo-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    mix-blend-mode: lighten;
}

</style>
</head>

<body>
    <?php
    include "header.php";
    ?>

    <div class="container-fluid p-0">
        <div id="header-carousel" class="carousel slide carousel-fade" data-bs-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img class="w-100 custom-carousel-height" src="img/carousel-1.jpg" alt="Image">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h1 class="display-5 text-uppercase text-primary mb-md-4">Build Your Dream House With Us</h1>
                            <a href="" class="btn btn-primary">Get A Quote</a>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <img class="w-100 custom-carousel-height" src="img/carousel-2.jpg" alt="Image">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h1 class="display-5 text-uppercase text-primary mb-md-4">We Are Trusted For Your Project</h1>
                            <a href="" class="btn btn-primary">Contact Us</a>
                        </div>
                    </div>
                </div>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#header-carousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>

    <style>
        .custom-carousel-height {
            height: 65vh;
        }

        @media (max-width: 576px) {
            .custom-carousel-height {
                height: auto;
            }
        }
    </style>


    <div class="container-fluid bg-light py-6 px-5">
        <div class="text-center mx-auto mb-5">
            <h1 class="display-5 text-uppercase mb-4">We Provide <span class="text-primary">The Best</span> Construction
                Services</h1>
        </div>
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="service-item bg-white d-flex flex-column align-items-center text-center">
                    <img class="img-fluid" src="img/service-1.jpg" alt="">
                    <div class="service-icon bg-white">
                        <i class="fa fa-3x fa-building text-primary"></i>
                    </div>
                    <div class="px-4 pb-4">
                        <h4 class="text-uppercase mb-3">Building Construction</h4>
                        <p>Duo dolore et diam sed ipsum stet amet duo diam. Rebum amet ut amet sed erat sed sed amet
                            magna elitr amet kasd diam duo</p>
                        <a class="btn text-primary" href="">Read More <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-item bg-white rounded d-flex flex-column align-items-center text-center">
                    <img class="img-fluid" src="img/service-2.jpg" alt="">
                    <div class="service-icon bg-white">
                        <i class="fa fa-3x fa-home text-primary"></i>
                    </div>
                    <div class="px-4 pb-4">
                        <h4 class="text-uppercase mb-3">House Renovation</h4>
                        <p>Duo dolore et diam sed ipsum stet amet duo diam. Rebum amet ut amet sed erat sed sed amet
                            magna elitr amet kasd diam duo</p>
                        <a class="btn text-primary" href="">Read More <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-item bg-white rounded d-flex flex-column align-items-center text-center">
                    <img class="img-fluid" src="img/service-3.jpg" alt="">
                    <div class="service-icon bg-white">
                        <i class="fa fa-3x fa-drafting-compass text-primary"></i>
                    </div>
                    <div class="px-4 pb-4">
                        <h4 class="text-uppercase mb-3">Architecture Design</h4>
                        <p>Duo dolore et diam sed ipsum stet amet duo diam. Rebum amet ut amet sed erat sed sed amet
                            magna elitr amet kasd diam duo</p>
                        <a class="btn text-primary" href="">Read More <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-item bg-white rounded d-flex flex-column align-items-center text-center">
                    <img class="img-fluid" src="img/service-4.jpg" alt="">
                    <div class="service-icon bg-white">
                        <i class="fa fa-3x fa-palette text-primary"></i>
                    </div>
                    <div class="px-4 pb-4">
                        <h4 class="text-uppercase mb-3">Design-Build</h4>
                        <p>Duo dolore et diam sed ipsum stet amet duo diam. Rebum amet ut amet sed erat sed sed amet
                            magna elitr amet kasd diam duo</p>
                        <a class="btn text-primary" href="">Read More <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-item bg-white d-flex flex-column align-items-center text-center">
                    <img class="img-fluid" src="img/service-1.jpg" alt="">
                    <div class="service-icon bg-white">
                        <i class="fa fa-3x fa-building text-primary"></i>
                    </div>
                    <div class="px-4 pb-4">
                        <h4 class="text-uppercase mb-3">Building Construction</h4>
                        <p>Duo dolore et diam sed ipsum stet amet duo diam. Rebum amet ut amet sed erat sed sed amet
                            magna elitr amet kasd diam duo</p>
                        <a class="btn text-primary" href="">Read More <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-item bg-white rounded d-flex flex-column align-items-center text-center">
                    <img class="img-fluid" src="img/service-2.jpg" alt="">
                    <div class="service-icon bg-white">
                        <i class="fa fa-3x fa-home text-primary"></i>
                    </div>
                    <div class="px-4 pb-4">
                        <h4 class="text-uppercase mb-3">House Renovation</h4>
                        <p>Duo dolore et diam sed ipsum stet amet duo diam. Rebum amet ut amet sed erat sed sed amet
                            magna elitr amet kasd diam duo</p>
                        <a class="btn text-primary" href="">Read More <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="service-item bg-white rounded d-flex flex-column align-items-center text-center">
                    <img class="img-fluid" src="img/service-3.jpg" alt="">
                    <div class="service-icon bg-white">
                        <i class="fa fa-3x fa-drafting-compass text-primary"></i>
                    </div>
                    <div class="px-4 pb-4">
                        <h4 class="text-uppercase mb-3">Architecture Design</h4>
                        <p>Duo dolore et diam sed ipsum stet amet duo diam. Rebum amet ut amet sed erat sed sed amet
                            magna elitr amet kasd diam duo</p>
                        <a class="btn text-primary" href="">Read More <i class="bi bi-arrow-right"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="container-fluid py-6 px-5 mt-2 bg-light p-2">
        <div class="row gx-5">
            <div class="col-lg-6 col-md-6 col-12 mb-4 mb-md-0">
                <div class="modal-body pe-lg-5 pt-lg-5">
                    <iframe
                        src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3276.550198467233!2d84.53867131177725!3d26.016885611804767!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3992eb3820e83c4d%3A0x5da7067c905f0274!2sGuddu%20General%20store!5e0!3m2!1sen!2sin!4v1743092997865!5m2!1sen!2sin"
                        width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
            <div class="col-lg-6 col-md-6 col-12">
                <form action="" class="bg-light-radial w-100 p-4">
                    <div class="modal-body">
                        <input type="email" placeholder="Enter your Email" class="form-control p-2 m-2">
                        <input type="text" placeholder="Enter your Name" class="form-control p-2 m-2">
                        <input type="number" placeholder="Enter your Number" class="form-control p-2 m-2">
                        <input type="text" placeholder="Enter your Subject" class="form-control p-2 m-2">
                        <button type="submit" class="btn btn-primary w-100 py-2 mt-3">Send</button>
                    </div>
                </form>
            </div>
        </div>
    </div>



    <br><br><br>
    <?php
    include "footer.php";
    ?>
</body>

</html>