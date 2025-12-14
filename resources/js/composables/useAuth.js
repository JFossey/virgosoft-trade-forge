import { useRouter } from "vue-router";
import { useAuthStore } from "../store/auth";

export function useAuth() {
    const router = useRouter();
    const authStore = useAuthStore();

    // Expose state and getters from the store
    const user = authStore.user;
    const isAuthenticated = authStore.isAuthenticated;
    const loading = authStore.loading;
    const errors = authStore.errors;

    const clearErrors = authStore.clearErrors;

    const fetchUser = authStore.fetchUser;

    const register = async (formData) => {
        try {
            await authStore.register(formData);
            router.push({ name: "dashboard" });
        } catch (error) {
            // Errors are handled by the store
        }
    };

    const login = async (formData) => {
        try {
            await authStore.login(formData);
            router.push({ name: "dashboard" });
        } catch (error) {
            // Errors are handled by the store
        }
    };

    const logout = async () => {
        try {
            await authStore.logout();
            router.push({ name: "login" });
        } catch (error) {
            // Errors are handled by the store
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
