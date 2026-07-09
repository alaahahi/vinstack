import axios from 'axios';
import { DEFAULT_LOCALE, LOCALE_STORAGE_KEY, normalizeLocale } from '../constants/locales';

const api = axios.create({
    baseURL: '/api',
    headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
    },
});

api.interceptors.request.use((config) => {
    const token = sessionStorage.getItem('auth_isolated') === '1'
        ? sessionStorage.getItem('token')
        : localStorage.getItem('token');

    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    config.headers['Accept-Language'] = normalizeLocale(
        localStorage.getItem(LOCALE_STORAGE_KEY) || document.documentElement.lang?.split('-')[0] || DEFAULT_LOCALE,
    );

    if (config.data instanceof FormData) {
        // Axios 1.x keeps default application/json on AxiosHeaders; delete alone
        // does not run before transformRequest, which JSON-stringifies FormData.
        config.headers.setContentType(undefined);
    }

    return config;
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        if (error.response?.status === 401) {
            const isolated = sessionStorage.getItem('auth_isolated') === '1';
            const target = isolated ? sessionStorage : localStorage;

            target.removeItem('token');
            target.removeItem('user');

            if (isolated) {
                sessionStorage.removeItem('auth_isolated');
            }

            if (!window.location.pathname.startsWith('/login')) {
                window.location.href = '/login';
            }
        }

        return Promise.reject(error);
    },
);

export default api;
