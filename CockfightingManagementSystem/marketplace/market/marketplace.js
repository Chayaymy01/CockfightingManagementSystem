/* =========================
   Element References
========================= */
const chickenCardContainer = document.querySelector(
    "#chicken-card-container"
);

const searchInput = document.querySelector("#search-input");
const breedFilter = document.querySelector("#breed-filter");
const priceFilter = document.querySelector("#price-filter");

const genderFilter = document.querySelector("#gender-filter");

const searchButton = document.querySelector("#search-btn");

const resultText = document.querySelector("#result-text");
const emptyState = document.querySelector("#empty-state");
const emptyResetButton = document.querySelector("#empty-reset-btn");
const resetFilterButton = document.querySelector("#reset-filter-btn");

const previousPageButton = document.querySelector("#previous-page");
const nextPageButton = document.querySelector("#next-page");
const paginationPages = document.querySelector("#pagination-pages");
const pagination = document.querySelector("#pagination");

const sortButtons = document.querySelectorAll(".sort-chip");

const marketplaceUi = window.SuperKaichonUi;
const marketplaceState = marketplaceUi
    ? marketplaceUi.createState({
        loading: "#marketplace-loading",
        content: [chickenCardContainer, pagination],
        empty: emptyState,
        error: "#marketplace-error",
        busyTarget: "#market-section",
        status: "#marketplace-load-status"
    })
    : null;

/* =========================
   Default Images
========================= */

// รูปไก่สำรอง
const DEFAULT_CHICKEN_IMAGE =
"../../shared/images/main.png";

// รูปโปรไฟล์ฟาร์มสำรอง
const DEFAULT_FARM_IMAGE =
"../../shared/images/default-farm.png";

// รูปปกฟาร์มสำรอง
const DEFAULT_FARM_COVER =
"../../shared/images/farm-1.png";

/* =========================
   Sale / Reservation Config
========================= */

const marketplaceDemoData = window.MarketplaceDemoData || {
    currentUserId: null,
    reservationDurationMs: 24 * 60 * 60 * 1000,
    chickens: []
};

const RESERVATION_DURATION_MS =
    marketplaceDemoData.reservationDurationMs;

const CURRENT_USER_ID =
    marketplaceDemoData.currentUserId;

const SALE_STATE_STORAGE_KEY =
    "marketplace-chicken-sale-state";

const VALID_SALE_STATUSES = [
    "available",
    "pending_payment",
    "sold"
];

let chickens =
    marketplaceDemoData.chickens;

/* =========================
   Demo Data
========================= */

// TODO: เปลี่ยน marketplaceDemoData เป็นข้อมูลจาก Marketplace API เมื่อมี Backend จริง

/* =========================
   Pagination State
========================= */

const ITEMS_PER_PAGE = 16;

let currentPage = 1;
let currentSort = "latest";
let filteredChickens = [...chickens];

// เก็บ interval ไว้ตัวเดียว ป้องกันการสร้างซ้ำเมื่อ render การ์ด
let reservationCountdownInterval = null;
let nextOrderId = 1003;

