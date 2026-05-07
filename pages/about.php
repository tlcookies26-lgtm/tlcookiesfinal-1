<?php
include '../includes/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit-btn']) && !empty($user_id)) {
    $message = trim($_POST['message']);

    if (!empty($message)) {
        $insert_testimonial = $conn->prepare("INSERT INTO testimonials (user_id, message) VALUES (?, ?)");
        $insert_testimonial->execute([$user_id, $message]);

        // Redirect to the same page to avoid resubmission on refresh
        header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
        exit;
    }
}

if (isset($_POST['logout'])) {
    // Destroy the session
    session_unset(); // Unset all session variables
    session_destroy(); // Destroy the session

    // Redirect the user to the login page (or any other page after logout)
    header('Location: index.php');
    exit; // Make sure to call exit after header redirection
}
?>
<?php $page = 'about'; ?> <!-- Change per page -->

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - About Our Bakery</title>
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <style>
        /* Additional about page cookie theme styles */
        .banner {
            background-image: url('../images/cookie-banner-about.jpg');
        }

        .about-category .box {
            background: #fff;
            border-radius: 20px;
            padding: 2rem 1rem;
            box-shadow: var(--shadow);
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .about-category .box:hover {
            transform: translateY(-10px);
            border-color: var(--cookie-brown);
            box-shadow: 0 10px 30px rgba(139, 69, 19, 0.2);
        }

        .about-category .box img {
            width: 150px;
            height: 150px;
            object-fit: contain;
            margin-bottom: 1rem;
            transition: transform 0.3s ease;
        }

        .about-category .box:hover img {
            transform: scale(1.1) rotate(5deg);
        }

        .about-category .box h3 {
            color: var(--cookie-chocolate);
            font-size: 2rem;
            margin: 1rem 0;
            text-transform: capitalize;
        }

        .about-category .box p {
            color: #666;
            font-size: 1.4rem;
            margin-bottom: 1.5rem;
        }

        .about-category .box .btn {
            background-color: var(--cookie-brown);
            color: #fff;
            padding: 1rem 2.5rem;
            font-size: 1.4rem;
        }

        .about-category .box .btn:hover {
            background-color: var(--cookie-chocolate);
        }

        .about-services .box {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .about-services .box:hover {
            transform: scale(1.05);
            background: #fff;
            box-shadow: 0 5px 20px rgba(139, 69, 19, 0.15);
        }

        .about-services .box img {
            width: 80px;
            height: 80px;
            object-fit: contain;
            margin-bottom: 1rem;
        }

        .about-services .box h3 {
            color: var(--cookie-brown);
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            text-transform: capitalize;
        }

        .about-services .box p {
            color: #666;
            font-size: 1.4rem;
        }

        .testimonial-container {
            background-image: url('../images/cookie-testimonial-bg.jpg');
            position: relative;
            padding: 5rem 0;
        }

        .testimonial-container .overlay {
            background: var(--cookie-brown);
            opacity: 0.7;
        }

        .testimonial-container .title h1 {
            color: #fff;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .testimonial-item {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: 3px solid var(--cookie-tan);
        }

        .testimonial-item img {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--cookie-brown);
            margin: 0 auto 1.5rem;
        }

        .testimonial-item h1 {
            color: var(--cookie-chocolate) !important;
            font-size: 2.2rem !important;
            margin-bottom: 1rem !important;
        }

        .testimonial-item p {
            color: #555;
            font-size: 1.6rem;
            line-height: 1.8;
            font-style: italic;
        }

        .testimonial-item p::before,
        .testimonial-item p::after {
            content: '"';
            color: var(--cookie-brown);
            font-size: 2rem;
            font-weight: bold;
        }

        .contact-container {
            background-image: url('../images/cookie-contact-bg.jpg');
        }

        .address .box {
            background: #fff;
            border-radius: 15px;
            padding: 2rem;
            transition: all 0.3s ease;
            border: 2px solid var(--cookie-tan);
        }

        .address .box:hover {
            border-color: var(--cookie-brown);
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(139, 69, 19, 0.1);
        }

        .address .box i {
            background: var(--cookie-brown) !important;
        }

        .address .box h4 {
            color: var(--cookie-chocolate) !important;
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
        }

        .address .box p {
            color: #666;
            font-size: 1.4rem;
        }

        .modal-content {
            border-radius: 20px;
            border: 3px solid var(--cookie-tan);
        }

        .modal-content .form-container textarea {
            border: 2px solid var(--cookie-tan);
            border-radius: 10px;
            padding: 1rem;
            font-size: 1.4rem;
        }

        .modal-content .form-container textarea:focus {
            border-color: var(--cookie-brown);
            outline: none;
        }

        .modal-content .form-container .btn {
            background-color: var(--cookie-brown);
            color: #fff;
            padding: 1rem 3rem;
            font-size: 1.6rem;
            margin: 2rem auto;
        }

        .modal-content .form-container .btn:hover {
            background-color: var(--cookie-chocolate);
        }

        .close {
            color: var(--cookie-brown);
            font-size: 3rem;
            transition: all 0.3s ease;
        }

        .close:hover {
            color: var(--cookie-chocolate);
            transform: scale(1.1);
        }

        .logo2 {
            border-radius: 50%;
            border: 3px solid var(--cookie-tan);
            padding: 0.5rem;
            background: #fff;
        }

        .success-message {
            background: var(--cookie-tan);
            color: var(--cookie-chocolate);
            padding: 1.5rem;
            border-radius: 10px;
            margin: 2rem auto;
            max-width: 600px;
            text-align: center;
            font-size: 1.6rem;
            border-left: 5px solid var(--cookie-brown);
            animation: slideDown 0.5s ease;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @media (max-width: 768px) {
            .about-category .box-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .about-services .box-container {
                grid-template-columns: repeat(2, 1fr);
            }

            .testimonial-item {
                padding: 2rem;
            }

            .testimonial-item h1 {
                font-size: 1.8rem !important;
            }

            .testimonial-item p {
                font-size: 1.4rem;
            }
        }

        @media (max-width: 480px) {
            .about-category .box-container {
                grid-template-columns: 1fr;
            }

            .about-services .box-container {
                grid-template-columns: 1fr;
            }

            .address .box-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include '../includes/header.php'; ?>
    <?php
    if (isset($_SESSION['success_message'])):
        // Display the success message
        echo '<div class="success-message">' . htmlspecialchars($_SESSION['success_message']) . '</div>';

        // Clear the message after displaying
        unset($_SESSION['success_message']);
    endif;
    ?>

    <!-- About Content -->
    <div class="main">
        <div class="banner">
            <div class="overlay"></div>
            <h1>Our Bakery Story</h1>
        </div>
        <div class="title2">
            <a href="index.php">home </a><span>/ about us</span>
        </div>

        <section class="about-services">
            <div class="title">
                <h1>Why Choose Our Bakery?</h1>
                <p>Quality ingredients, baked with passion</p>
            </div>
            <div class="box-container">
                <div class="box">
                    <img src="../assets/images/icon2.png">
                    <div class="detail">
                        <h3>fresh ingredients</h3>
                        <p>farm-fresh eggs & real butter</p>
                    </div>
                </div>
                <div class="box">
                    <img src="../assets/images/icon1.png">
                    <div class="detail">
                        <h3>baked daily</h3>
                        <p>fresh batches every morning</p>
                    </div>
                </div>
                <div class="box">
                    <img src="../assets/images/icon0.png">
                    <div class="detail">
                        <h3>cookie rewards</h3>
                        <p>earn points with every purchase</p>
                    </div>
                </div>
            </div>
        </section>

        <div class="testimonial-container">
            <div class="overlay"></div>
            <div class="title">
                <a class="logo-text" style="font-family: 'Segoe UI', Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: #3E2723; text-decoration: none;">
                    Tender Loving <span style="color: #3E2723;">Cookies</span> 🍪</a>
                <h1>What Our Cookie Lovers Say</h1>
            </div>
            <div class="container">
                <?php
                // Fetch testimonials from the database
                $testimonials = $conn->prepare(
                    "SELECT t.message, u.first_name, u.surname, u.profile_picture 
                    FROM testimonials t 
                    JOIN users u ON t.user_id = u.id 
                    ORDER BY t.created_at DESC 
                    LIMIT 10"
                );
                $testimonials->execute();
                $results = $testimonials->fetchAll(PDO::FETCH_ASSOC);
                $first = true;

                // Display testimonials from the database
                foreach ($results as $testimonial):
                ?>
                    <div class="testimonial-item <?= $first ? 'active' : '' ?>">
                        <?php
                        $profilePic = !empty($testimonial['profile_picture'])
                            ? htmlspecialchars($testimonial['profile_picture'])
                            : '../assets/images/default-profile-photo.jpg'; // fallback if no image
                        ?>
                        <img src="<?= $profilePic ?>" alt="Profile Picture">
                        <h1><?= htmlspecialchars($testimonial['first_name']) ?>
                            <?= htmlspecialchars($testimonial['surname']) ?>
                        </h1>
                        <p><?= nl2br(htmlspecialchars($testimonial['message'])) ?></p>
                    </div>
                <?php
                    $first = false;
                endforeach;
                ?>

                <!-- Hardcoded Testimonials -->
                <div class="testimonial-item">
                    <img src="../assets/images/person1.jpg" alt="Customer">
                    <h1>Darrius Ken Pantaleon</h1>
                    <p>Ordered a dozen cookies for a party. Everyone loved them! Will definitely order again.</p>
                </div>

                <!-- Arrows -->
                <div class="left-arrow"><i class="bx bxs-left-arrow-alt"></i></div>
                <div class="right-arrow"><i class="bx bxs-right-arrow-alt"></i></div>
            </div>
        </div>

        <div class="title2">
            <a href="index.php">home </a><span>/ contact us</span>
        </div>

        <div class="contact-container">
            <div class="address">
                <div class="title">
                    <div class="img-box">
                        <a class="logo-text" style="font-family: 'Segoe UI', Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: #3E2723; text-decoration: none;">
                            Tender Loving <span style="color: #3E2723;">Cookies</span> 🍪</a>
                    </div>
                    <h1>Visit Us</h1>
                </div>
                <div class="box-container">
                    <div class="box">
                        <i class="bx bxs-map-pin"></i>
                        <div>
                            <h4>address</h4>
                            <p>WMSU, Baliwasan Normal Road, Zamboanga City</p>
                        </div>
                    </div>
                    <div class="box">
                        <i class="bx bxs-phone-call"></i>
                        <div>
                            <h4>phone number</h4>
                            <p>(+63)935-967-6696</p>
                        </div>
                    </div>
                    <div class="box">
                        <i class="bx bxs-envelope"></i>
                        <div>
                            <h4>email</h4>
                            <p>cookiejardelights@gmail.com</p>
                            <p>orders@cookiejar.com</p>
                        </div>
                    </div>
                </div>
                <div class="modal-container">
                    <button id="myBtn" class="btn">Leave a Cookie Review</button>
                    <div id="myModal" class="modal">
                        <div class="modal-content">
                            <div class="form-container">
                                <form method="post">
                                    <div class="close-container">
                                        <span class="close">&times;</span>
                                    </div>
                                    <div class="message-container">
                                        <div class="title">
                                            <a class="logo-text" style="font-family: 'Segoe UI', Arial, sans-serif; font-size: 2.5rem; font-weight: 800; color: #3E2723; text-decoration: none;">
                                                Tender Loving <span style="color: #3E2723;">Cookies</span> 🍪</a>
                                            <h1>Share Your Cookie Experience</h1>
                                            <p>Tell us what you think about our cookies! 🍪</p>
                                        </div>
                                        <div class="input-field">
                                            <p>your message<sup>*</sup></p>
                                            <textarea name="message" required placeholder="I love the..."></textarea>
                                        </div>
                                        <button type="submit" name="submit-btn" class="btn">Send Review</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php include '../includes/footer.php'; ?>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        // Testimonial slider functionality
        let slides = document.querySelectorAll('.testimonial-item');
        let index = 0;
        let testimonialTimerId;

        function showSlide(newIndex) {
            if (slides.length === 0) return;
            slides[index].classList.remove('active');
            index = (newIndex + slides.length) % slides.length;
            slides[index].classList.add('active');
        }

        function nextSlide() {
            showSlide(index + 1);
        }

        function prevSlide() {
            showSlide(index - 1);
        }

        function startTestimonialAutoSlide() {
            testimonialTimerId = setInterval(nextSlide, 5000);
        }

        function resetTestimonialTimer(action) {
            clearInterval(testimonialTimerId);
            action();
            startTestimonialAutoSlide();
        }

        const leftTestimonialArrow = document.querySelector(".testimonial-container .left-arrow");
        const rightTestimonialArrow = document.querySelector(".testimonial-container .right-arrow");

        if (leftTestimonialArrow && rightTestimonialArrow) {
            leftTestimonialArrow.addEventListener("click", function() {
                console.log("Left arrow clicked!");
                resetTestimonialTimer(prevSlide);
            });

            rightTestimonialArrow.addEventListener("click", function() {
                console.log("Right arrow clicked!");
                resetTestimonialTimer(nextSlide);
            });
        } else {
            console.error("Testimonial navigation arrows not found.");
        }

        // Initialize slider
        if (slides.length > 0) {
            showSlide(index);
            startTestimonialAutoSlide();
        }

        // Modal functionality
        var modal = document.getElementById("myModal");
        var btn = document.getElementById("myBtn");
        var span = document.getElementsByClassName("close")[0];

        if (btn) {
            btn.onclick = function() {
                modal.style.display = "block";
            }
        }

        if (span) {
            span.onclick = function() {
                modal.style.display = "none";
            }
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>

</html>