(function (window) {
    const reservationDurationMs = 24 * 60 * 60 * 1000;
    const currentUserId = 15;
    const breeds = ["พม่า", "ไทย", "ญี่ปุ่น", "ไซง่อน"];
    const farms = [
        {
            name: "ฟาร์มลูกบาศก์",
            profileImage: "../../shared/images/farm-1.png",
            coverImage: "../../shared/images/about-farm.png"
        },
        {
            name: "บ้านไก่ชนออนไลน์",
            profileImage: null,
            coverImage: null
        },
        {
            name: "ซุ้มไก่ทอง",
            profileImage: null,
            coverImage: "../../shared/images/farm-1.png"
        },
        {
            name: "ฟาร์มนักสู้",
            profileImage: "",
            coverImage: ""
        },
        {
            name: "ฟาร์มมังกรทอง",
            profileImage: null,
            coverImage: "../../shared/images/farm-1.png"
        },
        {
            name: "ซุ้มเพชรดำ",
            profileImage: null,
            coverImage: "../../shared/images/farm-1.png"
        }
    ];
    const names = [
        "เจ้าทอง",
        "เจ้าแดง",
        "เจ้าสายฟ้า",
        "เจ้าเพชร",
        "เจ้านิล",
        "เจ้าคม",
        "เจ้ามังกร",
        "เจ้าพายุ",
        "เจ้าเสือ",
        "เจ้าเพลิง",
        "เจ้าดำ",
        "เจ้าทัพ"
    ];
    const provinces = [
        "นครศรีธรรมราช",
        "สุราษฎร์ธานี",
        "กรุงเทพมหานคร",
        "ชลบุรี"
    ];

    function createDemoChickens() {
        return Array.from({ length: 48 }, function (_, index) {
            const selectedFarm = farms[index % farms.length];
            const breed = breeds[index % breeds.length];
            const gender = index % 4 === 0 ? "เพศเมีย" : "เพศผู้";
            const demoReservedAt = new Date();
            const demoReservedUntil = new Date(
                demoReservedAt.getTime() + reservationDurationMs
            );
            const saleStatus = index === 0
                ? "pending_payment"
                : index === 1
                    ? "sold"
                    : "available";

            return {
                id: index + 1,
                name: names[index % names.length] + " " + (index + 1),
                breed: breed,
                age: 9 + (index % 9) + " เดือน",
                gender: gender,
                price: 3500 + ((index * 1250) % 17000),
                farm: selectedFarm.name,
                farmImage: selectedFarm.profileImage,
                farmCoverImage: selectedFarm.coverImage,
                rating: Number((4.5 + (index % 5) * 0.1).toFixed(1)),
                reviewCount: 12 + (index % 40),
                province: provinces[index % provinces.length],
                image: index % 7 === 0
                    ? ""
                    : "../../shared/images/main.png",
                createdOrder: index,
                isNew: index >= 40,
                popularity: 50 + ((index * 13) % 300),
                saleStatus: saleStatus,
                reservedAt: saleStatus === "pending_payment"
                    ? demoReservedAt.toISOString()
                    : null,
                reservedUntil: saleStatus === "pending_payment"
                    ? demoReservedUntil.toISOString()
                    : null,
                buyerId: saleStatus === "available" ? null : currentUserId,
                orderId: saleStatus === "pending_payment"
                    ? 1001
                    : saleStatus === "sold"
                        ? 1002
                        : null,
                paymentStatus: saleStatus === "pending_payment"
                    ? "pending"
                    : saleStatus === "sold"
                        ? "paid"
                        : null
            };
        });
    }

    window.MarketplaceDemoData = Object.freeze({
        currentUserId: currentUserId,
        reservationDurationMs: reservationDurationMs,
        chickens: createDemoChickens()
    });
})(window);
