(function (window, document) {
    function setupMobileNavigation() {
        const mobileMenuButton = document.querySelector("#mobile-menu-btn");
        const navMenu = document.querySelector("#nav-menu");

        if (!mobileMenuButton || !navMenu) return;

        mobileMenuButton.addEventListener("click", function () {
            navMenu.classList.toggle("show");
        });
    }

    function setupProfileDropdown() {
        const profileButton = document.querySelector("#profile-button");
        const profileDropdown = document.querySelector("#profile-dropdown");

        if (!profileButton || !profileDropdown) return;

        profileButton.addEventListener("click", function (event) {
            event.stopPropagation();

            const isOpen = profileDropdown.classList.toggle("show");
            profileButton.classList.toggle("open", isOpen);
            profileButton.setAttribute("aria-expanded", String(isOpen));
        });

        document.addEventListener("click", function (event) {
            if (
                !profileDropdown.contains(event.target) &&
                !profileButton.contains(event.target)
            ) {
                profileDropdown.classList.remove("show");
                profileButton.classList.remove("open");
                profileButton.setAttribute("aria-expanded", "false");
            }
        });
    }

    function initializeRevealAnimation() {
        const revealElements = document.querySelectorAll(
            ".reveal:not(.show):not(.reveal-bound)"
        );

        if (revealElements.length === 0) return;

        if (!("IntersectionObserver" in window)) {
            revealElements.forEach(function (element) {
                element.classList.add("show");
            });

            return;
        }

        const observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) return;

                entry.target.classList.add("show");
                entry.target.classList.add("reveal-bound");
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.18,
            rootMargin: "0px 0px -4% 0px"
        });

        revealElements.forEach(function (element) {
            element.classList.add("reveal-bound");
            observer.observe(element);
        });
    }

    function getCartCount(response) {
        const payload = response && response.data !== undefined
            ? response.data
            : response;

        if (!payload) return null;
        if (Array.isArray(payload)) return payload.length;
        if (Array.isArray(payload.items)) return payload.items.length;

        const count = payload.itemCount ?? payload.count ?? payload.total;
        return Number.isFinite(Number(count)) ? Number(count) : null;
    }

    async function loadCartCount() {
        const badge = document.querySelector("#cart-count");
        const ui = window.SuperKaichonUi;
        const cartApi = window.CartApi;

        if (
            !badge ||
            !ui ||
            !cartApi ||
            !ui.isEndpointConfigured("cart")
        ) {
            return;
        }

        let skeletonTimer = window.setTimeout(function () {
            badge.classList.add("is-loading");
            badge.setAttribute("aria-hidden", "true");
        }, 120);

        try {
            const count = getCartCount(await cartApi.getCart());

            if (count !== null) {
                badge.textContent = count.toLocaleString("th-TH");
                ui.animateUpdate(badge);
            }
        } catch (error) {
            badge.title = "ไม่สามารถอัปเดตจำนวนสินค้าในรถเข็นได้";
        } finally {
            window.clearTimeout(skeletonTimer);
            skeletonTimer = null;
            badge.classList.remove("is-loading");
            badge.setAttribute("aria-hidden", "false");
        }
    }

    const marketplaceShared = {
        initializeRevealAnimation: initializeRevealAnimation
    };

    window.MarketplaceShared = Object.freeze(marketplaceShared);

    setupMobileNavigation();
    setupProfileDropdown();
    initializeRevealAnimation();
    loadCartCount();
})(window, document);
