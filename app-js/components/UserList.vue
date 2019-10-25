<template>
    <div>
        <div v-if="!formOpen">
            <el-button-group id="controls">
                <el-button
                    type="primary"
                    icon="plus"
                    @click="openUser()"/>
                <el-button
                    type="primary"
                    icon="delete"
                    @click="deleteItems"/>
            </el-button-group>

            <table class="cic-table">
                <thead>
                    <tr>
                        <th>&nbsp;</th>
                        <th>Name</th>
                        <th class="not-for-mobile">Email Address</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="u in users" :key="u.getId()">
                        <td><el-checkbox @change="onRowSelected(u.getId())" /></td>
                        <td class="primary" @click="openUser(u)">{{ u.getName() }}</td>
                        <td class="not-for-mobile" @click="openUser(u)">{{ u.getUsername() }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <user-account
            v-if="formOpen"
            v-model="user"
            :closable="true"
            @blur="close" />
    </div>
</template>

<script>
import { Button, ButtonGroup, Checkbox } from 'element-ui';
import User from '../models/user';
import UserAccount from '../components/UserAccount.vue';
import Http from '../mixins/Http';

export default {
    name: 'UserList',
    components: {
        UserAccount,
        'el-button': Button,
        'el-button-group': ButtonGroup,
        'el-checkbox': Checkbox,
    },
    mixins: [ Http ],
    data: function () {
        return {
            users: [],
            user: new User(),
            formOpen: false,
            selected: [],
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

    created: function () {
        this.loadUsers();
    },

    methods: {
        close () {
            this.formOpen = false;
        },

        deleteItems () {
            this.save('users/delete', { userIds: this.selected }).then(() => {
                this.loadUsers();
            });
        },

        loadUsers () {
            this.load('users/get', {}).then((response) => {
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
                this.load('user/get', { userId: this.user.getId() }).then((response) => {
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
