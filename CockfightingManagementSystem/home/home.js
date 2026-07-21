// ─── การดึง Element เดิม ───
const mobileMenuBtn = document.querySelector("#mobile-menu-btn");
const navMenu = document.querySelector("#nav-menu");

const breedCardContainer = document.querySelector("#breed-card-container");
const chickenCardContainer = document.querySelector("#chicken-card-container");
const farmCardContainer = document.querySelector("#farm-card-container");

const searchSection = document.querySelector("#search-section");
const searchInput = document.querySelector("#search-input");
const breedFilter = document.querySelector("#breed-filter");
const priceFilter = document.querySelector("#price-filter");
const searchBtn = document.querySelector("#search-btn");
const marketSection = document.querySelector("#market-section");

const API_ENDPOINTS = {
    memberStats: "../api/member-stats.php",
    chickens: "../api/chickens.php", // TODO: เปลี่ยน URL ให้ตรงกับ endpoint PHP ของเพื่อนเมื่อพร้อม
    farms: "../api/farms.php" // TODO: เปลี่ยน URL ให้ตรงกับ endpoint PHP ของเพื่อนเมื่อพร้อม
};
const USE_BACKEND_COLLECTIONS = false; // TODO: เปลี่ยนเป็น true เมื่อ api/chickens.php และ api/farms.php พร้อมใช้งาน
const FALLBACK_CHICKEN_IMAGE = "../shared/images/main.png";
const FALLBACK_FARM_IMAGE = "../shared/images/logo.png";

function imageOrFallback(imagePath, fallbackImage = FALLBACK_CHICKEN_IMAGE) {
    return imagePath && String(imagePath).trim() !== "" ? imagePath : fallbackImage;
}

// ─── การดึง Element เพิ่มเติมสำหรับกล่องเมนูวิ่งตาม ───
const menuContainer = document.querySelector('.nav-menu');
const menuCapsule = document.querySelector("#nav-menu");
const menuLinks = document.querySelectorAll("#nav-menu a");

const memberStatsCard = document.querySelector("#member-stats-card");
const farmCountElement = document.querySelector("#farm-count");
const memberCountElement = document.querySelector("#member-count");



// ระบบเปิด-ปิดเมนูมือถือ (เพิ่ม Safety Check กันโค้ดพัง)
if (mobileMenuBtn && navMenu) {
    mobileMenuBtn.addEventListener("click", () => {
        navMenu.classList.toggle("show");
        mobileMenuBtn.classList.toggle("open");
    });
}

/* =========================
   Demo Data
========================= */


let chickens = [
{ name: "เจ้าทอง", breed: "พม่า", age: "12 เดือน", gender: "เพศผู้", price: 4500, farm: "ฟาร์มพัฒนา", image: "../shared/images/main.png" },
{ name: "เจ้าแดง", breed: "ไทย", age: "10 เดือน", gender: "เพศผู้", price: 12000, farm: "บ้านไก่ชน", image: "../shared/images/main.png" },
{ name: "เจ้าสายฟ้า", breed: "ญี่ปุ่น", age: "9 เดือน", gender: "เพศผู้", price: 8900, farm: "ฟาร์มลูกบาศก์", image: "../shared/images/main.png" },
{ name: "เจ้าเพชร", breed: "ไซง่อน", age: "14 เดือน", gender: "เพศผู้", price: 6000, farm: "ซุ้มไก่ทอง", image: "../shared/images/main.png" },
{ name: "เจ้านิล", breed: "พม่า", age: "11 เดือน", gender: "เพศผู้", price: 7500, farm: "ซุ้มไก่เมือง", image: "../shared/images/main.png" },
{ name: "เจ้าคม", breed: "ไทย", age: "13 เดือน", gender: "เพศผู้", price: 9800, farm: "ฟาร์มนักสู้", image: "../shared/images/main.png" },
{ name: "เจ้ามังกร", breed: "ไซง่อน", age: "15 เดือน", gender: "เพศผู้", price: 15000, farm: "ฟาร์มมังกรทอง", image: "../shared/images/main.png" },
{ name: "เจ้าพายุ", breed: "ญี่ปุ่น", age: "10 เดือน", gender: "เพศผู้", price: 11000, farm: "ฟาร์มสายฟ้า", image: "../shared/images/main.png" },
{ name: "เจ้าเสือ", breed: "พม่า", age: "16 เดือน", gender: "เพศผู้", price: 18000, farm: "ซุ้มเสือดำ", image: "../shared/images/main.png" },
{ name: "เจ้าเพลิง", breed: "ไทย", age: "12 เดือน", gender: "เพศผู้", price: 5200, farm: "บ้านไก่เพลิง", image: "../shared/images/main.png" }
];

