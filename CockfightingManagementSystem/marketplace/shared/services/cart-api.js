(function (window) {
    function getCartEndpoint() {
        const endpoints = window.SuperKaichonConfig
            ? window.SuperKaichonConfig.endpoints
            : null;
        const endpoint = endpoints ? endpoints.cart : null;

        if (!endpoint) {
            throw new Error(
                "TODO: กำหนด Cart endpoint จาก Backend จริง"
            );
        }

        return endpoint;
    }

    window.CartApi = Object.freeze({
        getCart: function () {
            const api = window.SuperKaichonApi;

            if (!api) {
                return Promise.reject(new Error("ยังไม่ได้โหลด API Client"));
            }

            return api.apiRequest(getCartEndpoint(), { method: "GET" });
        }
    });
})(window);
