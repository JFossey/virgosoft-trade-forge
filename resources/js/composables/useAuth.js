import { ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';

export function useAuth() {
    const router = useRouter();
    const errors = ref([]);
    const loading = ref(false);

    /**
     * Extract and format validation errors from API response
     */
    const handleErrors = (error) => {
        if (error.response?.data?.errors) {
            // Laravel validation errors
            const validationErrors = error.response.data.errors;
            errors.value = Object.values(validationErrors).flat();
        } else if (error.response?.data?.message) {
            errors.value = [error.response.data.message];
        } else {
            errors.value = ['An error occurred. Please try again.'];
        }
    };

    /**
     * Clear all errors
     */
    const clearErrors = () => {
        errors.value = [];
    };

    /**
     * Make an authenticated API request with CSRF token
     */
    const makeAuthRequest = async (url, data, redirectTo = '/') => {
        clearErrors();
        loading.value = true;

        try {
            // Get CSRF cookie first
            await axios.get('/sanctum/csrf-cookie');

            // Make the API request
            const response = await axios.post(url, data);

            if (response.data.user) {
                // Redirect on success
                router.push(redirectTo);
            }

            return response;
        } catch (error) {
            handleErrors(error);
            throw error;
        } finally {
            loading.value = false;
        }
    };

    /**
     * Register a new user
     */
    const register = async (formData, redirectTo = '/') => {
        return makeAuthRequest('/api/register', formData, redirectTo);
    };

    /**
     * Login a user
     */
    const login = async (formData, redirectTo = '/') => {
        return makeAuthRequest('/api/login', formData, redirectTo);
    };

    /**
     * Logout the current user
     */
    const logout = async () => {
        clearErrors();
        loading.value = true;

        try {
            await axios.post('/api/logout');
            router.push('/login');
        } catch (error) {
            handleErrors(error);
        } finally {
            loading.value = false;
        }
    };

    return {
        errors,
        loading,
        clearErrors,
        register,
        login,
        logout,
    };
}