let farms = [
{ name: "ฟาร์มลูกบาศก์", province: "นครศรีธรรมราช", chickens: 54, rating: 4.9, image: "../shared/images/logo.png" },
{ name: "ซุ้มไก่ชนเมือง", province: "สุราษฎร์ธานี", chickens: 38, rating: 4.8, image: "../shared/images/logo.png" },
{ name: "บ้านไก่ชนออนไลน์", province: "กรุงเทพมหานคร", chickens: 42, rating: 4.7, image: "../shared/images/logo.png" }
];

/* =========================
   Render Breed
========================= */
function renderBreeds() {
    if (!breedCardContainer) return; // ถ้าไม่มีคอนเทนเนอร์ในหน้านี้ ให้ข้ามไป
    breedCardContainer.innerHTML = "";
    breeds.forEach(breed => {
        const card = document.createElement("div");
        card.className = "breed-card";
        card.innerHTML = `
            <img src="${breed.image}" alt="${breed.name}">
            <div class="breed-info">
                <h3>${breed.name}</h3>
                <p>${breed.count}</p>
            </div>
        `;
        breedCardContainer.appendChild(card);
    });
}

/* =========================
   Render Chickens
========================= */
function renderChickens(data) {
    if (!chickenCardContainer) return;
    chickenCardContainer.innerHTML = "";

    if (data.length === 0) {
        chickenCardContainer.innerHTML = `
            <div class="empty-card" style="grid-column: 1/-1; text-align: center; padding: 40px; color: #888;">
                ไม่พบรายการไก่ชนที่ค้นหา
            </div>
        `;
        return;
    }

    data.forEach(function (chicken, index) {
        const card = document.createElement("div");
        const chickenImage = imageOrFallback(chicken.image, FALLBACK_CHICKEN_IMAGE);
        card.className = "chicken-card reveal fade-up";
        card.style.setProperty("--reveal-delay", `${(index % 4) * 90}ms`);
        card.innerHTML = `
            <img src="${chickenImage}" alt="${chicken.name}" class="chicken-img" onerror="this.onerror=null; this.src='${FALLBACK_CHICKEN_IMAGE}';">
            <div class="chicken-body">
                <div class="chicken-top">
                    <h3>${chicken.name}</h3>
                    <span class="price">฿${chicken.price.toLocaleString()}</span>
                </div>
                <div class="chicken-meta">
                    <span>${chicken.breed}</span>
                    <span>${chicken.age}</span>
                    <span>${chicken.gender}</span>
                </div>
                <div class="farm-name">
                    <i class="bi bi-shop"></i> ${chicken.farm}
                <div class="chicken-actions">
                    <button class="detail-btn">ดูรายละเอียด</button>
                    <button class="buy-link-btn">ดูฟาร์ม <i class="bi bi-chevron-right"></i></button>
                </div>
        `;
        chickenCardContainer.appendChild(card);
    });
}

/* =========================
   Render Farms
========================= */
function renderFarms() {
    if (!farmCardContainer) return;
    farmCardContainer.innerHTML = "";
    farms.forEach(function (farm, index) {
        const card = document.createElement("div");
        const farmImage = imageOrFallback(farm.image, FALLBACK_FARM_IMAGE);
        card.className = "farm-card reveal fade-up";
        card.style.setProperty("--reveal-delay", `${(index % 3) * 100}ms`);
        card.innerHTML = `
            <img src="${farmImage}" alt="${farm.name}" class="farm-logo" onerror="this.onerror=null; this.src='${FALLBACK_FARM_IMAGE}';">
            <h3>${farm.name}</h3>
            <p><i class="bi bi-geo-alt"></i> ${farm.province}</p>
            <div class="farm-stats">
                <div>
                    <strong>${farm.chickens}</strong>
                    <span>ไก่ชน</span>
                </div>
                <div>
                    <strong>${farm.rating}</strong>
                    <span>คะแนน</span>
                </div>
            </div>
            <button class="farm-btn">ดูฟาร์ม</button>
        `;
        farmCardContainer.appendChild(card);
    });
}

