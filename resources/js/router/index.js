import { createRouter, createWebHistory } from "vue-router";
import { useAuthStore } from "../store/auth"; // Import the Pinia store

import Home from "../components/Home.vue";
import Login from "../components/Login.vue";
import Register from "../components/Register.vue";
import Dashboard from "../components/Dashboard.vue";

const routes = [
    {
        path: "/",
        name: "home",
        component: Home,
        meta: { guestOnly: true },
    },
    {
        path: "/login",
        name: "login",
        component: Login,
        meta: { guestOnly: true },
    },
    {
        path: "/register",
        name: "register",
        component: Register,
        meta: { guestOnly: true },
    },
    {
        path: "/dashboard",
        name: "dashboard",
        component: Dashboard,
        meta: { requiresAuth: true },
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Global navigation guard
router.beforeEach(async (to, from) => {
    const authStore = useAuthStore();

    // Fetch user if not already loaded
    if (authStore.user === null) {
        await authStore.fetchUser();
    }

    const isAuth = authStore.isAuthenticated;

    if (to.meta.requiresAuth && !isAuth) {
        return { name: "login", query: { redirect: to.fullPath } };
    }

    if (to.meta.guestOnly && isAuth) {
        return { name: "dashboard" };
    }

    return true;
});

export default router;
