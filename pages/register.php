<?php
include '../includes/connection.php';
session_start();

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    header('Location: index.php');
} else {
    $user_id = '';
}

if (isset($_POST['submit'])) {
    $id = unique_id();

    // Sanitize inputs
    $username = filter_var($_POST['username'], FILTER_SANITIZE_SPECIAL_CHARS);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    if (!$email) {
        echo "Invalid email format";
        exit;
    }

    $pass = filter_var($_POST['pass'], FILTER_SANITIZE_SPECIAL_CHARS);
    $cpass = filter_var($_POST['cpass'], FILTER_SANITIZE_SPECIAL_CHARS);
    $surname = filter_var($_POST['surname'], FILTER_SANITIZE_SPECIAL_CHARS);
    $first_name = filter_var($_POST['first_name'], FILTER_SANITIZE_SPECIAL_CHARS);
    $middle_name = filter_var($_POST['middle_name'], FILTER_SANITIZE_SPECIAL_CHARS);
    $phone = preg_replace('/\D/', '', $_POST['phone']);
    $barangay = filter_var($_POST['barangay'], FILTER_SANITIZE_SPECIAL_CHARS);
    $address = filter_var($_POST['address'], FILTER_SANITIZE_SPECIAL_CHARS);

    // Check if password and confirm password match
    if ($pass !== $cpass) {
        echo "Passwords do not match.";
        exit;
    }

    // Check if the email or username already exists
    $select_user = $conn->prepare("SELECT 1 FROM `users` WHERE email = ? LIMIT 1");
    $select_user->execute([$email]);
    if ($select_user->rowCount() > 0) {
        echo "Email already exists.";
        exit;
    }

    $select_username = $conn->prepare("SELECT 1 FROM `users` WHERE username = ? LIMIT 1");
    $select_username->execute([$username]);
    if ($select_username->rowCount() > 0) {
        echo "Username already exists.";
        exit;
    }

    // Hash the password
    $hashed_pass = password_hash($pass, PASSWORD_DEFAULT);

    // Insert the user into the database
    $insert_user = $conn->prepare("INSERT INTO `users`(id, username, email, password, surname, first_name, middle_name, phone, barangay, address, profile_picture) 
        VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert_user->execute([$id, $username, $email, $hashed_pass, $surname, $first_name, $middle_name, $phone, $barangay, $address, $profile_picture]);

    // Store user data in session after successful insertion
    $_SESSION['user_id'] = $id;
    $_SESSION['username'] = $username;
    $_SESSION['first_name'] = $first_name;
    $_SESSION['surname'] = $surname;
    $_SESSION['middle_name'] = $middle_name;
    $_SESSION['email'] = $email;
    $_SESSION['phone'] = $phone;
    $_SESSION['barangay'] = $barangay;
    $_SESSION['address'] = $address;

    $email = $_POST['email'];
    $email = filter_var($email, FILTER_SANITIZE_SPECIAL_CHARS);
    $pass = $_POST['pass'];
    $pass = filter_var($pass, FILTER_SANITIZE_SPECIAL_CHARS);

    $select_users = $conn->prepare("SELECT * FROM `users` WHERE email = ?");
    $select_users->execute([$email]);
    $row = $select_users->fetch(PDO::FETCH_ASSOC);

    if ($row && password_verify($pass, $row['password'])) {
        $_SESSION['user_id'] = $row['id'];
        $_SESSION['user_name'] = $row['username'];
        $_SESSION['user_email'] = $row['email'];
        $_SESSION['is_admin'] = $row['is_admin'];
        $_SESSION['first_name'] = $row['first_name'];
        $_SESSION['surname'] = $row['surname'];
        $_SESSION['middle_name'] = $row['middle_name'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['phone'] = $row['phone'];
        $_SESSION['barangay'] = $row['barangay'];
        $_SESSION['address'] = $row['address'];
    }
    header('Location: index.php');
    exit;

}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Tender Loving Cookies - Sign Up</title>
    <link rel="stylesheet" href="../assets/css/styles.css" />
    <link rel="stylesheet" href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css">
</head>

<body class="auth-page">
    <?php include '../includes/header.php'; ?>

    <div class="main-container-register">
        <section class="form-container">
            <form action="register.php" method="post" enctype="multipart/form-data">
                <!-- Step 1: Username, Email, Password -->
                <div class="form-step active">
                    <h1>Sign Up</h1>
                    <div class="input-field">
                        <label>Username <sup>*</sup></label>
                        <input type="text" name="username" required maxlength="50"
                            oninput="this.value = this.value.replace(/\s/g, '')">
                        <span class="error-message"></span>
                    </div>
                    <div class="input-field">
                        <label>Email <sup>*</sup></label>
                        <input type="email" name="email" required maxlength="50" minlength="12"
                            oninput="this.value = this.value.replace(/\s/g, '')">
                        <span class="error-message"></span>
                    </div>
                    <div class="password-section">
                        <div class="input-field">
                            <label>Password <sup>*</sup></label>
                            <div class="password-container">
                                <input type="password" id="password" name="pass" required maxlength="16" minlength="8"
                                    oninput="this.value = this.value.replace(/\s/g, '')">
                                <i id="toggle-pass" class="bx bx-hide toggle-password"
                                    onclick="togglePassword('password', 'toggle-pass')"></i>
                            </div>
                        </div>
                        <div class="input-field">
                            <label>Confirm Password <sup>*</sup></label>
                            <div class="password-container">
                                <input type="password" id="cpassword" name="cpass" required maxlength="16" minlength="8"
                                    oninput="this.value = this.value.replace(/\s/g, '')">
                                <i id="toggle-cpass" class="bx bx-hide toggle-password"
                                    onclick="togglePassword('cpassword', 'toggle-cpass')"></i>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn next-step">Next</button>
                    <p>
                        Already have an account?
                        <a href="login.php" class="link">Log in</a>
                    </p>
                </div>

                <!-- Step 2: Name, Phone Number, Barangay, Address -->
                <div class="form-step">
                    <h1>Additional Information</h1>
                    <div class="name">
                        <div class="input-field">
                            <label>Surname <sup>*</sup></label>
                            <input type="text" name="surname" required>
                        </div>
                        <div class="input-field">
                            <label>First Name <sup>*</sup></label>
                            <input type="text" name="first_name" required>
                        </div>
                        <div class="input-field">
                            <label>Middle Name <sup>*</sup></label>
                            <input type="text" name="middle_name" required>
                        </div>
                    </div>
                    <div class="barangay-profile">
                        <div class="input-field">
                            <label>Phone Number <sup>*</sup></label>
                            <input type="tel" name="phone" required maxlength="15"
                                oninput="this.value = this.value.replace(/\s/g, '')">
                        </div>
                        <div class="input-field">
                            <label>Barangay <sup>*</sup></label>
                            <select name="barangay">
                                <option value="" disabled selected>Select a Barangay</option>
                                <?php include '../includes/select.php'; ?>
                            </select>
                        </div>
                    </div>
                    <div class="input-field">
                        <label>Address <sup>*</sup></label>
                        <input type="text" name="address" required>
                    </div>
                    <button type="submit" class="btn submit-btn" name="submit">Submit</button>
                </div>
            </form>
        </section>
    </div>

    <script src="../assets/js/script.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const formSteps = document.querySelectorAll(".form-step");
            const nextBtns = document.querySelectorAll(".next-step");
            const prevBtns = document.querySelectorAll(".prev-step");
            const form = document.querySelector("form");
            const passwordInput = document.getElementById("password");
            const confirmPasswordInput = document.getElementById("cpassword");
            let currentStep = 0;

            // Update the visible step
            function updateStep(step) {
                formSteps.forEach((formStep, index) => {
                    formStep.classList.toggle("active", index === step);
                });

                const firstInvalidField = formSteps[step].querySelector("input:invalid, textarea:invalid");
                if (firstInvalidField) firstInvalidField.focus();
            }

            function validateField(input) {
                let inputField = input.closest(".input-field");
                let errorMessage = inputField.querySelector(".error-message");

                if (errorMessage) {
                    errorMessage.remove();
                }

                errorMessage = document.createElement("span");
                errorMessage.classList.add("error-message");

                // Do not trim spaces for name fields and address
                if (input.name === "first_name" || input.name === "middle_name" || input.name === "surname" || input.name === "address") {
                    input.value = input.value; // Do not trim spaces for name fields and address
                } else {
                    input.value = input.value.trim(); // Trim spaces for other fields
                }

                let isValid = true;
                let message = "";

                if (!input.value) {
                    isValid = false;
                    message = "This field is required";
                } else if (input.name === "username" && !/^[a-zA-Z0-9_-]{3,50}$/.test(input.value)) {
                    isValid = false;
                    message = "Username must be 3-50 alphanumeric characters.";
                } else if ((input.name === "first_name" || input.name === "middle_name" || input.name === "surname") && !/^[a-zA-Z\s]+$/.test(input.value)) {
                    isValid = false;
                    message = "Name fields can only contain letters and spaces.";
                } else if (input.name === "email" && !/^\S+@\S+\.\S+$/.test(input.value)) {
                    isValid = false;
                    message = "Enter a valid email address.";
                } else if (input.name === "phone" && !/^\d{10,15}$/.test(input.value)) {
                    isValid = false;
                    message = "Phone number must be 10-15 digits.";
                } else if (input.name === "address" && !/^[a-zA-Z0-9\s,.-]+$/.test(input.value)) {
                    isValid = false;
                    message = "Address can contain letters, numbers, spaces, commas, and periods.";
                } else if (input.name === "pass" && !/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{6,16}$/.test(input.value)) {
                    isValid = false;
                    message = "Password must be 6-16 characters, including a number, and/or a special character.";
                }

                if (!isValid) {
                    errorMessage.textContent = message;
                    inputField.appendChild(errorMessage);
                }

                input.classList.toggle("input-error", !isValid);
                input.setCustomValidity(isValid ? "" : message);

                return isValid;
            }

            // Password match validation
            function checkPasswordMatch() {
                let inputField = confirmPasswordInput.closest(".input-field");
                let errorMessage = inputField.querySelector(".error-message");

                if (errorMessage) {
                    errorMessage.remove();
                }

                errorMessage = document.createElement("span");
                errorMessage.classList.add("error-message");

                if (confirmPasswordInput.value !== passwordInput.value) {
                    confirmPasswordInput.setCustomValidity("Passwords do not match");
                    errorMessage.textContent = "Passwords do not match";
                    inputField.appendChild(errorMessage);
                } else {
                    confirmPasswordInput.setCustomValidity("");
                }
            }

            passwordInput.addEventListener("input", checkPasswordMatch);
            confirmPasswordInput.addEventListener("input", checkPasswordMatch);

            // Move to next step after validation
            nextBtns.forEach(button => {
                button.addEventListener("click", () => {
                    if (validateStep(currentStep) && currentStep < formSteps.length - 1) {
                        currentStep++;
                        updateStep(currentStep);
                    }
                });
            });

            // Validate step before moving
            function validateStep(step) {
                let isValid = true;
                const inputs = formSteps[step].querySelectorAll("input[required], textarea[required], select[required]");

                inputs.forEach(input => {
                    if (!validateField(input)) {
                        isValid = false;
                    }
                });

                return isValid;
            }

            // Go back to previous step
            prevBtns.forEach(button => {
                button.addEventListener("click", () => {
                    if (currentStep > 0) {
                        currentStep--;
                        updateStep(currentStep);
                    }
                });
            });

            // Locate the username input and its associated error message span.
            const usernameInput = document.querySelector('input[name="username"]');
            const errorMessageSpan = usernameInput.parentElement.querySelector('.error-message');

            // Create a debounce timer variable.
            let debounceTimer;

            // Add an event listener for the input event.
            usernameInput.addEventListener('input', function () {
                // Retrieve and trim the value.
                const username = usernameInput.value.trim();

                // Clear any pending debounce timers.
                clearTimeout(debounceTimer);

                // Basic regex validation: Username must be 3-50 alphanumeric characters.
                const regex = /^[a-zA-Z0-9]{3,50}$/;
                if (!regex.test(username)) {
                    errorMessageSpan.innerText = "Username must be 3-50 alphanumeric characters.";
                    errorMessageSpan.style.color = "red";
                    return;
                } else {
                    // Clear error message if valid.
                    errorMessageSpan.innerText = "";
                }

                // Debounce the AJAX call (e.g., delay execution for 500ms after last input)
                debounceTimer = setTimeout(() => {
                    fetch('../includes/check_username.php?username=' + encodeURIComponent(username))
                        .then(response => response.text())
                        .then(result => {
                            // Trim the response and show messages accordingly.
                            const responseText = result.trim();
                            if (responseText === "taken") {
                                errorMessageSpan.innerText = "Username is already taken.";
                                errorMessageSpan.style.color = "red";
                            } else if (responseText === "available") {
                                errorMessageSpan.innerText = "Username is available.";
                                errorMessageSpan.style.color = "green";
                            } else {
                                errorMessageSpan.innerText = "Unexpected response.";
                                errorMessageSpan.style.color = "orange";
                            }
                        })
                        .catch(error => {
                            console.error("Error checking username:", error);
                            errorMessageSpan.innerText = "Error checking username.";
                            errorMessageSpan.style.color = "red";
                        });
                }, 500); // 500ms debounce delay
            });

            const emailInput = document.querySelector('input[name="email"]');
            const emailErrorMessage = emailInput.parentElement.querySelector('.error-message');

            emailInput.addEventListener('input', function () {
                const email = emailInput.value.trim();
                clearTimeout(debounceTimer);

                debounceTimer = setTimeout(() => {
                    fetch('../includes/check_email.php?email=' + encodeURIComponent(email))
                        .then(response => response.text())
                        .then(result => {
                            const responseText = result.trim();
                            if (responseText === "taken") {
                                emailErrorMessage.innerText = "Email is already taken.";
                                emailErrorMessage.style.color = "red";
                            } else if (responseText === "available") {
                                emailErrorMessage.innerText = "Email is available.";
                                emailErrorMessage.style.color = "green";
                            } else {
                                emailErrorMessage.innerText = "Unexpected response.";
                                emailErrorMessage.style.color = "orange";
                            }
                        })
                        .catch(error => {
                            console.error("Error checking email:", error);
                            emailErrorMessage.innerText = "Error checking email.";
                            emailErrorMessage.style.color = "red";
                        });
                }, 500);
            });

        });
    </script>
    <script>
        // If the user hasn't agreed to terms yet, redirect to terms page
        if (!sessionStorage.getItem("agreedToTerms")) {
            sessionStorage.setItem("returnTo", window.location.href); // Save current page
            window.location.href = "terms-and-conditions.php";
        }


        document.querySelector("form").addEventListener("submit", () => {
            sessionStorage.removeItem("agreedToTerms");
        });

    </script>
</body>

</html>