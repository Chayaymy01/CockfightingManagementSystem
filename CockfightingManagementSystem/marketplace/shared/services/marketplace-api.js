(function (window) {
    function getEndpoint(name) {
        const endpoints = window.SuperKaichonConfig
            ? window.SuperKaichonConfig.endpoints
            : null;
        const endpoint = endpoints ? endpoints[name] : null;

        if (!endpoint) {
            throw new Error(
                "TODO: กำหนด Marketplace endpoint " + name + " จาก Backend จริง"
            );
        }

        return endpoint;
    }

    window.MarketplaceApi = Object.freeze({
        getListings: function (query) {
            const params = new URLSearchParams(query || {});
            const queryString = params.toString();
            const endpoint = getEndpoint("marketplaceListings");
            const api = window.SuperKaichonApi;

            if (!api) {
                return Promise.reject(new Error("ยังไม่ได้โหลด API Client"));
            }

            return api.apiRequest(
                endpoint + (queryString ? "?" + queryString : ""),
                { method: "GET" }
            );
        }
    });
})(window);
