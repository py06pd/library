<template>
    <div>
        <el-dialog
            :before-close="close"
            :top="dialogYOffset"
            :visible="true"
            class="cic-dialog"
            title="Edit Group">
            <el-form>
                <el-form-item label="Name">
                    <el-input
                        v-if="$root.user.hasRole('ROLE_ADMIN')"
                        v-model="group.name"
                        class="name" />
                    <template v-else>{{ group.getName() }}</template>
                </el-form-item>
                <el-form-item v-if="$root.user.hasRole('ROLE_ADMIN')" label="Users">
                    <el-select
                        filterable
                        multiple
                        :value="group.users.map(x => x.userId)"
                        placeholder="Please select a user"
                        @input="setUsers">
                        <el-option
                            v-for="u in users"
                            :key="u.userId"
                            :label="u.name"
                            :value="u.userId" />
                    </el-select>
                </el-form-item>
            </el-form>
            <table class="cic-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Wishlist</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in group.users" :key="u.userId">
                        <td class="primary" @click="openUser(u)">{{ u.name }}</td>
                        <td>
                            <el-button
                                icon="star-off"
                                title="Wishlist"
                                @click="openWishlist(u.userId)" />
                        </td>
                    </tr>
                </tbody>
            </table>
            <span slot="footer" class="dialog-footer">
                <el-button
                    v-if="$root.user.hasRole('ROLE_ADMIN')"
                    type="primary"
                    @click="saveItem">
                    Save
                </el-button>
                <el-button @click="close">Cancel</el-button>
            </span>
        </el-dialog>
        <user-account
            v-if="$root.user.hasRole('ROLE_ADMIN') && userOpen"
            v-model="user"
            :closable="true"
            @blur="closeUser" />
    </div>
</template>

<script>
import { Button, Dialog, Form, FormItem, Input, Option, Select } from 'element-ui';
import Group from '../models/group';
import User from '../models/user';
import UserAccount from '../components/UserAccount.vue';
import Http from '../mixins/Http';

export default {
    name: 'GroupForm',
    components: {
        UserAccount,
        'el-button': Button,
        'el-dialog': Dialog,
        'el-form': Form,
        'el-form-item': FormItem,
        'el-input': Input,
        'el-option': Option,
        'el-select': Select,
    },
    mixins: [ Http ],
    props: {
        users: {
            type: Array,
            required: true,
        },
        value: {
            type: Boolean,
            required: true,
        },
    },
    data () {
        return {
            group: new Group(),
            user: new User(),
            userOpen: false,
        };
    },

    computed: {
        dialogYOffset () {
            if (window.innerWidth <= 600) {
                return '0';
            }

            return '15%';
        },
    },

    created () {
        this.group.groupId = this.value.getId();
        this.group.name = this.value.getName();
        this.group.users = this.value.getUsers();
    },

    methods: {
        close () {
            this.$emit('blur');
        },

        closeUser () {
            this.userOpen = false;
        },

        openUser (user) {
            if (this.$root.user.hasRole('ROLE_ADMIN')) {
                this.user = new User(user);
                this.userOpen = true;
            }
        },

        openWishlist (userId) {
            this.$router.push('/wishlist/' + userId);
        },

        saveItem () {
            this.$emit('input', this.group);
        },

        setUsers (userIds) {
            this.group.users = this.users.filter(x => userIds.indexOf(x.userId) !== -1);
        },
    },
};
</script>