/* =========================
   Search Filter
========================= */
function filterChickens(shouldScroll = false) {
    if (!searchInput || !breedFilter || !priceFilter) return;

    const keyword = searchInput.value.trim().toLowerCase();
    const breedValue = breedFilter.value;
    const priceValue = priceFilter.value;

    let result = chickens.filter(chicken => {
        const matchKeyword =
            chicken.name.toLowerCase().includes(keyword) ||
            chicken.farm.toLowerCase().includes(keyword) ||
            chicken.breed.toLowerCase().includes(keyword);

        const matchBreed = breedValue === "" || chicken.breed === breedValue;

        let matchPrice = true;
        if (priceValue === "low") matchPrice = chicken.price < 5000;
        else if (priceValue === "mid") matchPrice = chicken.price >= 5000 && chicken.price <= 10000;
        else if (priceValue === "high") matchPrice = chicken.price > 10000;

        return matchKeyword && matchBreed && matchPrice;
    });

    renderChickens(result);
    observeReveal(chickenCardContainer);

    if (shouldScroll && marketSection) {
        marketSection.scrollIntoView({ behavior: "smooth" });
    }
}

// ผูก Event ค้นหา (ใส่ Safety Check ป้องกันกรณีไม่มีปุ่มในหน้าเว็บบางหน้า)
if (searchBtn) {
    searchBtn.addEventListener("click", function () {
        const isMobile = window.matchMedia("(max-width: 560px)").matches;

        if (isMobile && searchSection && !searchSection.classList.contains("search-open")) {
            searchSection.classList.add("search-open");

            setTimeout(function () {
                if (searchInput) {
                    searchInput.focus();
                }
            }, 250);

            return;
        }

        filterChickens(true);
    });
}

if (searchInput) {
    searchInput.addEventListener("keydown", (event) => {
        if (event.key === "Enter") {
            filterChickens(true);
        }
    });
}

if (breedFilter) {
    breedFilter.addEventListener("change", () => filterChickens(false));
}

if (priceFilter) {
    priceFilter.addEventListener("change", () => filterChickens(false));
}

/* =========================
   ฟังก์ชันระบบกล่องวิ่งตามเมนู (Sliding Navigation)
========================= */
function moveIndicator(element) {
    if (menuIndicator) {
        menuIndicator.style.left = `${element.offsetLeft}px`;
        menuIndicator.style.width = `${element.offsetWidth}px`;
    }
}

if (menuCapsule && menuLinks.length > 0) {
    const indicator = document.createElement("span");
    indicator.className = "nav-indicator";
    menuCapsule.prepend(indicator);

    function moveIndicator(target) {
        indicator.style.width = `${target.offsetWidth}px`;
        indicator.style.left = `${target.offsetLeft}px`;
        indicator.style.opacity = "1";
    }

    const activeLink = document.querySelector("#nav-menu a.active") || menuLinks[0];

    setTimeout(function () {
        moveIndicator(activeLink);
    }, 100);

    menuLinks.forEach(function (link) {
        link.addEventListener("mouseenter", function () {
            moveIndicator(link);
        });

        link.addEventListener("click", function (event) {
            event.preventDefault();

            menuLinks.forEach(function (item) {
                item.classList.remove("active");
            });

            link.classList.add("active");
            moveIndicator(link);

            // เลื่อนไปยัง section เป้าหมายแบบนุ่มนวล
            const targetId = link.getAttribute("href");
            if (targetId && targetId.startsWith("#")) {
                const targetSection = document.querySelector(targetId);
                if (targetSection) {
                    targetSection.scrollIntoView({ behavior: "smooth" });
                }
            }

            // ปิดเมนูมือถือหลังจากกดเลือกเมนู
            if (navMenu && navMenu.classList.contains("show")) {
                navMenu.classList.remove("show");
                if (mobileMenuBtn) mobileMenuBtn.classList.remove("open");
            }
        });
    });

    menuCapsule.addEventListener("mouseleave", function () {
        const currentActive = document.querySelector("#nav-menu a.active") || activeLink;
        moveIndicator(currentActive);
    });

    window.addEventListener("resize", function () {
        const currentActive = document.querySelector("#nav-menu a.active") || activeLink;
        moveIndicator(currentActive);
    });
}