/* =========================
   Escape HTML
========================= */
function escapeHTML(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

/* =========================
   Resolve Image Paths
========================= */
function getChickenImagePath(chicken) {
    if (
        chicken.image &&
        String(chicken.image).trim() !== ""
    ) {
        return chicken.image;
    }

    return DEFAULT_CHICKEN_IMAGE;
}

function getFarmImagePath(chicken) {
    if (
        chicken.farmImage &&
        String(chicken.farmImage).trim() !== ""
    ) {
        return chicken.farmImage;
    }

    return DEFAULT_FARM_IMAGE;
}

function getFarmCoverPath(chicken) {
    if (
        chicken.farmCoverImage &&
        String(chicken.farmCoverImage).trim() !== ""
    ) {
        return chicken.farmCoverImage;
    }

    return DEFAULT_FARM_COVER;
}

/* =========================
   Sale State Persistence
========================= */

// บันทึกเฉพาะข้อมูลสถานะการขาย เพื่อให้สถานะยังอยู่หลังรีเฟรชหน้า
function saveSaleState() {
    const saleState = chickens.map(
        function (chicken) {
            return {
                id: chicken.id,
                saleStatus:
                    chicken.saleStatus,
                reservedAt:
                    chicken.reservedAt,
                reservedUntil:
                    chicken.reservedUntil,
                buyerId: chicken.buyerId,
                orderId: chicken.orderId,
                paymentStatus:
                    chicken.paymentStatus
            };
        }
    );

    try {
        localStorage.setItem(
            SALE_STATE_STORAGE_KEY,
            JSON.stringify(saleState)
        );
    } catch (error) {
        console.warn(
            "ไม่สามารถบันทึกสถานะการขายได้",
            error
        );
    }
}

// โหลดสถานะเดิมกลับเข้าอาร์เรย์ โดยไม่แก้ข้อมูลค้นหาและข้อมูลฟาร์ม
function loadSaleState() {
    let savedState = null;

    try {
        const rawState =
            localStorage.getItem(
                SALE_STATE_STORAGE_KEY
            );

        savedState = rawState
            ? JSON.parse(rawState)
            : null;
    } catch (error) {
        console.warn(
            "ไม่สามารถอ่านสถานะการขายได้",
            error
        );
    }

    if (!Array.isArray(savedState)) {
        return;
    }

    savedState.forEach(function (savedChicken) {
        const chicken = chickens.find(
            function (item) {
                return (
                    item.id ===
                    Number(savedChicken.id)
                );
            }
        );

        if (
            !chicken ||
            !VALID_SALE_STATUSES.includes(
                savedChicken.saleStatus
            )
        ) {
            return;
        }

        chicken.saleStatus =
            savedChicken.saleStatus;
        chicken.reservedAt =
            savedChicken.reservedAt || null;
        chicken.reservedUntil =
            savedChicken.reservedUntil || null;
        chicken.buyerId =
            savedChicken.buyerId ?? null;
        chicken.orderId =
            savedChicken.orderId ?? null;
        chicken.paymentStatus =
            savedChicken.paymentStatus || null;
    });

    const highestOrderId = chickens.reduce(
        function (highest, chicken) {
            const orderId = Number(
                chicken.orderId
            );

            return Number.isFinite(orderId)
                ? Math.max(highest, orderId)
                : highest;
        },
        1002
    );

    nextOrderId = highestOrderId + 1;
}

/* =========================
   Reservation Helpers
========================= */

// คืนข้อมูลไก่เป็นพร้อมขายและล้างข้อมูลคำสั่งซื้อที่หมดเวลา
function resetChickenReservation(chicken) {
    chicken.saleStatus = "available";
    chicken.reservedAt = null;
    chicken.reservedUntil = null;
    chicken.buyerId = null;
    chicken.orderId = null;
    chicken.paymentStatus = null;
}

// ตรวจทุกตัวในข้อมูล แม้การ์ดจะไม่ได้แสดงอยู่ในหน้าปัจจุบัน
function releaseExpiredReservations() {
    const now = Date.now();
    let hasExpiredReservation = false;

    chickens.forEach(function (chicken) {
        if (
            chicken.saleStatus !==
                "pending_payment"
        ) {
            return;
        }

        const reservedUntilTime =
            new Date(
                chicken.reservedUntil
            ).getTime();

        if (
            !Number.isFinite(
                reservedUntilTime
            ) ||
            reservedUntilTime <= now
        ) {
            resetChickenReservation(
                chicken
            );

            hasExpiredReservation = true;
        }
    });

    return hasExpiredReservation;
}

// แปลงเวลาที่เหลือเป็น HH:MM:SS
function formatRemainingTime(
    reservedUntil,
    now = Date.now()
) {
    const reservedUntilTime =
        new Date(reservedUntil).getTime();

    if (
        !Number.isFinite(
            reservedUntilTime
        )
    ) {
        return "00:00:00";
    }

    const totalSeconds = Math.max(
        0,
        Math.ceil(
            (reservedUntilTime - now) /
            1000
        )
    );

    const hours = Math.floor(
        totalSeconds / 3600
    );

    const minutes = Math.floor(
        (totalSeconds % 3600) / 60
    );

    const seconds =
        totalSeconds % 60;

    return [hours, minutes, seconds]
        .map(function (value) {
            return String(value)
                .padStart(2, "0");
        })
        .join(":");
}

// อัปเดตตัวนับทุกการ์ดที่กำลังแสดง และ render ใหม่เมื่อมีรายการหมดเวลา
function updateReservationCountdowns() {
    if (releaseExpiredReservations()) {
        saveSaleState();
        renderCurrentPage();
        return;
    }

    const now = Date.now();

    document.querySelectorAll(
        ".countdown-timer[data-reserved-until]"
    ).forEach(function (timer) {
        timer.textContent =
            "เหลือเวลา " +
            formatRemainingTime(
                timer.dataset.reservedUntil,
                now
            );
    });

    const hasPendingReservation =
        chickens.some(function (chicken) {
            return (
                chicken.saleStatus ===
                "pending_payment"
            );
        });

    if (
        !hasPendingReservation &&
        reservationCountdownInterval !==
            null
    ) {
        clearInterval(
            reservationCountdownInterval
        );

        reservationCountdownInterval =
            null;
    }
}

function startReservationCountdown() {
    updateReservationCountdowns();

    const hasPendingReservation =
        chickens.some(function (chicken) {
            return (
                chicken.saleStatus ===
                "pending_payment"
            );
        });

    if (
        hasPendingReservation &&
        reservationCountdownInterval ===
            null
    ) {
        reservationCountdownInterval =
            setInterval(
                updateReservationCountdowns,
                1000
            );
    }
}

// สร้างคำสั่งซื้อและล็อกไก่ไว้ให้ผู้ซื้อปัจจุบันเป็นเวลา 24 ชั่วโมง
function reserveChicken(chicken) {
    if (
        chicken.saleStatus !== "available"
    ) {
        return false;
    }

    const reservedAt = new Date();
    const reservedUntil = new Date(
        reservedAt.getTime() +
        RESERVATION_DURATION_MS
    );

    chicken.saleStatus =
        "pending_payment";
    chicken.reservedAt =
        reservedAt.toISOString();
    chicken.reservedUntil =
        reservedUntil.toISOString();
    chicken.buyerId = CURRENT_USER_ID;
    chicken.orderId = nextOrderId;
    chicken.paymentStatus = "pending";

    nextOrderId += 1;

    saveSaleState();

    return true;
}

/*
    เรียกฟังก์ชันนี้หลังระบบชำระเงินยืนยันสำเร็จ
    orderId ต้องตรงกับคำสั่งซื้อที่กำลังรอชำระเท่านั้น
*/
function markChickenPaymentPaid(
    chickenId,
    orderId
) {
    if (releaseExpiredReservations()) {
        saveSaleState();
    }

    const chicken = chickens.find(
        function (item) {
            return (
                item.id ===
                Number(chickenId)
            );
        }
    );

    if (
        !chicken ||
        chicken.saleStatus !==
            "pending_payment" ||
        Number(chicken.orderId) !==
            Number(orderId)
    ) {
        return false;
    }

    chicken.saleStatus = "sold";
    chicken.reservedUntil = null;
    chicken.paymentStatus = "paid";

    saveSaleState();
    renderCurrentPage();

    return true;
}

// เปิดให้หน้าชำระเงินหรือ callback ของ API เรียกอัปเดตสถานะได้
window.markChickenPaymentPaid =
    markChickenPaymentPaid;

/* =========================
   Render Farm / Sale Status
========================= */
function renderFarmStatus(chicken) {
    if (
        chicken.saleStatus ===
        "pending_payment"
    ) {
        return `
            <div
                class="farm-profile-card pending-payment"
                aria-label="รอชำระเงิน"
            >
                <strong class="pending-payment-title">
                    <i class="bi bi-clock-history"></i>
                    รอชำระเงิน
                </strong>

                <span
                    class="countdown-timer"
                    data-reserved-until="${escapeHTML(chicken.reservedUntil)}"
                >
                    เหลือเวลา ${formatRemainingTime(chicken.reservedUntil)}
                </span>
            </div>
        `;
    }

    if (chicken.saleStatus === "sold") {
        return `
            <div
                class="farm-profile-card sold-status"
                aria-label="ขายแล้ว"
            >
                <i class="bi bi-bag-check-fill"></i>
                <strong>ขายแล้ว</strong>
            </div>
        `;
    }

    const farmImagePath =
        getFarmImagePath(chicken);

    const farmCoverPath =
        getFarmCoverPath(chicken);

    return `
        <button
            type="button"
            class="farm-profile-card"
            data-farm="${escapeHTML(chicken.farm)}"
            aria-label="ดูข้อมูลฟาร์ม ${escapeHTML(chicken.farm)}"
            style="
                background-image:
                    linear-gradient(
                        90deg,
                        rgba(0, 0, 0, 0.72),
                        rgba(0, 0, 0, 0.35)
                    ),
                    url('${escapeHTML(farmCoverPath)}');
            "
        >
            <img
                src="${escapeHTML(farmImagePath)}"
                alt="รูปโปรไฟล์ ${escapeHTML(chicken.farm)}"
                class="farm-profile-image"
                loading="lazy"
            >

            <span class="farm-profile-info">
                <strong
                    class="farm-profile-name"
                    title="${escapeHTML(chicken.farm)}"
                >
                    ${escapeHTML(chicken.farm)}
                </strong>

                <span class="farm-rating">
                    <span class="rating-stars">
                        <i class="bi bi-star-fill"></i>
                        ${chicken.rating.toFixed(1)}
                    </span>

                    <span class="review-count">
                        (${chicken.reviewCount} รีวิว)
                    </span>
                </span>
            </span>

            <span class="farm-profile-arrow">
                <i class="bi bi-chevron-right"></i>
            </span>
        </button>
    `;
}

/* =========================
   Render Chicken Cards
========================= */
function renderCurrentPage() {
    if (!chickenCardContainer) {
        return;
    }

    // กันกรณีเปิดหน้าเข้ามาหลังเวลาจองสิ้นสุดแล้ว
    if (releaseExpiredReservations()) {
        saveSaleState();
    }

    const totalItems =
        filteredChickens.length;

    const totalPages = Math.max(
        1,
        Math.ceil(
            totalItems / ITEMS_PER_PAGE
        )
    );

    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    const startIndex =
        (currentPage - 1) * ITEMS_PER_PAGE;

    const endIndex =
        startIndex + ITEMS_PER_PAGE;

    const pageItems =
        filteredChickens.slice(
            startIndex,
            endIndex
        );

    chickenCardContainer.innerHTML = "";

    pageItems.forEach(function (chicken, index) {
        const card =
            document.createElement("article");

        card.className = "chicken-card reveal";
        card.style.setProperty(
            "--reveal-delay",
            `${Math.min(index * 35, 175)}ms`
        );

        const chickenImagePath =
            getChickenImagePath(chicken);

        const farmImagePath =
            getFarmImagePath(chicken);

        const farmCoverPath =
            getFarmCoverPath(chicken);

        const farmStatusHTML =
            renderFarmStatus(chicken);

        const isAvailable =
            chicken.saleStatus ===
            "available";

        const cartButtonLabel =
            chicken.saleStatus ===
                "pending_payment"
                ? `${chicken.name} ถูกจองและกำลังรอชำระเงิน`
                : chicken.saleStatus ===
                    "sold"
                    ? `${chicken.name} ขายแล้ว`
                    : `ซื้อ ${chicken.name}`;

        const cartButtonIcon =
            chicken.saleStatus ===
                "pending_payment"
                ? "bi-clock-history"
                : chicken.saleStatus ===
                    "sold"
                    ? "bi-cart-x"
                    : "bi-cart-plus";

        card.innerHTML = `
            <div class="chicken-image-wrapper">
                <img
                    src="${escapeHTML(chickenImagePath)}"
                    alt="${escapeHTML(chicken.name)}"
                    class="chicken-img"
                    loading="lazy"
                >

                ${
                    chicken.isNew
                        ? `
                            <span class="new-badge">
                                มาใหม่
                            </span>
                        `
                        : ""
                }

                <span class="chicken-badge">
                    <i class="bi bi-geo-alt-fill"></i>
                    ${escapeHTML(chicken.province)}
                </span>
            </div>

            <div class="chicken-body">
                <div class="chicken-heading">
                    <h3 title="${escapeHTML(chicken.name)}">
                        ${escapeHTML(chicken.name)}
                    </h3>

                    <span class="price">
                        ${chicken.price.toLocaleString("th-TH")} บาท
                    </span>
                </div>

                <div class="chicken-meta">
                    <span>
                        ${escapeHTML(chicken.breed)}
                    </span>

                    <span>
                        ${escapeHTML(chicken.age)}
                    </span>

                    <span>
                        ${escapeHTML(chicken.gender)}
                    </span>
                </div>

                <!-- กล่องฟาร์มหรือสถานะการขายของไก่ -->
                ${farmStatusHTML}

                <div class="card-actions">
                    <button
                        type="button"
                        class="detail-btn"
                        data-id="${chicken.id}"
                    >
                        ดูรายละเอียด
                    </button>

                    <button
                        type="button"
                        class="cart-btn${isAvailable ? "" : " disabled"}"
                        data-id="${chicken.id}"
                        aria-label="${escapeHTML(cartButtonLabel)}"
                        ${isAvailable ? "" : "disabled"}
                    >
                        <i class="bi ${cartButtonIcon}"></i>
                    </button>
                </div>
            </div>
        `;

        /* =========================
           รูปไก่เสีย ใช้รูปสำรอง
        ========================== */
        const chickenImage =
            card.querySelector(".chicken-img");

        if (chickenImage) {
            chickenImage.addEventListener(
                "error",
                function () {
                    chickenImage.src =
                        DEFAULT_CHICKEN_IMAGE;
                },
                { once: true }
            );
        }

        /* =========================
           รูปโปรไฟล์ฟาร์มเสีย
           ใช้รูปสำรอง
        ========================== */
        const farmImage =
            card.querySelector(
                ".farm-profile-image"
            );

        if (farmImage) {
            farmImage.addEventListener(
                "error",
                function () {
                    farmImage.src =
                        DEFAULT_FARM_IMAGE;
                },
                { once: true }
            );
        }

        /* =========================
           รูปปกฟาร์มเสีย

           ไม่สามารถจับ error จาก CSS background
           ได้โดยตรง จึงทดสอบรูปด้วย Image()
        ========================== */
        const availableFarmCard =
            card.querySelector(
                ".farm-profile-card[data-farm]"
            );

        if (availableFarmCard) {
            const farmCoverTester =
                new Image();

            farmCoverTester.src =
                farmCoverPath;

            farmCoverTester.addEventListener(
                "error",
                function () {
                    availableFarmCard
                        .style
                        .backgroundImage = `
                            linear-gradient(
                                90deg,
                                rgba(0, 0, 0, 0.72),
                                rgba(0, 0, 0, 0.35)
                            ),
                            url('${DEFAULT_FARM_COVER}')
                        `;
                },
                { once: true }
            );
        }

        chickenCardContainer.appendChild(
            card
        );
    });

    updateResultText(
        startIndex,
        endIndex,
        totalItems
    );

    renderPagination(totalPages);
    updateEmptyState(totalItems);
    startReservationCountdown();
    window.MarketplaceShared.initializeRevealAnimation();
}

/* =========================
   Result Text
========================= */
function updateResultText(
    startIndex,
    endIndex,
    totalItems
) {
    if (!resultText) {
        return;
    }

    if (totalItems === 0) {
        resultText.textContent =
            "ไม่พบรายการที่ตรงกับตัวกรอง";

        return;
    }

    const visibleStart =
        startIndex + 1;

    const visibleEnd =
        Math.min(
            endIndex,
            totalItems
        );

    resultText.textContent =
        `แสดง ${visibleStart}–${visibleEnd} ` +
        `จากทั้งหมด ` +
        `${totalItems.toLocaleString("th-TH")} รายการ`;
}

/* =========================
   Empty State
========================= */
function updateEmptyState(totalItems) {
    if (!emptyState || !pagination) {
        return;
    }

    const hasItems =
        totalItems > 0;

    emptyState.hidden =
        hasItems;

    pagination.hidden =
        !hasItems;
}

/* =========================
   Pagination
========================= */
function renderPagination(totalPages) {
    if (
        !paginationPages ||
        !previousPageButton ||
        !nextPageButton
    ) {
        return;
    }

    paginationPages.innerHTML = "";

    const visiblePages =
        getVisiblePages(
            totalPages,
            currentPage
        );

    visiblePages.forEach(function (page) {
        if (page === "...") {
            const dots =
                document.createElement("span");

            dots.textContent = "...";
            dots.className =
                "pagination-dots";

            paginationPages.appendChild(
                dots
            );

            return;
        }

        const pageButton =
            document.createElement("button");

        pageButton.type = "button";

        pageButton.className =
            "pagination-page" +
            (
                page === currentPage
                    ? " active"
                    : ""
            );

        pageButton.textContent = page;

        pageButton.setAttribute(
            "aria-label",
            `ไปหน้าที่ ${page}`
        );

        pageButton.addEventListener(
            "click",
            function () {
                goToPage(page);
            }
        );

        paginationPages.appendChild(
            pageButton
        );
    });

    previousPageButton.disabled =
        currentPage === 1;

    nextPageButton.disabled =
        currentPage === totalPages;
}

function getVisiblePages(
    totalPages,
    activePage
) {
    if (totalPages <= 7) {
        return Array.from(
            { length: totalPages },
            function (_, index) {
                return index + 1;
            }
        );
    }

    if (activePage <= 4) {
        return [
            1,
            2,
            3,
            4,
            5,
            "...",
            totalPages
        ];
    }

    if (
        activePage >=
        totalPages - 3
    ) {
        return [
            1,
            "...",
            totalPages - 4,
            totalPages - 3,
            totalPages - 2,
            totalPages - 1,
            totalPages
        ];
    }

    return [
        1,
        "...",
        activePage - 1,
        activePage,
        activePage + 1,
        "...",
        totalPages
    ];
}

function goToPage(page) {
    const totalPages = Math.max(
        1,
        Math.ceil(
            filteredChickens.length /
            ITEMS_PER_PAGE
        )
    );

    if (
        page < 1 ||
        page > totalPages
    ) {
        return;
    }

    currentPage = page;

    renderCurrentPage();

    const marketSection =
        document.querySelector(
            "#market-section"
        );

    if (marketSection) {
        marketSection.scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    }
}

/* =========================
   Search / Filter
========================= */
function applyFilters() {
    const keyword = searchInput
        ? searchInput.value
            .trim()
            .toLowerCase()
        : "";

    const selectedBreed = breedFilter
        ? breedFilter.value
        : "";

    const selectedPrice = priceFilter
        ? priceFilter.value
        : "";

    /*
        HTML ตอนนี้ใช้:

        "" = ทั้งหมด
        male = เพศผู้
        female = เพศเมีย
    */
    const selectedGender = genderFilter
        ? genderFilter.value
        : "";

    filteredChickens =
        chickens.filter(function (chicken) {
            const searchableText = [
                chicken.name,
                chicken.breed,
                chicken.farm,
                chicken.province
            ]
                .join(" ")
                .toLowerCase();

            const keywordMatched =
                keyword === "" ||
                searchableText.includes(
                    keyword
                );

            const breedMatched =
                selectedBreed === "" ||
                chicken.breed ===
                    selectedBreed;

            const genderMatched =
                selectedGender === "" ||
                (
                    selectedGender ===
                        "male" &&
                    chicken.gender ===
                        "เพศผู้"
                ) ||
                (
                    selectedGender ===
                        "female" &&
                    chicken.gender ===
                        "เพศเมีย"
                );

            let priceMatched = true;

            if (
                selectedPrice === "low"
            ) {
                priceMatched =
                    chicken.price < 5000;
            }

            if (
                selectedPrice === "mid"
            ) {
                priceMatched =
                    chicken.price >= 5000 &&
                    chicken.price <= 10000;
            }

            if (
                selectedPrice === "high"
            ) {
                priceMatched =
                    chicken.price > 10000;
            }

            return (
                keywordMatched &&
                breedMatched &&
                genderMatched &&
                priceMatched
            );
        });

    sortChickenData(
        filteredChickens,
        currentSort
    );

    currentPage = 1;

    renderCurrentPage();
}

/* =========================
   Sorting
========================= */
function sortChickenData(
    data,
    sortValue
) {
    if (
        sortValue === "price-low"
    ) {
        data.sort(function (a, b) {
            return a.price - b.price;
        });

        return;
    }

    if (
        sortValue === "price-high"
    ) {
        data.sort(function (a, b) {
            return b.price - a.price;
        });

        return;
    }

    if (
        sortValue === "popular"
    ) {
        data.sort(function (a, b) {
            return (
                b.popularity -
                a.popularity
            );
        });

        return;
    }

    data.sort(function (a, b) {
        return (
            b.createdOrder -
            a.createdOrder
        );
    });
}

/* =========================
   Reset Filter
========================= */
function resetFilters() {
    if (searchInput) {
        searchInput.value = "";
    }

    if (breedFilter) {
        breedFilter.value = "";
    }

    if (priceFilter) {
        priceFilter.value = "";
    }

    if (genderFilter) {
        genderFilter.value =
            "";
    }

    currentSort = "latest";

    sortButtons.forEach(
        function (button) {
            button.classList.remove(
                "active"
            );

            if (
                button.dataset.sort ===
                    "latest"
            ) {
                button.classList.add(
                    "active"
                );
            }
        }
    );

    filteredChickens = [
        ...chickens
    ];

    sortChickenData(
        filteredChickens,
        currentSort
    );

    currentPage = 1;

    renderCurrentPage();
}

/* =========================
   Pagination Events
========================= */
if (previousPageButton) {
    previousPageButton.addEventListener(
        "click",
        function () {
            goToPage(
                currentPage - 1
            );
        }
    );
}

if (nextPageButton) {
    nextPageButton.addEventListener(
        "click",
        function () {
            goToPage(
                currentPage + 1
            );
        }
    );
}

/* =========================
   Search Events
========================= */
if (searchButton) {
    searchButton.addEventListener(
        "click",
        applyFilters
    );
}

if (searchInput) {
    searchInput.addEventListener(
        "keydown",
        function (event) {
            if (
                event.key === "Enter"
            ) {
                applyFilters();
            }
        }
    );
}

if (breedFilter) {
    breedFilter.addEventListener(
        "change",
        applyFilters
    );
}

if (priceFilter) {
    priceFilter.addEventListener(
        "change",
        applyFilters
    );
}

if (genderFilter) {
    genderFilter.addEventListener(
        "change",
        applyFilters
    );
}

if (resetFilterButton) {
    resetFilterButton.addEventListener(
        "click",
        resetFilters
    );
}

if (emptyResetButton) {
    emptyResetButton.addEventListener(
        "click",
        resetFilters
    );
}

/* =========================
   Sort Chip Events
========================= */
sortButtons.forEach(
    function (button) {
        button.addEventListener(
            "click",
            function () {
                sortButtons.forEach(
                    function (item) {
                        item.classList.remove(
                            "active"
                        );
                    }
                );

                button.classList.add(
                    "active"
                );

                currentSort =
                    button.dataset.sort ||
                    "latest";

                sortChickenData(
                    filteredChickens,
                    currentSort
                );

                currentPage = 1;

                renderCurrentPage();
            }
        );
    }
);

/* =========================
   Card Events
========================= */
if (chickenCardContainer) {
    chickenCardContainer.addEventListener(
        "click",
        function (event) {
            const farmCard =
                event.target.closest(
                    ".farm-profile-card"
                );

            const detailButton =
                event.target.closest(
                    ".detail-btn"
                );

            const cartButton =
                event.target.closest(
                    ".cart-btn"
                );

            /*
                เปิดหน้าฟาร์ม
            */
            if (farmCard) {
                const farmName =
                    farmCard.dataset.farm;

                window.location.href =
                    `Farm-Detail.html?farm=${
                        encodeURIComponent(
                            farmName
                        )
                    }`;

                return;
            }

            /*
                เปิดรายละเอียดไก่
            */
            if (detailButton) {
                const chickenId =
                    detailButton.dataset.id;

                window.location.href =
                    `Chicken-Detail.html?id=${chickenId}`;

                return;
            }

            /*
                เพิ่มลงรถเข็น
            */
            if (cartButton) {
                const chickenId = Number(
                    cartButton.dataset.id
                );

                const chicken =
                    chickens.find(
                        function (item) {
                            return (
                                item.id ===
                                chickenId
                            );
                        }
                    );

                /*
                    ตรวจสถานะซ้ำตอนคลิก ป้องกันการซื้อซ้ำ
                    แม้ปุ่มถูกแก้ DOM หรือสถานะเปลี่ยนจากอีกแท็บ
                */
                if (
                    !chicken ||
                    cartButton.disabled ||
                    chicken.saleStatus !==
                        "available"
                ) {
                    return;
                }

                if (reserveChicken(chicken)) {
                    renderCurrentPage();
                }
            }
        }
    );
}

/* =========================
   Sync Sale State Between Tabs
========================= */

// ถ้าอีกแท็บเปลี่ยนสถานะ ให้หน้า Marketplace ปรับตามทันที
window.addEventListener(
    "storage",
    function (event) {
        if (
            event.key !==
            SALE_STATE_STORAGE_KEY
        ) {
            return;
        }

        loadSaleState();

        if (releaseExpiredReservations()) {
            saveSaleState();
        }

        renderCurrentPage();
    }
);

/* =========================
   Initial Data Loading
   ใช้ข้อมูล Demo ทันทีเมื่อยังไม่กำหนด Backend Endpoint
========================= */
function normalizeListingsResponse(response) {
    const payload = response && response.data !== undefined
        ? response.data
        : response;

    if (Array.isArray(payload)) return payload;
    if (!payload || typeof payload !== "object") return null;

    const candidates = [
        payload.items,
        payload.listings,
        payload.results,
        payload.content
    ];

    return candidates.find(Array.isArray) || null;
}

function renderLoadedMarketplaceData() {
    filteredChickens = [...chickens];
    loadSaleState();
    sortChickenData(filteredChickens, currentSort);
    currentPage = 1;
    renderCurrentPage();

    if (!marketplaceState) return;

    if (filteredChickens.length === 0) {
        marketplaceState.showEmpty("ไม่พบรายการไก่ชน");
    } else {
        marketplaceState.showContent("โหลดรายการไก่ชนเรียบร้อยแล้ว");
    }
}

async function loadMarketplaceListings() {
    const marketplaceApi = window.MarketplaceApi;

    if (
        !marketplaceState ||
        !marketplaceUi ||
        !marketplaceApi ||
        !marketplaceUi.isEndpointConfigured("marketplaceListings")
    ) {
        renderLoadedMarketplaceData();
        return;
    }

    marketplaceState.begin("กำลังโหลดรายการไก่ชน");

    try {
        const listings = normalizeListingsResponse(
            await marketplaceApi.getListings()
        );

        if (!listings) {
            throw new Error("รูปแบบข้อมูลรายการไก่ชนจาก Backend ไม่ถูกต้อง");
        }

        chickens = listings;
        renderLoadedMarketplaceData();
    } catch (error) {
        if (resultText) {
            resultText.textContent = "ไม่สามารถโหลดข้อมูลได้";
        }

        marketplaceState.showError(
            error && error.message
                ? error.message
                : "ไม่สามารถโหลดรายการไก่ชนได้"
        );
    }
}

const marketplaceRetryButton = document.querySelector(
    "#marketplace-retry-btn"
);

if (marketplaceRetryButton) {
    marketplaceRetryButton.addEventListener(
        "click",
        loadMarketplaceListings
    );
}

loadMarketplaceListings();
