import { useRouter } from "vue-router";
import { storeToRefs } from "pinia";
import { useAuthStore } from "../store/auth";

export function useAuth() {
    const router = useRouter();
    const authStore = useAuthStore();

    // Expose state and getters from the store as reactive refs
    const { user, isAuthenticated, loading, errors } = storeToRefs(authStore);

    const clearErrors = authStore.clearErrors;

    const fetchUser = authStore.fetchUser;

    const register = async (formData) => {
        await authStore.register(formData);
        router.push({ name: "dashboard" });
    };

    const login = async (formData) => {
        await authStore.login(formData);
        router.push({ name: "dashboard" });
    };

    const logout = async () => {
        await authStore.logout();
        router.push({ name: "login" });
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
