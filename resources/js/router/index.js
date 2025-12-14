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
        component: Home
    },
    {
        path: "/login",
        name: "login",
        component: Login,
        meta: { guestOnly: true }
    },
    {
        path: "/register",
        name: "register",
        component: Register,
        meta: { guestOnly: true }
    },
    {
        path: "/dashboard",
        name: "dashboard",
        component: Dashboard,
        meta: { requiresAuth: true }
    },
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Global navigation guard
router.beforeEach(async (to, from, next) => {
    const authStore = useAuthStore();
    const isAuth = authStore.isAuthenticated;

    if (to.meta.requiresAuth && !isAuth) {
        next({ name: 'login', query: { redirect: to.fullPath } });
    } else if (to.meta.guestOnly && isAuth) {
        next({ name: 'dashboard' });
    } else {
        next();
    }
});

export default router;
