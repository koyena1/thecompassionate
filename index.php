<?php
// 1. DATABASE CONNECTION
require_once 'config/db.php';
// --- VISITOR TRACKING LOGIC ---
$visitor_ip = $_SERVER['REMOTE_ADDR'];
$visit_date = date('Y-m-d');

// Insert or ignore if the IP has already visited today to ensure "Unique Daily Visitors"
$stmt = $conn->prepare("INSERT IGNORE INTO site_visitors (ip_address, visit_date) VALUES (?, ?)");
$stmt->bind_param("ss", $visitor_ip, $visit_date);
$stmt->execute();
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>The Compassionate Space</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/animate.css">
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/owl.theme.default.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/style.css">
  </head>
  <body>

<?php include 'includes/header.php'; ?>

    <div class="hero-wrap" style="background-image: url('images/hero1.jpg');" data-stellar-background-ratio="0.5">
      <div class="overlay"></div>
      <div class="container">
        <div class="row no-gutters slider-text align-items-center">
          <div class="col-md-6 ftco-animate d-flex align-items-end">
            <div class="text w-100">
                <h1 class="mb-4">Counseling For Your Better Life</h1>
                <p class="mb-4">A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
                <p><a href="#" class="btn btn-primary py-3 px-4">Contact us</a> <a href="#" class="btn btn-white py-3 px-4">Read more</a></p>
            </div>
          </div>
          <a href="https://vimeo.com/45830194" class="img-video popup-vimeo d-flex align-items-center justify-content-center">
            <span class="fa fa-play"></span>
          </a>
        </div>
      </div>
    </div>

    <section class="ftco-intro">
        <div class="container">
            <div class="row no-gutters">
                <div class="col-md-4 d-flex">
                    <div class="intro aside-stretch d-lg-flex w-100">
                        <div class="icon"><span class="flaticon-checklist"></span></div>
                        <div class="text">
                            <h2>100% Confidential</h2>
                            <p>A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-flex">
                    <div class="intro color-1 d-lg-flex w-100">
                        <div class="icon"><span class="flaticon-employee"></span></div>
                        <div class="text">
                            <h2>Qualified Team</h2>
                            <p>A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 d-flex">
                    <div class="intro color-2 d-lg-flex w-100">
                        <div class="icon"><span class="flaticon-umbrella"></span></div>
                        <div class="text">
                            <h2>Individual Approach</h2>
                            <p>A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ftco-section">
        <div class="container">
            <div class="row justify-content-center pb-5">
              <div class="col-md-7 heading-section text-center ftco-animate">
                <span class="subheading">Services</span>
                <h2>How It Works</h2>
              </div>
            </div>
            <div class="row">
                <div class="col-md-4 d-flex align-items-stretch ftco-animate">
                    <div class="services-2 text-center">
                        <div class="icon-wrap">
                            <div class="number d-flex align-items-center justify-content-center"><span>01</span></div>
                            <div class="icon d-flex align-items-center justify-content-center"><span class="flaticon-calendar"></span></div>
                        </div>
                        <h2>Make Schedule</h2>
                        <p>A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-stretch ftco-animate">
                    <div class="services-2 text-center">
                        <div class="icon-wrap">
                            <div class="number d-flex align-items-center justify-content-center"><span>02</span></div>
                            <div class="icon d-flex align-items-center justify-content-center"><span class="flaticon-qa"></span></div>
                        </div>
                        <h2>Start Discussion</h2>
                        <p>A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-stretch ftco-animate">
                    <div class="services-2 text-center">
                        <div class="icon-wrap">
                            <div class="number d-flex align-items-center justify-content-center"><span>03</span></div>
                            <div class="icon d-flex align-items-center justify-content-center"><span class="flaticon-checklist"></span></div>
                        </div>
                        <h2>Enjoy Plan</h2>
                        <p>A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php
    $services_data = [];
    $res = $conn->query("SELECT * FROM services");
    if($res->num_rows > 0) {
        while($row = $res->fetch_assoc()) { $services_data[] = $row; }
    }
    ?>
    <section class="ftco-section">
        <div class="container">
            <div class="row justify-content-center mb-5">
              <div class="col-md-8 text-center heading-section ftco-animate">
                <span class="subheading">Our Services</span>
                <h2 class="mb-3">We Can Help You With This Situation</h2>
              </div>
            </div>
            
            <div class="row tabulation mt-4 ftco-animate">
                <div class="col-md-4">
                    <ul class="nav nav-pills nav-fill d-md-flex d-block flex-column">
                      <?php foreach($services_data as $index => $s): ?>
                          <li class="nav-item text-left">
                            <a class="nav-link <?php echo ($index == 0) ? 'active' : ''; ?> py-4" data-toggle="tab" href="#<?php echo $s['tab_id']; ?>">
                                <?php echo $s['title']; ?>
                            </a>
                          </li>
                      <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-md-8">
                    <div class="tab-content">
                      <?php foreach($services_data as $index => $s): ?>
                          <div class="tab-pane container p-0 <?php echo ($index == 0) ? 'active' : 'fade'; ?>" id="<?php echo $s['tab_id']; ?>">
                            <div class="img" style="background-image: url(<?php echo $s['image']; ?>);"></div>
                            <h3><a href="#"><?php echo $s['title']; ?></a></h3>
                            <p><?php echo $s['description']; ?></p>
                          </div>
                      <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ftco-section testimony-section">
        <div class="img img-bg border" style="background-image: url(images/bg_4.jpg);"></div>
        <div class="overlay"></div>
      <div class="container">
        <div class="row justify-content-center mb-5">
          <div class="col-md-7 text-center heading-section heading-section-white ftco-animate">
            <span class="subheading">Motivation</span>
            <h2 class="mb-3">The Motivation Corner</h2>
          </div>
        </div>
        <div class="row ftco-animate">
          <div class="col-md-12">
            <div class="carousel-testimony owl-carousel ftco-owl">
              
              <?php
                // Query to fetch quotes
                $quotes_sql = $conn->query("SELECT * FROM motivational_quotes ORDER BY id DESC");
                
                // Check if query returns rows
                if($quotes_sql && $quotes_sql->num_rows > 0):
                    while($q = $quotes_sql->fetch_assoc()):
              ?>
              <div class="item">
                <div class="testimony-wrap py-4">
                    <div class="icon d-flex align-items-center justify-content-center"><span class="fa fa-quote-left"></span></div>
                  <div class="text">
                    <p class="mb-4" style="font-size: 1.25rem; font-style: italic; color: #333;">
                        "<?php echo htmlspecialchars($q['quote_content']); ?>"
                    </p>
                  </div>
                </div>
              </div>
              <?php 
                    endwhile;
                else:
                    // FALLBACK IF EMPTY
              ?>
                <div class="item">
                    <div class="testimony-wrap py-4">
                        <div class="icon d-flex align-items-center justify-content-center"><span class="fa fa-quote-left"></span></div>
                        <div class="text">
                            <p class="mb-4" style="font-size: 1.25rem; font-style: italic; color: #333;">
                                "The best way to predict the future is to create it."
                            </p>
                        </div>
                    </div>
                </div>
              <?php endif; ?>

            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="ftco-section bg-light">
        <div class="container">
            <div class="row justify-content-center pb-5 mb-3">
              <div class="col-md-7 heading-section text-center ftco-animate">
                <span class="subheading">Price &amp; Plans</span>
                <h2>Affordable Packages</h2>
              </div>
            </div>
            <div class="row">
                <div class="col-md-4 ftco-animate d-flex">
                  <div class="block-7 w-100">
                    <div class="text-center">
                        <span class="price"><sup>$</sup> <span class="number">49</span> <sub>/mo</sub></span>
                        <span class="excerpt d-block">For Adults</span>
                        <ul class="pricing-text mb-5">
                          <li><span class="fa fa-check mr-2"></span>Individual Counseling</li>
                          <li><span class="fa fa-check mr-2"></span>Couples Therapy</li>
                          <li><span class="fa fa-check mr-2"></span>Family Therapy</li>
                        </ul>
                        <a href="upi://pay?pa=YOUR_UPI_ID_HERE&pn=TheCompassionateSpace&am=49&cu=INR" class="btn btn-primary d-block px-2 py-3">Get Started</a>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 ftco-animate d-flex">
                  <div class="block-7 w-100">
                    <div class="text-center">
                        <span class="price"><sup>$</sup> <span class="number">79</span> <sub>/mo</sub></span>
                        <span class="excerpt d-block">For Children</span>
                        <ul class="pricing-text mb-5">
                          <li><span class="fa fa-check mr-2"></span>Counseling for Children</li>
                          <li><span class="fa fa-check mr-2"></span>Behavioral Management</li>
                          <li><span class="fa fa-check mr-2"></span>Educational Counseling</li>
                        </ul>
                        <a href="upi://pay?pa=YOUR_UPI_ID_HERE&pn=TheCompassionateSpace&am=79&cu=INR" class="btn btn-primary d-block px-2 py-3">Get Started</a>
                    </div>
                  </div>
                </div>
                <div class="col-md-4 ftco-animate d-flex">
                  <div class="block-7 w-100">
                    <div class="text-center">
                        <span class="price"><sup>$</sup> <span class="number">109</span> <sub>/mo</sub></span>
                        <span class="excerpt d-block">For Business</span>
                        <ul class="pricing-text mb-5">
                          <li><span class="fa fa-check mr-2"></span>Consultancy Services</li>
                          <li><span class="fa fa-check mr-2"></span>Employee Counseling</li>
                          <li><span class="fa fa-check mr-2"></span>Psychological Assessment</li>
                        </ul>
                        <a href="upi://pay?pa=YOUR_UPI_ID_HERE&pn=TheCompassionateSpace&am=109&cu=INR" class="btn btn-primary d-block px-2 py-3">Get Started</a>
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </section>
        
    <section class="ftco-appointment ftco-section img" style="background-image: url(images/bg_2.jpg);">
        <div class="overlay"></div>
        <div class="container">
            <div class="row">
                <div class="col-md-6 half ftco-animate">
                    <h2 class="mb-4">Send a Message &amp; Get in touch!</h2>
                    
                    <form action="#" class="appointment" onsubmit="sendToWhatsapp(event)">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="text" id="w_name" class="form-control" placeholder="Your Name" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <input type="text" id="w_email" class="form-control" placeholder="Email" required>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <div class="form-field">
                                        <div class="select-wrap">
                                            <div class="icon"><span class="fa fa-chevron-down"></span></div>
                                            <select id="w_service" class="form-control">
                                                <option value="General Inquiry">Services</option>
                                                <?php foreach($services_data as $s): ?>
                                                    <option value="<?php echo $s['title']; ?>"><?php echo $s['title']; ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <textarea id="w_message" cols="30" rows="7" class="form-control" placeholder="Message" required></textarea>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <input type="submit" value="Send message" class="btn btn-primary py-3 px-4">
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <script>
    function sendToWhatsapp(e) {
        e.preventDefault(); 
        var name = document.getElementById('w_name').value;
        var email = document.getElementById('w_email').value;
        var service = document.getElementById('w_service').value;
        var msg = document.getElementById('w_message').value;
        
        var formattedMsg = "*New Inquiry from Website*%0a" + 
                           "Name: " + name + "%0a" + 
                           "Email: " + email + "%0a" + 
                           "Service: " + service + "%0a" + 
                           "Message: " + msg;
                           
        var phone = "916294880595"; // Your Number
        window.open("https://wa.me/" + phone + "?text=" + formattedMsg, '_blank');
    }
    </script>

    <section class="ftco-section">
      <div class="container">
        <div class="row justify-content-center mb-5">
          <div class="col-md-7 heading-section text-center ftco-animate">
            <span class="subheading">Blog</span>
            <h2>Recent Blog</h2>
          </div>
        </div>
        <div class="row d-flex">
          <?php
            // Fetch Latest 3 Blogs
            $blogs = $conn->query("SELECT * FROM blogs ORDER BY id DESC LIMIT 3");
            if($blogs && $blogs->num_rows > 0):
                while($b = $blogs->fetch_assoc()):
                    $d = date("d", strtotime($b['created_at']));
                    $m = date("F", strtotime($b['created_at']));
                    $y = date("Y", strtotime($b['created_at']));
          ?>
          <div class="col-md-4 d-flex ftco-animate">
            <div class="blog-entry justify-content-end">
              <div class="text text-center">
                <a href="#" class="block-20 img" style="background-image: url('<?php echo $b['image']; ?>');">
                </a>
                <div class="meta text-center mb-2 d-flex align-items-center justify-content-center">
                    <div>
                        <span class="day"><?php echo $d; ?></span>
                        <span class="mos"><?php echo $m; ?></span>
                        <span class="yr"><?php echo $y; ?></span>
                    </div>
                </div>
                <h3 class="heading mb-3"><a href="#"><?php echo $b['title']; ?></a></h3>
                <p><?php echo substr($b['content'], 0, 90) . '...'; ?></p>
              </div>
            </div>
          </div>
          <?php 
                endwhile;
            endif;
          ?>
        </div>
      </div>
    </section> 
   
<?php include 'includes/footer.php'; ?>
    
  <div id="ftco-loader" class="show fullscreen"><svg class="circular" width="48px" height="48px"><circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke="#eeeeee"/><circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke-miterlimit="10" stroke="#F96D00"/></svg></div>

  <script src="js/jquery.min.js"></script>
  <script src="js/jquery-migrate-3.0.1.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/jquery.easing.1.3.js"></script>
  <script src="js/jquery.waypoints.min.js"></script>
  <script src="js/jquery.stellar.min.js"></script>
  <script src="js/owl.carousel.min.js"></script>
  <script src="js/jquery.magnific-popup.min.js"></script>
  <script src="js/jquery.animateNumber.min.js"></script>
  <script src="js/scrollax.min.js"></script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBVWaKrjvy3MaE7SQ74_uJiULgl1JY0H2s&sensor=false"></script>
  <script src="js/google-map.js"></script>
  <script src="js/main.js"></script>
    
  </body>
</html>