async function loadMemberStats() {
    if (!memberStatsCard) return; // ถ้าไม่มีกล่องสมาชิกในหน้านี้ ให้ข้ามไป

    try {
        memberStatsCard.classList.add("loading");

        // TODO: เปลี่ยน URL ใน API_ENDPOINTS.memberStats ให้ตรงกับไฟล์ PHP/API ของเพื่อน
        const response = await fetch(API_ENDPOINTS.memberStats);

        if (!response.ok) {
            throw new Error("ไม่สามารถโหลดข้อมูลสมาชิกได้");
        }

        const data = await response.json();

        const farmCount = Number(data.farm_count) || 0;
        const memberCount = Number(data.member_count) || 0;

        if (farmCountElement) animateNumber(farmCountElement, farmCount);
        if (memberCountElement) animateNumber(memberCountElement, memberCount);

    } catch (error) {
        console.warn("ใช้ข้อมูลตัวอย่างแทน:", error);

        // ข้อมูลตัวอย่างตอนยังไม่มี Backend
        if (farmCountElement) animateNumber(farmCountElement, 54);
        if (memberCountElement) animateNumber(memberCountElement, 1254);

    } finally {
        memberStatsCard.classList.remove("loading");
    }
}

function animateNumber(element, targetNumber) {
    let currentNumber = 0;
    const duration = 900;
    const frameRate = 16;
    const totalFrames = Math.round(duration / frameRate);
    const increment = targetNumber / totalFrames;

    const counter = setInterval(function () {
        currentNumber += increment;

        if (currentNumber >= targetNumber) {
            currentNumber = targetNumber;
            clearInterval(counter);
        }

        element.textContent = Math.floor(currentNumber).toLocaleString();
    }, frameRate);
}

loadMemberStats();

const premiumCarousel = document.querySelector("#premium-carousel");
const premiumTrack = document.querySelector("#breed-card-container");
const premiumPrevBtn = document.querySelector("#premium-prev");
const premiumNextBtn = document.querySelector("#premium-next");

let premiumAutoSlide;
let premiumResumeTimer;

// เริ่มระบบ Carousel เฉพาะเมื่อมี Element ครบ (กัน error หน้าที่ไม่มีส่วนนี้)
const hasPremiumCarousel = !!(premiumCarousel && premiumTrack && premiumPrevBtn && premiumNextBtn);

function getPremiumScrollAmount() {
    const firstCard = premiumTrack.querySelector(".breed-card");

    if (!firstCard) {
        return 300;
    }

    const trackStyle = window.getComputedStyle(premiumTrack);
    const gap = parseFloat(trackStyle.gap) || 18;

    return firstCard.getBoundingClientRect().width + gap;
}

function scrollPremiumCarousel(direction) {
    premiumTrack.scrollBy({
        left: getPremiumScrollAmount() * direction,
        behavior: "smooth"
    });
}

/* เลื่อนไปข้างหน้า และวนกลับต้นเมื่อถึงท้าย */
function autoScrollPremium() {
    const maxScrollLeft =
        premiumTrack.scrollWidth - premiumTrack.clientWidth;

    const nearEnd =
        premiumTrack.scrollLeft >= maxScrollLeft - 10;

    if (nearEnd) {
        premiumTrack.scrollTo({
            left: 0,
            behavior: "smooth"
        });
    } else {
        scrollPremiumCarousel(1);
    }
}

function startPremiumAutoSlide() {
    stopPremiumAutoSlide();

    premiumAutoSlide = setInterval(function () {
        autoScrollPremium();
    }, 3000);
}

function stopPremiumAutoSlide() {
    clearInterval(premiumAutoSlide);
}

function pauseAndResumePremiumAutoSlide() {
    stopPremiumAutoSlide();
    clearTimeout(premiumResumeTimer);

    premiumResumeTimer = setTimeout(function () {
        startPremiumAutoSlide();
    }, 4000);
}

