<template>
    <div id="menuSection">
        <el-button type="primary" icon="menu" @click="toggleMenu"></el-button>
        <ul class="el-menu" v-show="showMenu">
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/me' }" @click="onOptionSelected('/me')">Me</li>
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/' }" @click="onOptionSelected('/')">Books</li>
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/authors' }" @click="onOptionSelected('/authors')">Authors</li>
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/groups' }" @click="onOptionSelected('/groups')">Groups</li>
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/lending' }" @click="onOptionSelected('/lending')">
                <el-badge v-if="$root.requests > 0" :value="$root.requests">Lending</el-badge>
                <span v-else>Lending</span>
            </li>
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/series' }" @click="onOptionSelected('/series')">Series</li>
            <li v-if="$root.user.hasRole('ROLE_ADMIN')" class="el-menu-item" :class="{ 'is-active': $route.path === '/users' }" @click="onOptionSelected('/users')">Users</li>
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/wishlist/' + $root.user.getId() }" @click="onOptionSelected('/wishlist/' + $root.user.userId)">Wishlist</li>
        </ul>
    </div>
</template>

<script>
    import { Button } from 'element-ui';

    export default {
        name: 'main-menu',
        components: {
            'el-button': Button,
        },
        data: function () {
            return {
                showMenu: false,
            };
        },
        methods: {
            onOptionSelected: function(option) {
                this.$router.push(option);
                this.showMenu = false;
            },
            toggleMenu: function() {
                this.showMenu = !this.showMenu;
            },
        },
    };
</script>