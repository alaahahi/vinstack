const GOOGLE_AUTHENTICATOR_PLAY_STORE_URL =
    'https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en';

const GOOGLE_AUTHENTICATOR_APP_STORE_URL =
    'https://apps.apple.com/us/app/google-authenticator/id388497605';

function isAndroid() {
    if (typeof navigator === 'undefined') {
        return false;
    }

    const ua = navigator.userAgent || '';

    return /android/i.test(ua);
}

function isAppleMobile() {
    if (typeof navigator === 'undefined') {
        return false;
    }

    const ua = navigator.userAgent || '';
    const platform = navigator.platform || '';

    if (/iPad|iPhone|iPod/i.test(ua)) {
        return true;
    }

    return platform === 'MacIntel' && navigator.maxTouchPoints > 1;
}

export function getGoogleAuthenticatorStoreUrl() {
    if (isAndroid()) {
        return GOOGLE_AUTHENTICATOR_PLAY_STORE_URL;
    }

    if (isAppleMobile()) {
        return GOOGLE_AUTHENTICATOR_APP_STORE_URL;
    }

    return GOOGLE_AUTHENTICATOR_APP_STORE_URL;
}
