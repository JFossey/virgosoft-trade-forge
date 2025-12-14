import { ref, computed } from "vue";
import { useRouter } from "vue-router";
import axios from "axios";

// Shared state - created once and shared across all components
const errors = ref([]);
const loading = ref(false);
const user = ref(null);
const isAuthenticated = computed(() => user.value !== null);

export function useAuth() {
    const router = useRouter();

    /**
     * Extract and format validation errors from API response
     */
    const handleErrors = (error) => {
        // Laravel validation errors
        if (error.response?.data?.errors) {
            const validationErrors = error.response.data.errors;
            errors.value = Object.values(validationErrors).flat();
            return;
        }

        errors.value = ["An unexpected error occurred. Please try again."];
    };

    /**
     * Clear all errors
     */
    const clearErrors = () => {
        errors.value = [];
    };

    /**
     * Fetch the authenticated user from the API
     */
    const fetchUser = async () => {
        try {
            const response = await axios.get('/api/user');
            user.value = response.data.user; // Extract nested user object
            return response.data.user;
        } catch (error) {
            // 401 means not authenticated, not an error
            if (error.response?.status === 401) {
                user.value = null;
                return null;
            }
            // Other errors (network, server) should be thrown
            throw error;
        }
    };

    /**
     * Make an authenticated API request with CSRF token
     */
    const makeAuthRequest = async (url, data, redirectTo = "/") => {
        clearErrors();
        loading.value = true;

        try {
            // Get CSRF cookie first
            await axios.get("/sanctum/csrf-cookie");

            // Make the API request
            const response = await axios.post(url, data);

            if (response.data.user) {
                user.value = response.data.user;
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
    const register = async (formData, redirectTo = "/") => {
        return makeAuthRequest("/api/register", formData, redirectTo);
    };

    /**
     * Login a user
     */
    const login = async (formData, redirectTo = "/") => {
        return makeAuthRequest("/api/login", formData, redirectTo);
    };

    /**
     * Logout the current user
     */
    const logout = async () => {
        clearErrors();
        loading.value = true;

        try {
            await axios.post("/api/logout");
            user.value = null;
            router.push("/login");
        } catch (error) {
            handleErrors(error);
        } finally {
            loading.value = false;
        }
    };

    return {
        user,
        isAuthenticated,
        errors,
        loading,
        clearErrors,
        fetchUser,
        register,
        login,
        logout,
    };
}
