<template>
    <div id="menuSection">
        <el-button type="primary" icon="menu" @click="toggleMenu"></el-button>
        <ul class="el-menu" v-show="showMenu">
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/me' }" index="5" @click="onOptionSelected('/me')">Me</li>
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/' }" index="1" @click="onOptionSelected('/')">Books</li>
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/authors' }" index="1" @click="onOptionSelected('/authors')">Authors</li>
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/series' }" index="1" @click="onOptionSelected('/series')">Series</li>
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/lending' }" index="2" @click="onOptionSelected('/lending')">
                <el-badge v-if="$root.requests > 0" :value="$root.requests">Lending</el-badge>
                <span v-else>Lending</span>
            </li>
            <template v-if="$root.user.role === 'ROLE_ADMIN'">
                <li class="el-menu-item" :class="{ 'is-active': $route.path === '/users' }" index="3" @click="onOptionSelected('/users')">Users</li>
            </template>
            <li class="el-menu-item" :class="{ 'is-active': $route.path === '/wishlist/' + $root.user.userId }" index="4" @click="onOptionSelected('/wishlist/' + $root.user.userId)">Wishlist</li>
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