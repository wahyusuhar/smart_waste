// "Gunakan lokasi saat ini" button on the report form. Fills the Livewire latitude/longitude fields.
document.addEventListener('alpine:init', () => {
    window.Alpine.data('geolocation', () => ({
        locating: false,
        error: '',

        locate() {
            this.error = '';

            if (!('geolocation' in navigator)) {
                this.error = 'Browser ini tidak mendukung pengambilan lokasi. Isi koordinat secara manual.';
                return;
            }

            this.locating = true;
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.$wire.set('form.latitude', position.coords.latitude.toFixed(7));
                    this.$wire.set('form.longitude', position.coords.longitude.toFixed(7));
                    this.locating = false;
                },
                (failure) => {
                    this.locating = false;
                    this.error =
                        failure.code === failure.PERMISSION_DENIED
                            ? 'Izin lokasi ditolak. Izinkan akses lokasi di pengaturan browser, atau isi koordinat secara manual.'
                            : 'Lokasi tidak dapat diambil. Isi koordinat secara manual.';
                },
                { enableHighAccuracy: true, timeout: 10000 },
            );
        },
    }));
});
