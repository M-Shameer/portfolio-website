
document.addEventListener("DOMContentLoaded", function () {

    const menuToggle = document.getElementById("menu-toggle");
    const navLinks = document.getElementById("nav-links");

    if (menuToggle && navLinks) {

        menuToggle.addEventListener("click", function () {

            navLinks.classList.toggle("active");

            if (navLinks.classList.contains("active")) {
                menuToggle.innerHTML = "✕";
                menuToggle.setAttribute("aria-expanded", "true");
            } else {
                menuToggle.innerHTML = "☰";
                menuToggle.setAttribute("aria-expanded", "false");
            }

        });

        const navigationItems = navLinks.querySelectorAll("a");

        navigationItems.forEach(function (item) {

            item.addEventListener("click", function () {

                navLinks.classList.remove("active");
                menuToggle.innerHTML = "☰";
                menuToggle.setAttribute("aria-expanded", "false");

            });

        });

    }

});
