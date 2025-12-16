<?php
// --- DB CONNECT ---
require_once 'config/db.php';

// --- PAGINATION ---
$limit = 6; 
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Get total for pagination
$total_result = $conn->query("SELECT count(*) as total FROM blogs");
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);
?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <title>MindCare</title>
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
    
    <section class="hero-wrap hero-wrap-2" style="background-image: url('images/bg_5.jpg');" data-stellar-background-ratio="0.5">
      <div class="overlay"></div>
      <div class="container">
        <div class="row no-gutters slider-text align-items-end justify-content-center">
          <div class="col-md-9 ftco-animate mb-5 text-center">
            <p class="breadcrumbs mb-0"><span class="mr-2"><a href="index.php">Home <i class="fa fa-chevron-right"></i></a></span> <span>Blog <i class="fa fa-chevron-right"></i></span></p>
            <h1 class="mb-0 bread">Mental Health Journal - Illness and Wellness</h1>
          </div>
        </div>
      </div>
    </section>

    <section class="ftco-section">
      <div class="container">
        <div class="row d-flex">
          
          <?php
            // Fetch blogs
            $sql = "SELECT * FROM blogs ORDER BY created_at DESC LIMIT $start, $limit";
            $result = $conn->query($sql);

            if ($result->num_rows > 0) {
                while($row = $result->fetch_assoc()) {
                    $title = $row['title'];
                    $content = substr(strip_tags($row['content']), 0, 100) . "...";
                    $date = strtotime($row['created_at']);
                    
                    $img = $row['image']; 
                    // Fallback if image is missing
                    if(empty($img) || !file_exists($img)) { 
                        // You can change this to a default image if you have one
                        $img = 'images/bg_1.jpg'; 
                    }
          ?>
          
          <div class="col-md-4 d-flex ftco-animate">
            <div class="blog-entry justify-content-end">
              <div class="text text-center">
                
                <a href="#" class="block-20 img" style="background-image: url('<?php echo $img; ?>');">
                </a>
                
                <div class="meta text-center mb-2 d-flex align-items-center justify-content-center">
                  <div>
                    <span class="day"><?php echo date('d', $date); ?></span>
                    <span class="mos"><?php echo date('F', $date); ?></span>
                    <span class="yr"><?php echo date('Y', $date); ?></span>
                  </div>
                </div>
                <h3 class="heading mb-3"><a href="#"><?php echo $title; ?></a></h3>
                <p><?php echo $content; ?></p>
              </div>
            </div>
          </div>

          <?php 
                }
            } else {
                echo "<p class='text-center w-100'>No blogs found.</p>";
            }
          ?>
        </div>
        
        <div class="row mt-5">
          <div class="col text-center">
            <div class="block-27">
              <ul>
                <?php if($page > 1): ?>
                    <li><a href="blog.php?page=<?php echo $page-1; ?>">&lt;</a></li>
                <?php endif; ?>
                
                <?php for($i=1; $i<=$total_pages; $i++): ?>
                    <li class="<?php if($page==$i) echo 'active'; ?>"><a href="blog.php?page=<?php echo $i; ?>"><?php echo $i; ?></a></li>
                <?php endfor; ?>
                
                <?php if($page < $total_pages): ?>
                    <li><a href="blog.php?page=<?php echo $page+1; ?>">&gt;</a></li>
                <?php endif; ?>
              </ul>
            </div>
          </div>
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
    <script src="js/google-map.js"></script>
    <script src="js/main.js"></script>
  </body>
</html>