import { defineStore } from 'pinia';
import api from '../api/client';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user') || 'null'),
        token: localStorage.getItem('token'),
        loading: false,
    }),
    getters: {
        isAuthenticated: (state) => Boolean(state.token && state.user),
        isAdmin: (state) => state.user?.role === 'admin',
        isDealer: (state) => state.user?.role === 'dealer',
    },
    actions: {
        setSession({ token, user }) {
            this.token = token;
            this.user = user;
            localStorage.setItem('token', token);
            localStorage.setItem('user', JSON.stringify(user));
        },
        async login(payload) {
            this.loading = true;

            try {
                const { data } = await api.post('/login', payload);

                if (data.two_factor_setup || data.two_factor) {
                    return data;
                }

                this.setSession(data);

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
                });
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

                this.setSession(data);
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
            localStorage.setItem('user', JSON.stringify(data.user));
        },
        logout() {
            api.post('/logout').catch(() => {});
            this.token = null;
            this.user = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
            sessionStorage.removeItem('setup_token');
            sessionStorage.removeItem('challenge_token');
        },
    },
});
