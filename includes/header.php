<style>
    /* CSS for the Professional Login Button */
    .btn-login-header {
        background: #F96D00; /* Theme Orange Color */
        color: #fff !important;
        border-radius: 30px; /* Pill shape */
        padding: 12px 30px !important; /* Larger click area */
        margin-left: 15px; /* Spacing from Contact link */
        font-weight: 600;
        font-size: 14px;
        letter-spacing: 1px;
        text-transform: uppercase;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(249, 109, 0, 0.3); /* Soft shadow */
        border: 2px solid #F96D00;
    }

    .btn-login-header:hover {
        background: transparent;
        color: #F96D00 !important;
        transform: translateY(-2px); /* Slight lift effect */
        box-shadow: 0 6px 20px rgba(249, 109, 0, 0.4);
    }

    /* Mobile Responsive Adjustments */
    @media (max-width: 991.98px) {
        .btn-login-header {
            margin-left: 0;
            margin-top: 10px;
            display: inline-block;
            text-align: center;
        }
    }
</style>

<div class="wrap">
    <div class="container">
        <div class="row">
            <div class="col-md-6 d-flex align-items-center">
                <p class="mb-0 phone pl-md-2">
                    <a href="#"><span class="fa fa-paper-plane mr-1"></span> thecompassionatespace49@gmail.com</a>
                </p>
            </div>
            <div class="col-md-6 d-flex justify-content-md-end">
                <div class="social-media">
                    <p class="mb-0 d-flex">
                        <a href="#" class="d-flex align-items-center justify-content-center"><span class="fa fa-facebook"><i class="sr-only">Facebook</i></span></a>
                        <a href="#" class="d-flex align-items-center justify-content-center"><span class="fa fa-twitter"><i class="sr-only">Twitter</i></span></a>
                        <a href="#" class="d-flex align-items-center justify-content-center"><span class="fa fa-instagram"><i class="sr-only">Instagram</i></span></a>
                        <a href="#" class="d-flex align-items-center justify-content-center"><span class="fa fa-dribbble"><i class="sr-only">Dribbble</i></span></a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <img src="images/logo.png" alt="The Compassionate Space" style="max-height: 70px; width: auto; margin-right: 5px;">
        </a>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="oi oi-menu"></span> Menu
        </button>

        <div class="collapse navbar-collapse" id="ftco-nav">
            <ul class="navbar-nav ml-auto align-items-center">
                <li class="nav-item active"><a href="index.php" class="nav-link">Home</a></li>
                <li class="nav-item"><a href="blog.php" class="nav-link">MindCare</a></li>
                <li class="nav-item"><a href="about.php" class="nav-link">About</a></li>
                <li class="nav-item"><a href="services.php" class="nav-link">Services</a></li>
                <li class="nav-item"><a href="contact.php" class="nav-link">Contact</a></li>

                <?php 
                // Check if the current page is index.php
                // The button will ONLY show if this condition is true
                $current_page = basename($_SERVER['PHP_SELF']);
                if ($current_page == 'index.php'): 
                ?>
                    <li class="nav-item">
                        <a href="login.php" class="nav-link btn-login-header">
                            <i class="fa fa-sign-in mr-1"></i> Login
                        </a>
                    </li>
                <?php endif; ?>
                
            </ul>
        </div>
    </div>
</nav>