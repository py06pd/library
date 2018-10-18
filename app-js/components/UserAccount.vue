<template>
    <div id="userAccount">
        <div>
            <div class="user-title">{{ value.getName() }}</div>
            <el-form>
                <el-form-item label="Display Name">
                    <el-input v-model="user.name"/>
                </el-form-item>
                <el-form-item label="Email Address">
                    <el-input v-model="user.username"/>
                </el-form-item>
                <el-form-item label="Password">
                    <el-input type="password" v-model="user.password"/>
                </el-form-item>
                <el-button type="primary" @click="updateAccount">Save</el-button>
            </el-form>
        </div>
        <br>
        <div>
            <div class="user-title">Sessions</div>
            <table class="cic-table">
                <thead>
                    <tr>
                        <th>Device</th>
                        <th>Created</th>
                        <th>Last Accessed</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="session in sessions">
                        <td>{{ session.device }}</td>
                        <td>{{ session.created }}</td>
                        <td>{{ session.lastAccessed }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>

<script>
    import { Button, Form, FormItem, Input } from 'element-ui';
    import User from '../models/user';
    let Http = require('../mixins/Http');

    export default {
        name: 'user-account',
        mixins: [ Http ],
        props: { value : { type: Object } },
        components: {
            'el-button': Button,
            'el-form': Form,
            'el-form-item': FormItem,
            'el-input': Input,
        },
        data: function () {
            return {
                sessions: [],
                user: {
                    name: '',
                    username: '',
                    password: '',
                },
            };
        },
        created: function () {
            this.user.name = this.value.getName();
            this.user.username = this.value.getUsername();
            this.user.password = '********';
            this.loadSessions();
        },
        methods: {
            loadSessions () {
                this.sessions = [];
                this.load('user/getSessions', { userId: this.value.getId() }).then(function(response) {
                    if (response.body.status === 'OK') {
                        this.sessions = response.body.data;
                    }
                });
            },

            updateAccount () {
                if (this.user.name === '') {
                    this.showWarningMessage('Display Name is a required field');
                }

                if (this.user.username === '') {
                    this.showWarningMessage('Username is a required field');
                }

                if (this.user.password === '') {
                    this.showWarningMessage('Password is a required field');
                }

                var params = {
                    userId: this.value.getId(),
                    name: this.user.name,
                    newUsername: this.user.username,
                    newPassword: this.user.password,
                };

                this.save('user/save', params).then(function(response) {
                    if (response.body.status === 'OK') {
                        let user = new User(this.value.serialise());
                        user.setName(this.user.name);
                        user.setUsername(this.user.username);
                        this.$emit('input', user);
                    }
                });
            },
        },
    };
</script>

<style scoped>
    #userAccount > div {
        width: 30%;
        margin: auto;
        padding: 10px;
        box-shadow: 0 0 5px #888;
        background-color: #fff;
    }

    #userAccount button {
        margin-right: 0px;
        margin-left: auto;
        display: flex;
    }

    .user-title {
        font-size: 16px;
        font-weight: 700;
        color: #1f2d3d;
        margin-bottom: 10px;
    }
</style>