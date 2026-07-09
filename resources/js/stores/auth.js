import { defineStore } from 'pinia';
import api from '../api/client';

const ISOLATED_FLAG = 'auth_isolated';

function hasIsolatedSession() {
    return sessionStorage.getItem(ISOLATED_FLAG) === '1';
}

function readStoredToken() {
    if (hasIsolatedSession()) {
        return sessionStorage.getItem('token');
    }

    return localStorage.getItem('token');
}

function readStoredUser() {
    const raw = hasIsolatedSession()
        ? sessionStorage.getItem('user')
        : localStorage.getItem('user');

    return JSON.parse(raw || 'null');
}

function writeSession({ token, user }, isolated = false) {
    const target = isolated ? sessionStorage : localStorage;

    target.setItem('token', token);
    target.setItem('user', JSON.stringify(user));

    if (isolated) {
        sessionStorage.setItem(ISOLATED_FLAG, '1');
    } else {
        sessionStorage.removeItem(ISOLATED_FLAG);
        sessionStorage.removeItem('token');
        sessionStorage.removeItem('user');
    }
}

function clearSession(isolated = hasIsolatedSession()) {
    const target = isolated ? sessionStorage : localStorage;

    target.removeItem('token');
    target.removeItem('user');

    if (isolated) {
        sessionStorage.removeItem(ISOLATED_FLAG);
    }
}

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: readStoredUser(),
        token: readStoredToken(),
        loading: false,
        isolated: hasIsolatedSession(),
    }),
    getters: {
        isAuthenticated: (state) => Boolean(state.token && state.user),
        isAdmin: (state) => state.user?.role === 'admin',
        isDealer: (state) => state.user?.role === 'dealer',
    },
    actions: {
        setSession({ token, user }, { isolated = false } = {}) {
            this.token = token;
            this.user = user;
            this.isolated = isolated;
            writeSession({ token, user }, isolated);
        },
        async login(payload, { isolated = false } = {}) {
            this.loading = true;

            try {
                const { data } = await api.post('/login', payload);

                if (data.two_factor_setup || data.two_factor) {
                    return data;
                }

                this.setSession(data, { isolated });

                return data;
            } finally {
                this.loading = false;
            }
        },
        async fetchTwoFactorSetup(setupToken) {
            const { data } = await api.post('/two-factor/setup', {
                setup_token: setupToken,
            });

            return data;
        },
        async confirmTwoFactor(setupToken, code) {
            this.loading = true;

            try {
                const { data } = await api.post('/two-factor/confirm', {
                    setup_token: setupToken,
                    code,
                });

                this.setSession({
                    token: data.token,
                    user: data.user,
                }, { isolated: this.isolated });
                sessionStorage.removeItem('setup_token');

                return data;
            } finally {
                this.loading = false;
            }
        },
        async challengeTwoFactor(challengeToken, code, recoveryCode = null) {
            this.loading = true;

            try {
                const { data } = await api.post('/two-factor/challenge', {
                    challenge_token: challengeToken,
                    code: code || undefined,
                    recovery_code: recoveryCode || undefined,
                });

                this.setSession(data, { isolated: this.isolated });
                sessionStorage.removeItem('challenge_token');

                return data;
            } finally {
                this.loading = false;
            }
        },
        async fetchMe() {
            if (! this.token) {
                return;
            }

            const { data } = await api.get('/me');
            this.user = data.user;
            const target = this.isolated ? sessionStorage : localStorage;
            target.setItem('user', JSON.stringify(data.user));
        },
        logout() {
            api.post('/logout').catch(() => {});
            this.token = null;
            this.user = null;
            clearSession(this.isolated);
            this.isolated = false;
            sessionStorage.removeItem('setup_token');
            sessionStorage.removeItem('challenge_token');
        },
    },
});