if (hasPremiumCarousel) {
    premiumPrevBtn.addEventListener("click", function () {
        scrollPremiumCarousel(-1);
        pauseAndResumePremiumAutoSlide();
    });

    premiumNextBtn.addEventListener("click", function () {
        scrollPremiumCarousel(1);
        pauseAndResumePremiumAutoSlide();
    });

    /* หยุดตอนเอาเมาส์ชี้ */
    premiumCarousel.addEventListener("mouseenter", stopPremiumAutoSlide);
    premiumCarousel.addEventListener("mouseleave", startPremiumAutoSlide);

    /* หยุดชั่วคราวตอนผู้ใช้เลื่อนด้วยนิ้ว */
    premiumTrack.addEventListener("touchstart", stopPremiumAutoSlide, {
        passive: true
    });

    premiumTrack.addEventListener("touchend", function () {
        pauseAndResumePremiumAutoSlide();
    }, {
        passive: true
    });

    /* หยุดเมื่อเปลี่ยนแท็บ ช่วยประหยัดทรัพยากร */
    document.addEventListener("visibilitychange", function () {
        if (document.hidden) {
            stopPremiumAutoSlide();
        } else {
            startPremiumAutoSlide();
        }
    });

    startPremiumAutoSlide();
}

/* =========================
   Popup Modal: ทำความรู้จักกับ Super Kaichon
========================= */
const aboutCards = document.querySelectorAll(".about-card, .about-image-card");
const aboutModalOverlay = document.querySelector("#about-modal-overlay");
const aboutModalBox = document.querySelector("#about-modal-box");
const aboutModalClose = document.querySelector("#about-modal-close");
const aboutModalBtn = document.querySelector("#about-modal-btn");
const aboutModalTitle = document.querySelector("#about-modal-title");
const aboutModalDesc = document.querySelector("#about-modal-desc");
const aboutModalIcon = document.querySelector("#about-modal-icon");
const aboutModalImage = document.querySelector("#about-modal-image");

/*
    ตอนนี้ใช้ Demo Data ก่อน
    อนาคตสามารถเปลี่ยนเป็น fetch("../api/about-content.php") ได้
*/
const aboutContent = {
    buyer: {
        icon: "bi-search-heart",
        title: "สำหรับผู้ซื้อ",
        paragraphs: [
            "Super Kaichon ช่วยให้ผู้ซื้อสามารถค้นหาไก่ชนจากหลายฟาร์มได้ในที่เดียว โดยไม่จำเป็นต้องค้นหาข้อมูลจากหลายแหล่งให้เสียเวลา",

            "ผู้ซื้อสามารถกรองข้อมูลตามสายพันธุ์ ช่วงราคา หรือฟาร์มที่สนใจได้ เพื่อช่วยให้การเลือกไก่ชนตรงกับความต้องการมากขึ้น",

            "นอกจากนี้ผู้ซื้อยังสามารถดูรายละเอียดของไก่ชน ข้อมูลฟาร์มต้นทาง และข้อมูลประกอบการตัดสินใจก่อนติดต่อหรือสั่งซื้อได้"
        ]
    },

    farm: {
        icon: "bi-house-heart",
        title: "สำหรับเจ้าของฟาร์ม",
image: "../shared/images/main.png",

        paragraphs: [
            "ระบบนี้ช่วยให้เจ้าของฟาร์มสามารถจัดการข้อมูลฟาร์มและข้อมูลไก่ชนได้อย่างเป็นระบบ ลดการจดบันทึกแบบกระดาษที่อาจสูญหายหรือค้นหายาก",

            "เจ้าของฟาร์มสามารถเพิ่มข้อมูลไก่ชน ลงประกาศขาย จัดการคำสั่งซื้อ และติดตามข้อมูลต่าง ๆ ภายในระบบเดียว",

            "การมีข้อมูลฟาร์มและรายการไก่ชนที่ชัดเจนช่วยเพิ่มความน่าเชื่อถือ และทำให้ผู้ซื้อสามารถตัดสินใจได้ง่ายขึ้น"
        ]
    },

    trust: {
        icon: "bi-shield-check",
        title: "ผู้พัฒนา",

        paragraphs: [
            "ระบบ Super Kaichon เป็นส่วนหนึ่งของปริญญานิพนธ์ สาขาเทคโนโลยีสารสนเทศ มหาวิทยาลัยมหาสารคาม",

        ],

        developers: [
            {
                name: "นายกฤษณะ เทพาฤทธิ์",
                role: "66011211002",
image: "../shared/images/66011211002.jpg"
            },
            {
                name: "นายชยางกูร หมอยา",
                role: "66011211108",
image: "../shared/images/66011211108.jpg"
            }
        ]
    }
};

let lastFocusedElement = null;

