<?php
include '../includes/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
  $user_id = $_SESSION['user_id'];

  // Fetch user name for display
  $select_user = $conn->prepare("SELECT first_name, profile_picture FROM users WHERE id = ?");
  $select_user->execute([$user_id]);
  $fetch_user = $select_user->fetch(PDO::FETCH_ASSOC);
  $user_name = $fetch_user['first_name'] ?? 'Cookie Lover';
  $profile_picture_path = $fetch_user['profile_picture'] ?? '../assets/images/default-cookie.png';
} else {
  $user_id = '';
  $user_name = 'Guest';
  $profile_picture_path = '../assets/images/default-cookie.png';
}

if (isset($_SESSION['unauthorized'])) {
  echo "<script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>";
  echo "<script>
      document.addEventListener('DOMContentLoaded', function() {
          Swal.fire({
              icon: 'error',
              title: 'Access Denied',
              text: 'You are not authorized to access the admin panel.',
              confirmButtonText: 'OK'
          });
      });
  </script>";
  unset($_SESSION['unauthorized']);
}

if (isset($_POST['logout'])) {
  session_unset(); // Unset all session variables
  session_destroy(); // Destroy the session
  header('Location: index.php');
  exit;
}

// Fetch active discounts
$today = date('Y-m-d');
$select_discounts = $conn->prepare("SELECT * FROM `discounts` WHERE `end_date` >= ? ORDER BY start_date DESC");
$select_discounts->execute([$today]);
$discounts = $select_discounts->fetchAll(PDO::FETCH_ASSOC);
?>

<?php $page = 'home'; ?> <!-- Change per page -->

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Tender Loving Cookies - Fresh Baked Cookies</title>
  <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
  <link rel="stylesheet" href="../assets/css/styles.css" />
</head>

