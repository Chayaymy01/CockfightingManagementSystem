/* =========================
   Element References
========================= */
const ownerListingContainer = document.querySelector(
    "#owner-listing-container"
);

const searchInput = document.querySelector(
    "#listing-search-input"
);

const breedFilter = document.querySelector("#breed-filter");
const statusFilter = document.querySelector("#status-filter");
const priceFilter = document.querySelector("#price-filter");
const sortFilter = document.querySelector("#sort-filter");
const searchButton = document.querySelector("#search-btn");

const resultText = document.querySelector("#result-text");
const emptyState = document.querySelector("#empty-listing-state");
const emptyTitle = document.querySelector("#empty-title");
const emptyDescription = document.querySelector("#empty-description");
const emptyCreateButton = document.querySelector("#empty-create-btn");
const emptyResetButton = document.querySelector("#empty-reset-btn");
const resetFilterButton = document.querySelector("#reset-filter-btn");

const previousPageButton = document.querySelector("#previous-page");
const nextPageButton = document.querySelector("#next-page");
const paginationPages = document.querySelector("#pagination-pages");
const pagination = document.querySelector("#pagination");

const summaryCards = document.querySelectorAll(
    "[data-summary-status]"
);

const summaryElements = {
    total: document.querySelector("#summary-total-count"),
    available: document.querySelector("#summary-available-count"),
    pending_payment: document.querySelector("#summary-pending-count"),
    sold: document.querySelector("#summary-sold-count"),
    inactive: document.querySelector("#summary-inactive-count")
};

const ownerMarketplaceUi = window.SuperKaichonUi;
const ownerMarketplaceState = ownerMarketplaceUi
    ? ownerMarketplaceUi.createState({
        loading: [
            document.querySelector("#owner-summary-loading"),
            document.querySelector("#owner-listing-loading")
        ],
        content: [
            document.querySelector("#listing-summary"),
            ownerListingContainer,
            pagination
        ],
        empty: emptyState,
        error: "#owner-listing-error",
        busyTarget: "#owner-listing-section",
        status: "#owner-market-load-status"
    })
    : null;

/* =========================
   Config
========================= */
const DEFAULT_CHICKEN_IMAGE = "../../shared/images/main.png";

const selfMarketDemoData = window.SelfMarketDemoData || {
    currentFarmOwnerId: null,
    reservationDurationMs: 24 * 60 * 60 * 1000,
    chickens: []
};

const authStorage = window.SuperKaichonAuthStorage;
const CURRENT_FARM_OWNER_ID =
    (authStorage && authStorage.getUserId()) ||
    selfMarketDemoData.currentFarmOwnerId;

const ITEMS_PER_PAGE = 8;
const RESERVATION_DURATION_MS =
    selfMarketDemoData.reservationDurationMs;
const OWNER_LISTING_STORAGE_KEY =
    "self-market-owner-listings-state";

const STATUS_LABELS = {
    available: "กำลังขาย",
    pending_payment: "รอชำระเงิน",
    sold: "ขายแล้ว",
    inactive: "ปิดประกาศ"
};

const STATUS_ICONS = {
    available: "bi-megaphone",
    pending_payment: "bi-hourglass-split",
    sold: "bi-check2-circle",
    inactive: "bi-pause-circle"
};

const VALID_LISTING_STATUSES = Object.keys(STATUS_LABELS);
let chickens = selfMarketDemoData.chickens;

/* =========================
   Demo Data
========================= */

// TODO: เปลี่ยน selfMarketDemoData เป็นข้อมูลจาก Marketplace API เมื่อมี Backend จริง

/* =========================
   Page State
========================= */
let filteredOwnerListings = [];
let currentPage = 1;
let reservationCountdownInterval = null;
let deletedListingIds = new Set();