function openAboutModal(key) {
    if (!aboutModalOverlay || !aboutContent[key]) return;

    const content = aboutContent[key];

    if (aboutModalTitle) {
        aboutModalTitle.textContent = content.title;
    }

    if (aboutModalIcon) {
        aboutModalIcon.innerHTML = `<i class="bi ${content.icon}"></i>`;
    }

    if (aboutModalImage) {
        if (content.image) {
            aboutModalImage.src = content.image;
            aboutModalImage.alt = content.title;
            aboutModalImage.style.display = "block";

            aboutModalImage.onerror = function () {
                aboutModalImage.onerror = null;
                aboutModalImage.src = FALLBACK_CHICKEN_IMAGE;
            };
        } else {
            aboutModalImage.removeAttribute("src");
            aboutModalImage.alt = "";
            aboutModalImage.style.display = "none";
        }
    }

    if (aboutModalDesc) {
        let modalContent = "";

        if (content.paragraphs && content.paragraphs.length > 0) {
            modalContent += content.paragraphs
                .map(function (paragraph) {
                    return `<p>${paragraph}</p>`;
                })
                .join("");
        }

        if (content.sectionTitle) {
            modalContent += `
                <h3 class="about-developer-title">
                    ${content.sectionTitle}
                </h3>
            `;
        }

        if (content.developers && content.developers.length > 0) {
            modalContent += `
                <div class="about-developer-list">
                    ${content.developers.map(function (developer) {
                        return `
                            <div class="about-developer-card">
                                <img 
                                    src="${developer.image}" 
                                    alt="${developer.name}" 
                                    class="about-developer-photo"
                                    onerror="this.onerror=null; this.src='${FALLBACK_FARM_IMAGE}';"
                                >

                                <div class="about-developer-info">
                                    <h4>${developer.name}</h4>
                                    <p>${developer.role}</p>
                                </div>
                            </div>
                        `;
                    }).join("")}
                </div>
            `;
        }

        aboutModalDesc.innerHTML = modalContent;
    }

    lastFocusedElement = document.activeElement;

    aboutModalOverlay.classList.add("show");
    document.body.classList.add("modal-open");

    if (aboutModalClose) {
        aboutModalClose.focus();
    }
}

function closeAboutModal() {
    if (!aboutModalOverlay) return;

    aboutModalOverlay.classList.remove("show");
    document.body.classList.remove("modal-open");

    if (lastFocusedElement) {
        lastFocusedElement.focus();
    }
}

/* เปิด Modal เมื่อกดการ์ด */
if (aboutCards.length > 0 && aboutModalOverlay) {
    aboutCards.forEach(function (card) {
        card.addEventListener("click", function () {
            const key = card.dataset.about || card.dataset.modal;
            openAboutModal(key);
        });
    });

    if (aboutModalClose) {
        aboutModalClose.addEventListener("click", closeAboutModal);
    }

    if (aboutModalBtn) {
        aboutModalBtn.addEventListener("click", closeAboutModal);
    }

    aboutModalOverlay.addEventListener("click", function (event) {
        if (event.target === aboutModalOverlay) {
            closeAboutModal();
        }
    });

    document.addEventListener("keydown", function (event) {
        if (
            event.key === "Escape" &&
            aboutModalOverlay.classList.contains("show")
        ) {
            closeAboutModal();
        }
    });
}

async function fetchCollection(endpoint) {
    const response = await fetch(endpoint);

    if (!response.ok) {
        throw new Error(`โหลดข้อมูลไม่สำเร็จ: ${endpoint}`);
    }

    const data = await response.json();

    if (Array.isArray(data)) {
        return data;
    }

    return data.items || data.data || [];
}

function normalizeChicken(item) {
    return {
        name: item.name || item.chicken_name || "ไม่ระบุชื่อ",
        breed: item.breed || item.chicken_breed || "ไม่ระบุสายพันธุ์",
        age: item.age || item.chicken_age || "-",
        gender: item.gender || item.chicken_gender || "-",
        price: Number(item.price || item.chicken_price) || 0,
        farm: item.farm || item.farm_name || "ไม่ระบุฟาร์ม",
        image: imageOrFallback(item.image || item.image_path || item.chicken_image, FALLBACK_CHICKEN_IMAGE)
    };
}