<body>
  <?php include '../includes/header.php'; ?>

  <div class="main">
    <section class="home-section">
      <div class="slide-wrapper">
        <div class="slider">

          <!-- Static Welcome Slide - Cookie Store Style -->
          <div class="slider__slider slide0">
            <div class="overlay"></div>
            <div class="slide-detail">
              <h1>Welcome to TLC</h1>
              <a href="view_profile.php">
                <img src="<?= $profile_picture_path ?>" alt="Profile Picture" class="logo">
              </a>
              <p><?= $user_name ?> 🍪</p>
              <p>Freshly Baked Daily</p>
              <a href="products.php" class="btn">Shop Now</a>
            </div>
            <div class="hero-dec-top"></div>
            <div class="hero-dec-bottom"></div>
          </div>

          <!-- Check if there are admin-added discounts -->
          <?php if (count($discounts) > 0): ?>
            <!-- Dynamic Slides for Admin-Added Discounts -->
            <?php
            $slide_number = 1;
            foreach ($discounts as $discount):
              // Limit to 3 discount slides maximum to avoid too many slides
              if ($slide_number > 3) break;
            ?>
              <div class="slider__slider slide<?= $slide_number ?>"
                style="background-image: url('../admin/<?= htmlspecialchars($discount['image']); ?>');">
                <div class="overlay"></div>
                <div class="slide-detail">
                  <h1><?= htmlspecialchars($discount['title']); ?></h1>
                  <p><?= htmlspecialchars($discount['description']); ?></p>
                  <p><strong>🎉 Special Offer:</strong> <?= $discount['discount_percentage']; ?>% OFF</p>
                  <p class="offer-dates">Valid until: <?= date('M d, Y', strtotime($discount['end_date'])); ?></p>
                  <a href="products.php" class="btn">Grab This Deal</a>
                </div>
                <div class="hero-dec-top"></div>
                <div class="hero-dec-bottom"></div>
              </div>
            <?php
              $slide_number++;
            endforeach;
            ?>
          <?php else: ?>
            <!-- Default "Coming Soon" Slide when no discounts are active -->
            <div class="slider__slider slide3">
              <div class="overlay"></div>
              <div class="slide-detail">
                <h1>🍪 New Cookie Flavors Coming Soon!</h1>
                <p>We're baking up something special</p>
                <p>Sign up for notifications and be the first to know</p>
                <div class="coming-soon-features">
                  <span class="feature-tag">✨ Double Chocolate</span>
                  <span class="feature-tag">🍯 Honey Butter</span>
                  <span class="feature-tag">🥜 Salted Caramel</span>
                </div>
                <a href="products.php" class="btn">Browse Current Flavors</a>
              </div>
              <div class="hero-dec-top"></div>
              <div class="hero-dec-bottom"></div>
            </div>
          <?php endif; ?>

        </div>
        <div class="left-arrow"><i class="bx bxs-left-arrow"></i></div>
        <div class="right-arrow"><i class="bx bxs-right-arrow"></i></div>
      </div>
    </section>

    <!-- Cookie Collections Section (Now just product grid without categories) -->
    <section class="cookie-categories">
      <div class="section-title">
        <h2>Our Cookie Collection</h2>
      </div>
      <div class="category-grid">
        <div class="category-card">
          <div class="category-img">
            <img src="../assets/images/chocolate-chip.png" alt="Chocolate Chip Cookies">
          </div>
          <h3>Chocolate Chip</h3>
          <p>Classic cookies packed with rich chocolate chips</p>
          <a href="products.php" class="btn">Shop Now</a>
        </div>

        <div class="category-card">
          <div class="category-img">
            <img src="../assets/images/Vanilla.png" alt="Vanilla Cookies">
          </div>
          <h3>Funfetti Vanilla</h3>
          <p>Have fun with sprinkled vanilla cookies</p>
          <a href="products.php" class="btn">Shop Now</a>
        </div>

        <div class="category-card">
          <div class="category-img">
            <img src="../assets/images/peanut-butter.jfif" alt="Peanut Butter Cookies">
          </div>
          <h3>Peanut Butter</h3>
          <p>Rich and nutty with the perfect crumb</p>
          <a href="products.php" class="btn">Shop Now</a>
        </div>

        <div class="category-card">
          <div class="category-img">
            <img src="../assets/images/Almond.png" alt="Almond Cookies">
          </div>
          <h3>Almond</h3>
          <p>Almondy nuts with the perfect crumb</p>
          <a href="products.php" class="btn">Shop Now</a>
        </div>

        <div class="category-card">
          <div class="category-img">
            <img src="../assets/images/Special.png" alt="Oreo Cookies">
          </div>
          <h3>Oreo</h3>
          <p>Delicious Oreo cookies with the perfect crunch</p>
          <a href="products.php" class="btn">Shop Now</a>
        </div>
      </div>
    </section>

    <!-- Featured Products Section -->
    <section class="featured-products">
      <div class="section-title">
        <h2>Today's Fresh Bakes</h2>
      </div>
      <div class="box-container">
        <?php
        // Fetch featured products (you can modify this query based on your needs)
        $featured_products = $conn->prepare("SELECT * FROM products ORDER BY created_at DESC LIMIT 4");
        $featured_products->execute();
        $products = $featured_products->fetchAll(PDO::FETCH_ASSOC);

        if (count($products) > 0):
          foreach ($products as $product):
        ?>
            <div class="box">
              <span class="product-badge">Fresh Baked</span>
              <img src="../admin/<?= $product['images']; ?>" alt="<?= $product['name']; ?>" class="img">
              <div class="name"><?= $product['name']; ?></div>
              <div class="price">₱<?= number_format($product['price'], 2); ?></div>
              <div class="button-container">
                <a href="products.php" class="btn">Shop Now</a>
              </div>
            </div>
          <?php
          endforeach;
        else:
          ?>
          <!-- Fallback featured products if no products in database -->
          <div class="box">
            <span class="product-badge">Best Seller</span>
            <img src="../assets/images/double-chocolate.jpg" alt="Double Chocolate Chip" class="img">
            <div class="name">Double Chocolate Chip</div>
            <div class="price">₱45/pc</div>
            <div class="button-container">
              <div class="button"><a href="cart.php"><i class="bx bx-cart-add"></i></a></div>
              <div class="button"><a href="view_page.php?pid=1"><i class="bx bx-show"></i></a></div>
            </div>
          </div>

          <div class="box">
            <span class="product-badge">New</span>
            <img src="../assets/images/macadamia.jpg" alt="White Chocolate Macadamia" class="img">
            <div class="name">White Chocolate Macadamia</div>
            <div class="price">₱50/pc</div>
            <div class="button-container">
              <div class="button"><a href="cart.php"><i class="bx bx-cart-add"></i></a></div>
              <div class="button"><a href="view_page.php?pid=2"><i class="bx bx-show"></i></a></div>
            </div>
          </div>

          <div class="box">
            <span class="product-badge">Seasonal</span>
            <img src="../assets/images/snickerdoodle.jpg" alt="Snickerdoodle" class="img">
            <div class="name">Snickerdoodle</div>
            <div class="price">₱40/pc</div>
            <div class="button-container">
              <div class="button"><a href="cart.php"><i class="bx bx-cart-add"></i></a></div>
              <div class="button"><a href="view_page.php?pid=3"><i class="bx bx-show"></i></a></div>
            </div>
          </div>

          <div class="box">
            <span class="product-badge">Limited</span>
            <img src="../assets/images/red-velvet.jpg" alt="Red Velvet" class="img">
            <div class="name">Red Velvet</div>
            <div class="price">₱55/pc</div>
            <div class="button-container">
              <div class="button"><a href="cart.php"><i class="bx bx-cart-add"></i></a></div>
              <div class="button"><a href="view_page.php?pid=4"><i class="bx bx-show"></i></a></div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </section>

    <!-- Cookie Features Section -->
    <section class="cookie-features">
      <div class="features-container">
        <div class="feature-box">
          <span class="feature-icon">🥚</span>
          <h3>Fresh Ingredients</h3>
          <p>Farm-fresh eggs, real butter, premium chocolate</p>
        </div>
        <div class="feature-box">
          <span class="feature-icon">⏰</span>
          <h3>Baked Daily</h3>
          <p>Fresh batches every morning at 6 AM</p>
        </div>
        <div class="feature-box">
          <span class="feature-icon">📦</span>
          <h3>Secure Packaging</h3>
          <p>Special packaging to keep cookies fresh</p>
        </div>
      </div>
    </section>

    <?php include '../includes/footer.php'; ?>
  </div>

  <style>
    /* Additional styles for the coming soon slide */
    .coming-soon-features {
      display: flex;
      justify-content: center;
      gap: 1rem;
      margin: 2rem 0;
      flex-wrap: wrap;
    }

    .feature-tag {
      background: rgba(255, 255, 255, 0.2);
      backdrop-filter: blur(5px);
      padding: 0.8rem 1.5rem;
      border-radius: 30px;
      color: #fff;
      font-size: 1.4rem;
      border: 2px solid var(--cookie-tan);
      animation: float 3s ease-in-out infinite;
    }

    .feature-tag:nth-child(2) {
      animation-delay: 0.2s;
    }

    .feature-tag:nth-child(3) {
      animation-delay: 0.4s;
    }

    @keyframes float {

      0%,
      100% {
        transform: translateY(0);
      }

      50% {
        transform: translateY(-10px);
      }
    }

    .offer-dates {
      font-size: 1.4rem !important;
      margin-top: 1rem !important;
      opacity: 0.9;
    }

    .slider__slider.slide3 .slide-detail p {
      margin: 1rem 0;
    }

    .slider__slider.slide3 .slide-detail h1 {
      font-size: 4rem;
    }

    .button-container {
      position: absolute;
      top: 10px;
      right: 10px;
      z-index: 2;
    }

    .button {
      display: flex;
      gap: 0.5rem;
      background: rgba(255, 255, 255, 0.9);
      padding: 0.5rem;
      border-radius: 30px;
      backdrop-filter: blur(5px);
    }

    .button .btn {
      background-color: var(--cookie-brown);
      color: #fff;
      padding: 0.5rem 1rem;
      border-radius: 20px;
      font-size: 1.2rem;
    }

    .button .btn:hover {
      background-color: var(--cookie-chocolate);
      transform: scale(1.05);
    }

    @media (max-width: 768px) {
      .coming-soon-features {
        flex-direction: column;
        align-items: center;
      }

      .feature-tag {
        width: 80%;
      }

      .slider__slider.slide3 .slide-detail h1 {
        font-size: 2.5rem;
      }
    }
  </style>

  <script src="../assets/js/script.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/2.1.2/sweetalert.min.js"></script>
  <?php include '../includes/alert.php'; ?>
</body>

</html>