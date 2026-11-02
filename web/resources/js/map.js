// Read-only location map on the report detail page.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('reportMap', ({ latitude, longitude }) => ({
        failed: false,

        async init() {
            // Loaded on demand, so Leaflet is only downloaded on pages that show a map.
            const [{ default: L }] = await Promise.all([import('leaflet'), import('leaflet/dist/leaflet.css')]);

            const map = L.map(this.$refs.map, { scrollWheelZoom: false }).setView([latitude, longitude], 16);

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
            })
                .on('tileerror', () => {
                    this.failed = true;
                })
                .addTo(map);

            // A circle marker needs no image files, so it cannot break when assets are bundled.
            L.circleMarker([latitude, longitude], {
                radius: 8,
                color: '#ffffff',
                weight: 2,
                fillColor: '#0f766e',
                fillOpacity: 1,
            }).addTo(map);
        },
    }));
});