function normalizeFarm(item) {
    return {
        name: item.name || item.farm_name || "ไม่ระบุชื่อฟาร์ม",
        province: item.province || item.farm_province || item.address || "-",
        chickens: Number(item.chickens || item.chicken_count) || 0,
        rating: Number(item.rating) || 0,
        image: imageOrFallback(item.image || item.image_path || item.farm_image, FALLBACK_FARM_IMAGE)
    };
}

async function loadHomeCollections() {
    if (!USE_BACKEND_COLLECTIONS) {
        renderRandomMarketChickens();
        renderFarms();
        observeReveal(document);
        return;
    }

    try {
        const backendChickens = await fetchCollection(API_ENDPOINTS.chickens);
        if (backendChickens.length > 0) {
            chickens = backendChickens.map(normalizeChicken);
        }
    } catch (error) {
        console.warn("ใช้ข้อมูลไก่ชนตัวอย่างแทน:", error);
    }

    try {
        const backendFarms = await fetchCollection(API_ENDPOINTS.farms);
        if (backendFarms.length > 0) {
            farms = backendFarms.map(normalizeFarm);
        }
    } catch (error) {
        console.warn("ใช้ข้อมูลฟาร์มตัวอย่างแทน:", error);
    }

    renderRandomMarketChickens();
    renderFarms();
    observeReveal(document);
}

/* =========================
   Random Market Chicken
========================= */
function getRandomItems(array, count) {
    const copiedArray = [...array];

    for (let i = copiedArray.length - 1; i > 0; i--) {
        const randomIndex = Math.floor(Math.random() * (i + 1));
        [copiedArray[i], copiedArray[randomIndex]] = [copiedArray[randomIndex], copiedArray[i]];
    }

    return copiedArray.slice(0, count);
}

function renderRandomMarketChickens() {
    const randomChickens = getRandomItems(chickens, 8);
    renderChickens(randomChickens);
}

/* =========================
   Market Carousel Control
========================= */
const marketTrack = document.querySelector("#chicken-card-container");
const marketPrevBtn = document.querySelector("#market-prev");
const marketNextBtn = document.querySelector("#market-next");

function getMarketScrollAmount() {
    const firstCard = marketTrack?.querySelector(".chicken-card");

    if (!firstCard) {
        return 300;
    }

    const trackStyle = window.getComputedStyle(marketTrack);
    const gap = parseFloat(trackStyle.gap) || 18;

    return firstCard.getBoundingClientRect().width + gap;
}

function scrollMarketCarousel(direction) {
    if (!marketTrack) return;

    marketTrack.scrollBy({
        left: getMarketScrollAmount() * direction,
        behavior: "smooth"
    });
}

if (marketPrevBtn && marketNextBtn && marketTrack) {
    marketPrevBtn.addEventListener("click", function () {
        scrollMarketCarousel(-1);
    });

    marketNextBtn.addEventListener("click", function () {
        scrollMarketCarousel(1);
    });
}



/* =========================
   Scroll Reveal Animation System
   (Intersection Observer - ไม่ใช้ Library ภายนอก)
========================= */
const revealObserver = ("IntersectionObserver" in window)
    ? new IntersectionObserver(function (entries, observer) {
        entries.forEach(function (entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add("show");
                observer.unobserve(entry.target); // เล่นครั้งเดียวพอ ไม่ต้องเล่นซ้ำตอนเลื่อนกลับขึ้น
            }
        });
    }, {
        threshold: 0.15,
        rootMargin: "0px 0px -60px 0px"
    })
    : null;

/* ผูก Observer ให้ element .reveal ทุกตัวที่ยังไม่เคยถูกสังเกต */
function observeReveal(scope) {
    const root = scope || document;
    const targets = root.querySelectorAll(".reveal:not(.reveal-bound)");

    targets.forEach(function (target) {
        target.classList.add("reveal-bound");

        if (revealObserver) {
            revealObserver.observe(target);
        } else {
            // เบราว์เซอร์เก่าที่ไม่รองรับ IntersectionObserver ให้แสดงผลทันที
            target.classList.add("show");
        }
    });
}

// สังเกต element ที่มีอยู่ในหน้าตั้งแต่แรก
observeReveal(document);

/* =========================
   Init เปิดใช้งานครั้งแรก
========================= */
renderBreeds();
loadHomeCollections();

// สังเกต Card ที่เพิ่ง Render ใหม่จาก JS (chicken-card / farm-card)
observeReveal(document);
