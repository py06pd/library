<template>
    <user-account v-model="$root.user"/>
</template>

<script>
import UserAccount from './UserAccount.vue';

export default {
    name: 'MyAccount',
    components: { UserAccount },
};
</script>
