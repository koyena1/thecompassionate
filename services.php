<?php 
// Include database connection
include 'config/db.php'; 
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Services</title>
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

    <style>
      .service-card {
        background: #fff;
        border-radius: 8px;
        box-shadow: 0px 5px 20px rgba(0, 0, 0, 0.05);
        overflow: hidden;
        margin-bottom: 40px; 
        transition: all 0.3s ease;
        border: 1px solid #f0f0f0;
        height: 100%;
        min-height: 450px;
        position: relative;
        display: flex;
        flex-direction: column;
      }

      .service-card:hover {
        transform: translateY(-5px);
        box-shadow: 0px 15px 30px rgba(0, 0, 0, 0.1);
      }

      .service-card .img {
        height: 220px;
        width: 100%;
        background-size: cover;
        background-position: center;
        position: relative;
        flex-shrink: 0;
      }

      /* Floating Icon Circle */
      .service-card .icon-holder {
        position: absolute;
        bottom: -25px;
        left: 25px;
        width: 50px;
        height: 50px;
        background: #fff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        z-index: 2;
      }
      
      .service-card .icon-holder i {
        font-size: 20px;
        color: #F96D00; /* Orange accent color */
      }

      .service-card .text {
        padding: 40px 25px 25px 25px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
      }

      .service-card h2 {
        font-size: 18px; 
        font-weight: 700;
        margin-bottom: 15px;
        color: #000;
        line-height: 1.4;
      }

      .service-card p {
        color: #666;
        font-size: 15px;
        line-height: 1.6;
        margin-bottom: 20px;
        flex-grow: 1;
      }

      .service-card .read-more {
        font-weight: 600;
        font-size: 14px;
        color: #007bff;
        text-decoration: none;
        margin-top: auto; 
        display: inline-block;
        cursor: pointer;
      }
      
      .service-card .read-more:hover {
        color: #F96D00;
      }

      /* Ensure equal height columns */
      .row.service-row {
        display: flex;
        flex-wrap: wrap;
      }

      .row.service-row > [class*='col-'] {
        display: flex;
        margin-bottom: 30px;
      }

      /* --- Modal Styles (Added for Popup) --- */
      .modal-service-img {
          width: 100%;
          height: 350px; /* Large header image like screenshot */
          object-fit: cover;
          border-top-left-radius: 4px;
          border-top-right-radius: 4px;
      }

      .modal-body {
          padding: 30px;
      }

      .modal-title {
          font-weight: 700;
          color: #000;
          margin-bottom: 15px;
          font-size: 24px;
      }

      .modal-text {
          font-size: 16px;
          color: #555;
          line-height: 1.8;
      }

      .btn-close-modal {
          background-color: #F96D00; /* Theme Orange */
          color: white;
          border: none;
          padding: 10px 30px;
          font-weight: 600;
          border-radius: 4px;
          cursor: pointer;
          transition: 0.3s;
      }

      .btn-close-modal:hover {
          background-color: #d85d00;
          text-decoration: none;
          color: #fff;
      }
    </style>
  </head>
  <body>

    <?php include 'includes/header.php'; ?>
    
    <section class="hero-wrap hero-wrap-2" style="background-image: url('images/hero.jpg');" data-stellar-background-ratio="0.5">
      <div class="overlay"></div>
      <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
          <div class="col-md-9 ftco-animate mb-5 text-center">
            <p class="breadcrumbs mb-0"><span class="mr-2"><a href="index.php">Home <i class="fa fa-chevron-right"></i></a></span> <span>Services <i class="fa fa-chevron-right"></i></span></p>
            <h1 class="mb-0 bread">Services</h1>
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
                            <div class="icon d-flex align-items-center justify-content-center">
                                <span class="flaticon-calendar"></span>
                            </div>
                        </div>
                        <h2>Make Schedule</h2>
                        <p>A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-stretch ftco-animate">
                    <div class="services-2 text-center">
                        <div class="icon-wrap">
                            <div class="number d-flex align-items-center justify-content-center"><span>02</span></div>
                            <div class="icon d-flex align-items-center justify-content-center">
                                <span class="flaticon-qa"></span>
                            </div>
                        </div>
                        <h2>Start Discussion</h2>
                        <p>A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
                    </div>
                </div>
                <div class="col-md-4 d-flex align-items-stretch ftco-animate">
                    <div class="services-2 text-center">
                        <div class="icon-wrap">
                            <div class="number d-flex align-items-center justify-content-center"><span>03</span></div>
                            <div class="icon d-flex align-items-center justify-content-center">
                                <span class="flaticon-checklist"></span>
                            </div>
                        </div>
                        <h2>Enjoy Plan</h2>
                        <p>A small river named Duden flows by their place and supplies it with the necessary regelialia.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="ftco-section bg-light">
        <div class="container">
            <div class="row justify-content-center pb-5">
                <div class="col-md-10 heading-section text-center ftco-animate">
                    <span class="subheading">Services</span>
                    <h2>We Can Help You With This Situation</h2>
                </div>
            </div>
            
            <div class="row service-row">
                <?php
                // Fetch services from the database
                $sql = "SELECT * FROM services ORDER BY id ASC";
                $result = $conn->query($sql);

                if ($result && $result->num_rows > 0) {
                    while($row = $result->fetch_assoc()) {
                        // Check if 'icon' column exists and has value, else default
                        $iconClass = (isset($row['icon']) && !empty($row['icon'])) ? $row['icon'] : 'fa-heart';
                        
                        // Handle image path
                        $imagePath = !empty($row['image']) ? $row['image'] : 'images/default.jpg';
                        
                        // Prepare data for modal (escape quotes to prevent HTML breaking)
                        $modalTitle = htmlspecialchars($row['title'], ENT_QUOTES);
                        $modalDesc = htmlspecialchars($row['description'], ENT_QUOTES);
                        $modalImg = htmlspecialchars($imagePath, ENT_QUOTES);
                ?>
                <div class="col-md-4 d-flex ftco-animate">
                    <div class="service-card">
                        <div class="img" style="background-image: url('<?php echo $imagePath; ?>');">
                            <div class="icon-holder">
                                <i class="fa <?php echo htmlspecialchars($iconClass); ?>"></i>
                            </div>
                        </div>
                        <div class="text">
                            <h2><?php echo htmlspecialchars($row['title']); ?></h2>
                            <p><?php echo substr(strip_tags($row['description']), 0, 100) . '...'; ?></p>
                            
                            <a class="read-more open-modal-btn" 
                               data-title="<?php echo $modalTitle; ?>" 
                               data-desc="<?php echo $modalDesc; ?>" 
                               data-img="<?php echo $modalImg; ?>">
                               Read More +
                            </a>
                        </div>
                    </div>
                </div>
                <?php 
                    }
                } else {
                    echo '<div class="col-12 text-center"><p>No services found or database connection issue.</p></div>';
                }
                ?>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>
    
    <div class="modal fade" id="serviceModal" tabindex="-1" role="dialog" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document"> 
        <div class="modal-content">
          
          <div class="img-container">
              <img src="" id="modalImage" class="modal-service-img" alt="Service Image">
          </div>

          <div class="modal-body">
            <h3 id="modalTitle" class="modal-title"></h3>
            
            <div id="modalDescription" class="modal-text"></div>
            
            <div class="text-right mt-4">
                <button type="button" class="btn btn-close-modal" data-dismiss="modal">CLOSE</button>
            </div>
          </div>

        </div>
      </div>
    </div>
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

  <script>
    $(document).ready(function(){
        $('.open-modal-btn').on('click', function(){
            var title = $(this).data('title');
            var desc = $(this).data('desc');
            var img = $(this).data('img');

            $('#modalTitle').text(title);
            $('#modalImage').attr('src', img);
            // Replace newlines with <br> for proper formatting
            $('#modalDescription').html(desc.replace(/\n/g, '<br>'));

            $('#serviceModal').modal('show');
        });
    });
  </script>
    
  </body>
</html>