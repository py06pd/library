<template>
    <div id="menuSection">
        <el-button
            type="primary"
            icon="menu"
            @click="toggleMenu"/>
        <ul v-show="showMenu" class="el-menu">
            <li
                :class="{ 'is-active': $route.path === '/me' }"
                class="el-menu-item"
                @click="onOptionSelected('/me')">
                Me
            </li>
            <li
                :class="{ 'is-active': $route.path === '/' }"
                class="el-menu-item"
                @click="onOptionSelected('/')">
                Books
            </li>
            <li
                :class="{ 'is-active': $route.path === '/authors' }"
                class="el-menu-item"
                @click="onOptionSelected('/authors')">
                Authors
            </li>
            <li
                :class="{ 'is-active': $route.path === '/groups' }"
                class="el-menu-item"
                @click="onOptionSelected('/groups')">
                Groups
            </li>
            <li
                :class="{ 'is-active': $route.path === '/lending' }"
                class="el-menu-item"
                @click="onOptionSelected('/lending')">
                <el-badge v-if="$root.requests > 0" :value="$root.requests">Lending</el-badge>
                <span v-else>Lending</span>
            </li>
            <li
                :class="{ 'is-active': $route.path === '/series' }"
                class="el-menu-item"
                @click="onOptionSelected('/series')">
                Series
            </li>
            <li
                v-if="$root.user.hasRole('ROLE_ADMIN')"
                :class="{ 'is-active': $route.path === '/users' }"
                class="el-menu-item"
                @click="onOptionSelected('/users')">
                Users
            </li>
            <li
                :class="{ 'is-active': $route.path === '/wishlist/' + $root.user.getId() }"
                class="el-menu-item"
                @click="onOptionSelected('/wishlist/' + $root.user.userId)">
                Wishlist
            </li>
        </ul>
    </div>
</template>

<script>
import { Button } from 'element-ui';

export default {
    name: 'MainMenu',
    components: {
        'el-button': Button,
    },
    data () {
        return {
            showMenu: false,
        };
    },
    methods: {
        onOptionSelected (option) {
            this.$router.push(option);
            this.showMenu = false;
        },
        toggleMenu () {
            this.showMenu = !this.showMenu;
        },
    },
};
</script>