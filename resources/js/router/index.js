import { createRouter, createWebHistory } from "vue-router";
import axios from "axios";
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
        component: Login
    },
    {
        path: "/register",
        name: "register",
        component: Register
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

// Global navigation guard for authentication
router.beforeEach(async (to, from, next) => {
    // Check if route requires authentication
    if (to.meta.requiresAuth) {
        try {
            // Verify user is authenticated
            await axios.get('/api/user');
            next(); // Allow navigation
        } catch (error) {
            // Not authenticated - redirect to login
            if (error.response?.status === 401) {
                next({
                    name: 'login',
                    query: { redirect: to.fullPath }
                });
            } else {
                // Network error - allow through, let component handle
                console.error('Auth check failed:', error);
                next();
            }
        }
    } else {
        // Public route - allow navigation
        next();
    }
});

export default router;
