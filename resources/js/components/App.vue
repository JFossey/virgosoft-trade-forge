<template>
    <Layout />
</template>

<script setup>
import { onMounted } from 'vue';
import Layout from './Layout.vue';
import { useAuth } from '../composables/useAuth';

const { fetchUser } = useAuth();

// Check if user is authenticated on app load
onMounted(async () => {
    try {
        await fetchUser();
    } catch (error) {
        // Silent fail - user will just be in logged-out state
        console.error('Failed to fetch user on mount:', error);
    }
});
</script>
