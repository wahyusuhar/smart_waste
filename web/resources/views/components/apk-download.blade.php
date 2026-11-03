{{-- Shown on Login and Daftar only when MOBILE_APK_URL is set, so there is never a dead link. --}}
@if (config('services.mobile.apk_url'))
    <p {{ $attributes->class('text-center text-sm text-gray-600') }}>
        Pakai Android?
        <a href="{{ config('services.mobile.apk_url') }}" class="font-medium text-accent hover:text-accent-hover">Unduh aplikasi (APK)</a>
    </p>
@endif