/* =========================
   Utilities
========================= */
function escapeHTML(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function getChickenImagePath(chicken) {
    if (
        chicken.image &&
        String(chicken.image).trim() !== ""
    ) {
        return chicken.image;
    }

    return DEFAULT_CHICKEN_IMAGE;
}

function formatThaiDate(value) {
    const date = new Date(value);

    if (!Number.isFinite(date.getTime())) {
        return "-";
    }

    return date.toLocaleDateString(
        "th-TH",
        {
            day: "2-digit",
            month: "short",
            year: "numeric"
        }
    );
}

function formatRemainingTime(
    reservedUntil,
    now = Date.now()
) {
    const reservedUntilTime =
        new Date(reservedUntil).getTime();

    if (!Number.isFinite(reservedUntilTime)) {
        return "00:00:00";
    }

    const totalSeconds = Math.max(
        0,
        Math.ceil(
            (reservedUntilTime - now) / 1000
        )
    );

    const hours = Math.floor(totalSeconds / 3600);
    const minutes = Math.floor(
        (totalSeconds % 3600) / 60
    );
    const seconds = totalSeconds % 60;

    return [hours, minutes, seconds]
        .map(function (value) {
            return String(value).padStart(2, "0");
        })
        .join(":");
}

function getStatusClass(status) {
    return `status-${String(status).replaceAll("_", "-")}`;
}

function findOwnerChickenById(chickenId) {
    return chickens.find(function (chicken) {
        return (
            chicken.id === Number(chickenId) &&
            chicken.ownerId === CURRENT_FARM_OWNER_ID
        );
    });
}

/* =========================
   Owner Filter Guard
========================= */
function getOwnerChickens() {
    /*
        กรองเฉพาะ ownerId ของเจ้าของร้านที่เข้าสู่ระบบ
        ทุกส่วนของหน้านี้ต้องเริ่มจากฟังก์ชันนี้เสมอ
        เพื่อไม่ให้แสดงไก่จากร้านอื่น
    */
    return chickens.filter(function (chicken) {
        return String(chicken.ownerId) === String(CURRENT_FARM_OWNER_ID);
    });
}

/* =========================
   Local State Persistence
========================= */
function saveOwnerListingState() {
    const state = {
        deletedIds: Array.from(deletedListingIds),
        listings: chickens.map(function (chicken) {
            return {
                id: chicken.id,
                saleStatus: chicken.saleStatus,
                createdAt: chicken.createdAt,
                reservedAt: chicken.reservedAt,
                reservedUntil: chicken.reservedUntil,
                orderId: chicken.orderId,
                buyerId: chicken.buyerId,
                paymentStatus: chicken.paymentStatus
            };
        })
    };

    try {
        localStorage.setItem(
            OWNER_LISTING_STORAGE_KEY,
            JSON.stringify(state)
        );
    } catch (error) {
        console.warn(
            "ไม่สามารถบันทึกสถานะประกาศขายได้",
            error
        );
    }
}

function loadOwnerListingState() {
    let savedState = null;

    try {
        const rawState =
            localStorage.getItem(
                OWNER_LISTING_STORAGE_KEY
            );

        savedState = rawState
            ? JSON.parse(rawState)
            : null;
    } catch (error) {
        console.warn(
            "ไม่สามารถอ่านสถานะประกาศขายได้",
            error
        );
    }

    if (!savedState || typeof savedState !== "object") {
        return;
    }

    if (Array.isArray(savedState.deletedIds)) {
        deletedListingIds = new Set(
            savedState.deletedIds.map(Number)
        );

        chickens = chickens.filter(function (chicken) {
            return !deletedListingIds.has(chicken.id);
        });
    }

    if (!Array.isArray(savedState.listings)) {
        return;
    }

    savedState.listings.forEach(function (savedChicken) {
        const chicken = chickens.find(function (item) {
            return item.id === Number(savedChicken.id);
        });

        if (
            !chicken ||
            !VALID_LISTING_STATUSES.includes(
                savedChicken.saleStatus
            )
        ) {
            return;
        }

        chicken.saleStatus = savedChicken.saleStatus;
        chicken.createdAt =
            savedChicken.createdAt || chicken.createdAt;
        chicken.reservedAt =
            savedChicken.reservedAt || null;
        chicken.reservedUntil =
            savedChicken.reservedUntil || null;
        chicken.orderId = savedChicken.orderId ?? null;
        chicken.buyerId = savedChicken.buyerId ?? null;
        chicken.paymentStatus =
            savedChicken.paymentStatus || null;
    });
}

/* =========================
   Reservation / Countdown
========================= */
function resetChickenReservation(chicken) {
    chicken.saleStatus = "available";
    chicken.reservedAt = null;
    chicken.reservedUntil = null;
    chicken.orderId = null;
    chicken.buyerId = null;
    chicken.paymentStatus = null;
}

function releaseExpiredReservations() {
    let hasExpiredReservation = false;
    const now = Date.now();

    getOwnerChickens().forEach(function (chicken) {
        if (
            chicken.saleStatus !== "pending_payment"
        ) {
            return;
        }

        const reservedUntilTime =
            new Date(
                chicken.reservedUntil
            ).getTime();

        if (
            !Number.isFinite(reservedUntilTime) ||
            reservedUntilTime <= now
        ) {
            // เมื่อหมดเวลารอชำระเงิน ให้กลับมาเป็นประกาศกำลังขาย
            resetChickenReservation(chicken);
            hasExpiredReservation = true;
        }
    });

    return hasExpiredReservation;
}

function updateReservationCountdowns() {
    if (releaseExpiredReservations()) {
        saveOwnerListingState();
        applyOwnerListingFilters({
            resetPage: false
        });
        return;
    }

    const now = Date.now();

    document
        .querySelectorAll(
            ".countdown-timer[data-reserved-until]"
        )
        .forEach(function (timer) {
            timer.textContent =
                `เหลือเวลา ${formatRemainingTime(
                    timer.dataset.reservedUntil,
                    now
                )}`;
        });

    const hasPendingReservation =
        getOwnerChickens().some(function (chicken) {
            return (
                chicken.saleStatus ===
                "pending_payment"
            );
        });

    if (
        !hasPendingReservation &&
        reservationCountdownInterval
    ) {
        clearInterval(
            reservationCountdownInterval
        );
        reservationCountdownInterval = null;
    }
}

function startReservationCountdown() {
    /*
        ใช้ setInterval ตัวเดียวสำหรับทั้งหน้า
        ป้องกันการสร้าง interval ซ้ำทุกครั้งที่ render ใหม่
    */
    updateReservationCountdowns();

    if (reservationCountdownInterval) {
        return;
    }

    const hasPendingReservation =
        getOwnerChickens().some(function (chicken) {
            return (
                chicken.saleStatus ===
                "pending_payment"
            );
        });

    if (!hasPendingReservation) {
        return;
    }

    reservationCountdownInterval = setInterval(
        updateReservationCountdowns,
        1000
    );
}

/* =========================
   Status / Actions Render
========================= */
function renderListingStatus(chicken) {
    const status = VALID_LISTING_STATUSES.includes(
        chicken.saleStatus
    )
        ? chicken.saleStatus
        : "inactive";

    const label = STATUS_LABELS[status];
    const icon = STATUS_ICONS[status];

    return `
        <span class="listing-status ${getStatusClass(status)}">
            <i class="bi ${icon}"></i>
            ${escapeHTML(label)}
        </span>
    `;
}

function renderActionButton(options) {
    const disabled = options.disabled
        ? " disabled"
        : "";

    const disabledAttribute = options.disabled
        ? "disabled"
        : "";

    return `
        <button
            type="button"
            class="action-btn ${options.variant || ""}${disabled}"
            data-action="${escapeHTML(options.action || "")}"
            data-id="${escapeHTML(options.id || "")}"
            data-order-id="${escapeHTML(options.orderId || "")}"
            data-buyer-id="${escapeHTML(options.buyerId || "")}"
            ${disabledAttribute}
        >
            <i class="bi ${escapeHTML(options.icon)}"></i>
            ${escapeHTML(options.label)}
        </button>
    `;
}

function renderListingActions(chicken) {
    /*
        Render ปุ่มตามสถานะประกาศ
        ป้องกันไม่ให้สถานะ pending/sold ถูกแก้หรือลบแบบประกาศปกติ
    */
    const baseDetailButton = renderActionButton({
        action: "detail",
        id: chicken.id,
        icon: "bi-eye",
        label: "ดูรายละเอียด",
        variant: "action-info"
    });

    if (chicken.saleStatus === "available") {
        return `
            ${baseDetailButton}
            ${renderActionButton({
                action: "edit",
                id: chicken.id,
                icon: "bi-pencil-square",
                label: "แก้ไขประกาศ",
                variant: "action-primary"
            })}
            ${renderActionButton({
                action: "close",
                id: chicken.id,
                icon: "bi-pause-circle",
                label: "ปิดประกาศ",
                variant: "action-warning"
            })}
            ${renderActionButton({
                action: "delete",
                id: chicken.id,
                icon: "bi-trash3",
                label: "ลบประกาศ",
                variant: "action-danger"
            })}
        `;
    }

    if (chicken.saleStatus === "pending_payment") {
        return `
            ${baseDetailButton}
            ${renderActionButton({
                action: "order",
                id: chicken.id,
                orderId: chicken.orderId,
                icon: "bi-receipt",
                label: "ดูคำสั่งซื้อ",
                variant: "action-primary"
            })}
            ${renderActionButton({
                action: "",
                id: chicken.id,
                icon: "bi-lock",
                label: "แก้ไขถูกล็อก",
                disabled: true
            })}
        `;
    }

    if (chicken.saleStatus === "sold") {
        return `
            ${baseDetailButton}
            ${renderActionButton({
                action: "order",
                id: chicken.id,
                orderId: chicken.orderId,
                icon: "bi-receipt",
                label: "ดูคำสั่งซื้อ",
                variant: "action-primary"
            })}
            ${renderActionButton({
                action: "buyer",
                id: chicken.id,
                buyerId: chicken.buyerId,
                icon: "bi-person-lines-fill",
                label: "ข้อมูลผู้ซื้อ",
                variant: "action-info"
            })}
        `;
    }

    if (chicken.saleStatus === "inactive") {
        return `
            ${baseDetailButton}
            ${renderActionButton({
                action: "reopen",
                id: chicken.id,
                icon: "bi-arrow-clockwise",
                label: "เปิดขายอีกครั้ง",
                variant: "action-primary"
            })}
            ${renderActionButton({
                action: "edit",
                id: chicken.id,
                icon: "bi-pencil-square",
                label: "แก้ไขประกาศ"
            })}
            ${renderActionButton({
                action: "delete",
                id: chicken.id,
                icon: "bi-trash3",
                label: "ลบประกาศ",
                variant: "action-danger"
            })}
        `;
    }

    return baseDetailButton;
}

function renderPendingNote(chicken) {
    if (chicken.saleStatus !== "pending_payment") {
        return "";
    }

    return `
        <div class="listing-note">
            กำลังรอผู้ซื้อชำระเงิน
            <span
                class="countdown-timer"
                data-reserved-until="${escapeHTML(chicken.reservedUntil)}"
            >
                เหลือเวลา ${formatRemainingTime(chicken.reservedUntil)}
            </span>
        </div>
    `;
}

/* =========================
   Owner Listings Render
========================= */
function renderOwnerListings() {
    if (!ownerListingContainer) {
        return;
    }

    if (releaseExpiredReservations()) {
        saveOwnerListingState();
    }

    const totalItems = filteredOwnerListings.length;
    const totalPages = Math.max(
        1,
        Math.ceil(totalItems / ITEMS_PER_PAGE)
    );

    if (currentPage > totalPages) {
        currentPage = totalPages;
    }

    const startIndex =
        (currentPage - 1) * ITEMS_PER_PAGE;
    const endIndex = startIndex + ITEMS_PER_PAGE;
    const pageItems = filteredOwnerListings.slice(
        startIndex,
        endIndex
    );

    ownerListingContainer.innerHTML = "";

    pageItems.forEach(function (chicken, index) {
        const card = document.createElement("article");

        card.className = "owner-listing-card reveal";
        card.style.setProperty(
            "--reveal-delay",
            `${Math.min(index * 35, 175)}ms`
        );

        card.innerHTML = `
            <div class="listing-image-panel">
                <img
                    src="${escapeHTML(getChickenImagePath(chicken))}"
                    alt="${escapeHTML(chicken.name)}"
                    class="listing-image"
                    loading="lazy"
                >

                <div class="listing-status-wrap">
                    ${renderListingStatus(chicken)}
                </div>
            </div>

            <div class="listing-content">
                <div class="listing-main-row">
                    <div class="listing-title">
                        <h3 title="${escapeHTML(chicken.name)}">
                            ${escapeHTML(chicken.name)}
                        </h3>

                        <p>
                            <span>${escapeHTML(chicken.breed)}</span>
                            <span>${escapeHTML(chicken.age)}</span>
                            <span>${escapeHTML(chicken.gender)}</span>
                            <span>${escapeHTML(chicken.province)}</span>
                        </p>
                    </div>

                    <strong class="listing-price">
                        ${chicken.price.toLocaleString("th-TH")} บาท
                    </strong>
                </div>

                <div class="listing-meta-grid">
                    <div class="listing-meta-item">
                        <span>วันที่ลงประกาศ</span>
                        <strong>${formatThaiDate(chicken.createdAt)}</strong>
                    </div>

                    <div class="listing-meta-item">
                        <span>เลขคำสั่งซื้อ</span>
                        <strong>${chicken.orderId ? `#${chicken.orderId}` : "-"}</strong>
                    </div>
                </div>

                ${renderPendingNote(chicken)}

                <div class="listing-actions">
                    ${renderListingActions(chicken)}
                </div>
            </div>
        `;

        const chickenImage =
            card.querySelector(".listing-image");

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

        ownerListingContainer.appendChild(card);
    });

    updateResultText(startIndex, endIndex, totalItems);
    updateEmptyState(totalItems);
    renderPagination(totalPages);
    startReservationCountdown();
    window.MarketplaceShared.initializeRevealAnimation();
}

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
            "ไม่พบรายการประกาศขายที่แสดงได้";
        return;
    }

    const visibleStart = startIndex + 1;
    const visibleEnd = Math.min(endIndex, totalItems);

    resultText.textContent =
        `แสดง ${visibleStart}-${visibleEnd} ` +
        `จากทั้งหมด ${totalItems.toLocaleString("th-TH")} รายการ`;
}

function updateEmptyState(totalItems) {
    if (!emptyState || !pagination) {
        return;
    }

    const ownerTotal = getOwnerChickens().length;
    const hasItems = totalItems > 0;

    emptyState.hidden = hasItems;
    pagination.hidden = !hasItems;

    if (hasItems) {
        return;
    }

    if (ownerTotal === 0) {
        emptyTitle.textContent =
            "คุณยังไม่มีรายการประกาศขาย";
        emptyDescription.textContent =
            "เริ่มลงขายไก่ชนตัวแรกของคุณได้เลย";
        emptyCreateButton.hidden = false;
        emptyResetButton.hidden = true;
        return;
    }

    emptyTitle.textContent =
        "ไม่พบรายการที่ตรงกับตัวกรอง";
    emptyDescription.textContent =
        "ลองเปลี่ยนคำค้นหา สถานะ สายพันธุ์ หรือช่วงราคา";
    emptyCreateButton.hidden = true;
    emptyResetButton.hidden = false;
}

/* =========================
   Summary
========================= */
function updateListingSummary() {
    const ownerChickens = getOwnerChickens();

    const counts = ownerChickens.reduce(
        function (result, chicken) {
            const status = VALID_LISTING_STATUSES.includes(
                chicken.saleStatus
            )
                ? chicken.saleStatus
                : "inactive";

            result.total += 1;
            result[status] += 1;

            return result;
        },
        {
            total: 0,
            available: 0,
            pending_payment: 0,
            sold: 0,
            inactive: 0
        }
    );

    Object.entries(summaryElements).forEach(
        function ([key, element]) {
            if (!element) {
                return;
            }

            element.textContent =
                counts[key].toLocaleString("th-TH");
        }
    );

    updateSummaryActiveState();
}

function updateSummaryActiveState() {
    const selectedStatus = statusFilter
        ? statusFilter.value
        : "";

    summaryCards.forEach(function (card) {
        const isActive =
            card.dataset.summaryStatus === selectedStatus;

        card.classList.toggle("active", isActive);
        card.setAttribute(
            "aria-pressed",
            String(isActive)
        );
    });
}

/* =========================
   Search / Filter / Sort
========================= */
function applyOwnerListingFilters(
    options = {}
) {
    if (releaseExpiredReservations()) {
        saveOwnerListingState();
    }

    const shouldResetPage =
        options.resetPage !== false;

    const keyword = searchInput
        ? searchInput.value.trim().toLowerCase()
        : "";

    const selectedBreed = breedFilter
        ? breedFilter.value
        : "";

    const selectedStatus = statusFilter
        ? statusFilter.value
        : "";

    const selectedPrice = priceFilter
        ? priceFilter.value
        : "";

    filteredOwnerListings =
        getOwnerChickens().filter(function (chicken) {
            const keywordMatched =
                keyword === "" ||
                chicken.name.toLowerCase().includes(keyword);

            const breedMatched =
                selectedBreed === "" ||
                chicken.breed === selectedBreed;

            const statusMatched =
                selectedStatus === "" ||
                chicken.saleStatus === selectedStatus;

            let priceMatched = true;

            if (selectedPrice === "low") {
                priceMatched = chicken.price < 5000;
            }

            if (selectedPrice === "mid") {
                priceMatched =
                    chicken.price >= 5000 &&
                    chicken.price <= 10000;
            }

            if (selectedPrice === "high") {
                priceMatched = chicken.price > 10000;
            }

            return (
                keywordMatched &&
                breedMatched &&
                statusMatched &&
                priceMatched
            );
        });

    sortOwnerListings(
        filteredOwnerListings,
        sortFilter ? sortFilter.value : "latest"
    );

    if (shouldResetPage) {
        currentPage = 1;
    }

    updateListingSummary();
    renderOwnerListings();
}

function sortOwnerListings(data, sortValue) {
    if (sortValue === "price-low") {
        data.sort(function (a, b) {
            return a.price - b.price;
        });
        return;
    }

    if (sortValue === "price-high") {
        data.sort(function (a, b) {
            return b.price - a.price;
        });
        return;
    }

    data.sort(function (a, b) {
        return (
            new Date(b.createdAt).getTime() -
            new Date(a.createdAt).getTime()
        );
    });
}

function resetFilters() {
    if (searchInput) {
        searchInput.value = "";
    }

    if (breedFilter) {
        breedFilter.value = "";
    }

    if (statusFilter) {
        statusFilter.value = "";
    }

    if (priceFilter) {
        priceFilter.value = "";
    }

    if (sortFilter) {
        sortFilter.value = "latest";
    }

    currentPage = 1;
    applyOwnerListingFilters();
}

/* =========================
   Listing State Actions
========================= */
function closeListing(chickenId) {
    const chicken = findOwnerChickenById(chickenId);

    if (
        !chicken ||
        chicken.saleStatus !== "available"
    ) {
        return false;
    }

    // เปลี่ยนสถานะประกาศเป็นปิดประกาศ เจ้าของร้านเปิดขายใหม่ได้ภายหลัง
    chicken.saleStatus = "inactive";
    chicken.reservedAt = null;
    chicken.reservedUntil = null;
    chicken.orderId = null;
    chicken.buyerId = null;
    chicken.paymentStatus = null;

    saveOwnerListingState();
    applyOwnerListingFilters({
        resetPage: false
    });

    return true;
}

function reopenListing(chickenId) {
    const chicken = findOwnerChickenById(chickenId);

    if (
        !chicken ||
        chicken.saleStatus !== "inactive"
    ) {
        return false;
    }

    // เปิดประกาศกลับมาเป็นกำลังขายอีกครั้ง
    chicken.saleStatus = "available";

    saveOwnerListingState();
    applyOwnerListingFilters({
        resetPage: false
    });

    return true;
}

function deleteListing(chickenId) {
    const chicken = findOwnerChickenById(chickenId);

    if (
        !chicken ||
        chicken.saleStatus === "pending_payment" ||
        chicken.saleStatus === "sold"
    ) {
        return false;
    }

    deletedListingIds.add(Number(chickenId));

    chickens = chickens.filter(function (item) {
        return item.id !== Number(chickenId);
    });

    saveOwnerListingState();
    applyOwnerListingFilters({
        resetPage: false
    });

    return true;
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

    getVisiblePages(
        totalPages,
        currentPage
    ).forEach(function (page) {
        if (page === "...") {
            const dots = document.createElement("span");
            dots.textContent = "...";
            dots.className = "pagination-dots";
            paginationPages.appendChild(dots);
            return;
        }

        const pageButton =
            document.createElement("button");

        pageButton.type = "button";
        pageButton.className =
            "pagination-page" +
            (page === currentPage ? " active" : "");
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

        paginationPages.appendChild(pageButton);
    });

    previousPageButton.disabled =
        currentPage === 1;
    nextPageButton.disabled =
        currentPage === totalPages;
}

function getVisiblePages(totalPages, activePage) {
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

    if (activePage >= totalPages - 3) {
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
            filteredOwnerListings.length /
            ITEMS_PER_PAGE
        )
    );

    if (page < 1 || page > totalPages) {
        return;
    }

    currentPage = page;
    renderOwnerListings();

    const listingSection = document.querySelector(
        "#owner-listing-section"
    );

    if (listingSection) {
        listingSection.scrollIntoView({
            behavior: "smooth",
            block: "start"
        });
    }
}

/* =========================
   Navigation Helpers
========================= */
function openListingDetail(chickenId) {
    window.location.href =
        `Chicken-Detail.html?id=${encodeURIComponent(chickenId)}`;
}

function openEditListing(chickenId) {
    // เชื่อมไปหน้าแก้ไขประกาศตาม id ของไก่
    window.location.href =
        `edit-listing.html?id=${encodeURIComponent(chickenId)}`;
}

function openOrderDetail(orderId) {
    if (!orderId) {
        return;
    }

    window.location.href =
        `order-detail.html?orderId=${encodeURIComponent(orderId)}`;
}

function openBuyerDetail(buyerId) {
    if (!buyerId) {
        return;
    }

    window.location.href =
        `buyer-detail.html?buyerId=${encodeURIComponent(buyerId)}`;
}

/* =========================
   Sync Between Tabs
========================= */
window.addEventListener(
    "storage",
    function (event) {
        if (
            event.key !== OWNER_LISTING_STORAGE_KEY
        ) {
            return;
        }

        loadOwnerListingState();
        applyOwnerListingFilters({
            resetPage: false
        });
    }
);

/* =========================
   Initial Data Loading
   ใช้ข้อมูล Demo ทันทีเมื่อยังไม่กำหนด Backend Endpoint
========================= */
function normalizeOwnerListingsResponse(response) {
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

function renderLoadedOwnerListings() {
    loadOwnerListingState();
    applyOwnerListingFilters();

    if (!ownerMarketplaceState) return;

    if (getOwnerChickens().length === 0) {
        ownerMarketplaceState.showEmpty("ยังไม่มีรายการประกาศขาย");
    } else {
        ownerMarketplaceState.showContent(
            "โหลดรายการประกาศขายเรียบร้อยแล้ว"
        );
    }
}

async function loadOwnerListings() {
    const marketplaceApi = window.MarketplaceApi;

    if (
        !ownerMarketplaceState ||
        !ownerMarketplaceUi ||
        !marketplaceApi ||
        !ownerMarketplaceUi.isEndpointConfigured("marketplaceListings")
    ) {
        renderLoadedOwnerListings();
        return;
    }

    ownerMarketplaceState.begin("กำลังโหลดรายการประกาศขาย");

    try {
        const query = CURRENT_FARM_OWNER_ID
            ? { ownerId: CURRENT_FARM_OWNER_ID }
            : {};
        const listings = normalizeOwnerListingsResponse(
            await marketplaceApi.getListings(query)
        );

        if (!listings) {
            throw new Error("รูปแบบข้อมูลรายการประกาศจาก Backend ไม่ถูกต้อง");
        }

        chickens = listings;
        renderLoadedOwnerListings();
    } catch (error) {
        if (resultText) {
            resultText.textContent = "ไม่สามารถโหลดข้อมูลได้";
        }

        ownerMarketplaceState.showError(
            error && error.message
                ? error.message
                : "ไม่สามารถโหลดรายการประกาศขายได้"
        );
    }
}

const ownerListingRetryButton = document.querySelector(
    "#owner-listing-retry-btn"
);

if (ownerListingRetryButton) {
    ownerListingRetryButton.addEventListener("click", loadOwnerListings);
}

loadOwnerListings();
