// =========================
// Non-home Animation Guard
// =========================
(function () {
    "use strict";

    function showExistingRevealTargets() {
        document
            .querySelectorAll(".reveal, .reveal-left, .reveal-right, .reveal-scale")
            .forEach(function (target) {
                target.classList.add("show");
                target.style.removeProperty("--reveal-delay");
            });
    }

    document.addEventListener("DOMContentLoaded", function () {
        // Animation is intentionally limited to the first page.
        // Other pages keep their normal layout and display immediately.
        if (!document.body.classList.contains("home-page")) {
            showExistingRevealTargets();
        }
    });
})();