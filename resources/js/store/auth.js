import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import axios from 'axios';

export const useAuthStore = defineStore('auth', () => {
    const user = ref(null);
    const loading = ref(false);
    const errors = ref([]);

    const isAuthenticated = computed(() => user.value !== null);

    const clearErrors = () => {
        errors.value = [];
    };

    const handleErrors = (error) => {
        if (error.response?.data?.errors) {
            const validationErrors = error.response.data.errors;
            errors.value = Object.values(validationErrors).flat();
            return;
        }
        errors.value = ["An unexpected error occurred. Please try again."];
    };

    const fetchUser = async () => {
        loading.value = true;
        try {
            const response = await axios.get('/api/user');
            user.value = response.data.user;
            return response.data.user;
        } catch (error) {
            if (error.response?.status === 401) {
                user.value = null;
                return null;
            }
            throw error;
        } finally {
            loading.value = false;
        }
    };

    const makeAuthRequest = async (url, data) => {
        clearErrors();
        loading.value = true;
        try {
            await axios.get("/sanctum/csrf-cookie");
            const response = await axios.post(url, data);
            if (response.data.user) {
                user.value = response.data.user;
            }
            return response;
        } catch (error) {
            handleErrors(error);
            throw error;
        } finally {
            loading.value = false;
        }
    };

    const register = async (formData) => {
        return makeAuthRequest("/api/register", formData);
    };

    const login = async (formData) => {
        return makeAuthRequest("/api/login", formData);
    };

    const logout = async () => {
        clearErrors();
        loading.value = true;
        try {
            await axios.post("/api/logout");
            user.value = null;
        } catch (error) {
            handleErrors(error);
            throw error;
        } finally {
            loading.value = false;
        }
    };

    return {
        user,
        isAuthenticated,
        loading,
        errors,
        clearErrors,
        fetchUser,
        register,
        login,
        logout,
    };
});