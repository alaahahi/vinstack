import { nextTick, ref, watch } from 'vue';

const SHAKE_MS = 500;
const SUCCESS_FLASH_MS = 450;

export function useOtpSubmit(codeRef, verifyFn, { afterSuccess } = {}) {
    const otpState = ref('idle');
    const otpWrapRef = ref(null);
    let inFlight = false;

    async function submit() {
        if (inFlight || otpState.value === 'success') {
            return;
        }

        if (String(codeRef.value).length !== 6) {
            return;
        }

        inFlight = true;

        try {
            const ok = await verifyFn();

            if (ok) {
                otpState.value = 'success';
                await new Promise((resolve) => window.setTimeout(resolve, SUCCESS_FLASH_MS));
                await afterSuccess?.();
            } else {
                await applyError();
            }
        } finally {
            inFlight = false;
        }
    }

    async function applyError() {
        otpState.value = 'error';
        codeRef.value = '';
        await nextTick();
        focusOtpFirst();
        window.setTimeout(() => {
            if (otpState.value === 'error') {
                otpState.value = 'idle';
            }
        }, SHAKE_MS);
    }

    function focusOtpFirst() {
        const input = otpWrapRef.value?.querySelector('input');

        input?.focus();
    }

    watch(codeRef, (value) => {
        if (String(value).length === 6) {
            void submit();
        }
    });

    return { otpState, otpWrapRef, submit };
}
