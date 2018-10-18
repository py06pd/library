<template>
    <user-account v-model="$root.user"></user-account>
</template>

<script>
    import UserAccount from './UserAccount.vue';

    export default {
        name: 'user-account-me',
        components: { 'user-account': UserAccount },
    };
</script>
