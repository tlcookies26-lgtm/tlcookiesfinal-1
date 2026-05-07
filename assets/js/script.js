document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll("[contenteditable=true]").forEach(el => {
        el.removeAttribute("contenteditable");
    });

    document.addEventListener("mousedown", (e) => {
        if (!e.target.closest("input, textarea, select")) {
            e.preventDefault(); // Prevents accidental focus on non-input elements
        }
    });

    /* Fixed Navbar */
    const header = document.querySelector('header');
    function fixedNavbar() {
        header?.classList.toggle('scroll', window.pageYOffset > 0);
    }
    fixedNavbar();
    window.addEventListener('scroll', fixedNavbar);

    /* Navbar Menu */
    let menu = document.querySelector('#menu-btn');
    let userBtn = document.querySelector('#user-btn');

    menu?.addEventListener('click', function () {
        document.querySelector('.navbar')?.classList.toggle('active');
    });

    userBtn?.addEventListener('click', function () {
        document.querySelector('.user-box')?.classList.toggle('active');
    });

    /* Home Slider */
    "use strict";

    const slider = document.querySelector(".slider");
    if (slider) {
        const leftArrow = document.querySelector(".left-arrow");
        const rightArrow = document.querySelector(".right-arrow");

        function scrollRight() {
            const scrollAmount = slider.clientWidth;
            if (slider.scrollLeft + scrollAmount >= slider.scrollWidth) {
                slider.scrollTo({ left: 0, behavior: "smooth" });
            } else {
                slider.scrollBy({ left: scrollAmount, behavior: "smooth" });
            }
        }

        function scrollLeft() {
            const scrollAmount = slider.clientWidth;
            if (slider.scrollLeft === 0) {
                slider.scrollTo({ left: slider.scrollWidth, behavior: "smooth" });
            } else {
                slider.scrollBy({ left: -scrollAmount, behavior: "smooth" });
            }
        }

        let timerId = setInterval(scrollRight, 7000);

        function resetTimer() {
            clearInterval(timerId);
            timerId = setInterval(scrollRight, 7000);
        }

        leftArrow?.addEventListener("click", () => {
            scrollLeft();
            resetTimer();
        });

        rightArrow?.addEventListener("click", () => {
            scrollRight();
            resetTimer();
        });

        slider.addEventListener("mouseenter", () => clearInterval(timerId));
        slider.addEventListener("mouseleave", resetTimer);
        window.addEventListener("resize", resetTimer);
    }

    /* Hover Icons */
    const cartBtn = document.querySelector(".cart-btn");
    const userIcon = document.querySelector(".header .icons i");

    function toggleActive(element) {
        element?.classList.toggle("active");
    }

    cartBtn?.addEventListener("click", function () {
        toggleActive(cartBtn);
    });

    userIcon?.addEventListener("click", function () {
        toggleActive(userIcon);
    });

    document.addEventListener("click", function (event) {
        if (cartBtn && userIcon && !cartBtn.contains(event.target) && !userIcon.contains(event.target)) {
            cartBtn.classList.remove("active");
            userIcon.classList.remove("active");
        }
    });

    /* Password Toggle */
    window.togglePassword = function (inputId, iconId) {
        const passwordField = document.getElementById(inputId);
        const toggleIcon = document.getElementById(iconId);

        if (passwordField && toggleIcon) {
            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.classList.replace("bx-hide","bx-show");
            } else {
                passwordField.type = "password";
                toggleIcon.classList.replace("bx-show","bx-hide");
            }
        }
    };

    /* SweetAlert2 Pop-ups */
    const urlParams = new URLSearchParams(window.location.search);
    const successMsg = urlParams.get("success_msg");
    const warningMsg = urlParams.get("warning_msg");

    if (successMsg) {
        Swal.fire({
            icon: "success",
            title: "Success!",
            text: successMsg,
            confirmButtonColor: "#87a243", // Green color for success
        });
        history.replaceState(null, null, window.location.pathname);
    }

    if (warningMsg) {
        Swal.fire({
            icon: "error",
            title: "Oops...",
            text: warningMsg,
            confirmButtonColor: "#87a243", // Red color for error
        });
        history.replaceState(null, null, window.location.pathname);
    }

    /* Modal */
    const modal = document.getElementById("myModal");
    const btn = document.getElementById("myBtn");
    const span = document.getElementsByClassName("close")[0];

    btn?.addEventListener("click", function () {
        modal.style.display = "block";
    });

    span?.addEventListener("click", function () {
        modal.style.display = "none";
    });

    window.addEventListener("click", function (event) {
        if (event.target == modal) {
            modal.style.display = "none";
        }
    });

    /* Profile Picture Upload Button Toggle */
    const fileInput = document.getElementById('profile_picture_input');
    const uploadBtnContainer = document.getElementById('uploadBtnContainer');

    if (fileInput && uploadBtnContainer) {
        fileInput.addEventListener('change', () => {
            uploadBtnContainer.style.display = fileInput.files.length > 0 ? 'block' : 'none';
        });
    }

    const editBtn = document.getElementById("editProfileBtn");
    const saveBtn = document.getElementById("saveProfileBtn");
    const textFields = document.querySelectorAll(".field-text");
    const inputFields = document.querySelectorAll(".field-input");

    editBtn?.addEventListener("click", () => {
        textFields.forEach(span => span.style.display = "none");
        inputFields.forEach(input => input.style.display = "inline-block");
        editBtn.style.display = "none";
        saveBtn.style.display = "inline-block";
    });

    // Optional: disable save if nothing changed or validate inputs before submission

});
