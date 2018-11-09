<template>
    <div>
        <div v-if="!formOpen">
            <el-button-group id="controls">
                <el-button type="primary" icon="plus" @click="openUser()"></el-button>
                <el-button type="primary" icon="delete" @click="deleteItems"></el-button>
            </el-button-group>

            <table class="cic-table">
                <thead>
                    <tr>
                        <th></th>
                        <th>Name</th>
                        <th class="not-for-mobile">Email Address</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="user in users">
                        <td><el-checkbox @change="onRowSelected(user.getId())" /></td>
                        <td class="primary" @click="openUser(user)">{{ user.getName() }}</td>
                        <td class="not-for-mobile" @click="openUser(user)">{{ user.getUsername() }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <user-account v-if="formOpen" v-model="user" :closable="true" @blur="close" />
    </div>
</template>

<script>
    import { Button, ButtonGroup, Checkbox, Dialog, Form, FormItem, Input } from 'element-ui';
    import User from '../models/user';
    import UserAccount from '../components/UserAccount.vue';
    let Http = require('../mixins/Http');

    export default {
        name: 'user-list',
        mixins: [ Http ],
        components: {
            UserAccount,
            'el-button': Button,
            'el-button-group': ButtonGroup,
            'el-checkbox': Checkbox,
            'el-dialog': Dialog,
            'el-form': Form,
            'el-form-item': FormItem,
            'el-input': Input,
        },
        data: function () {
            return {
                users: [],
                user: new User(),
                formOpen: false,
                selected: [],
            };
        },

        created: function () {
            this.loadUsers();
        },

        computed: {
            dialogYOffset () {
                if (window.innerWidth <= 600) {
                    return '0';
                }

                return '15%';
            },
        },

        methods: {
            close () {
                this.formOpen = false;
            },

            deleteItems () {
                this.save('users/delete', { userIds: this.selected }).then(function() {
                    this.loadUsers();
                });
            },

            loadUsers () {
                this.load('users/get', {}).then(function(response) {
                    this.users = response.body.users.map(x => new User(x));
                });
            },

            onRowSelected (val) {
                if (this.selected.indexOf(val) === -1) {
                    this.selected.push(val);
                } else {
                    this.selected.slice(this.selected.indexOf(val), 1);
                }
            },

            openUser (user) {
                if (user) {
                    this.user = user;
                } else {
                    this.user = new User();
                }

                if (this.user.getId()) {
                    this.load('user/get', { userId: this.user.getId() }).then(function(response) {
                        this.user = new User(response.body.data);
                        this.formOpen = true;
                    });
                } else {
                    this.formOpen = true;
                }
            },
        },
    };
</script>
