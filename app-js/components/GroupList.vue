<template>
    <div>
        <el-button-group v-if="$root.user.hasRole('ROLE_ADMIN')" id="controls">
            <el-button type="primary" icon="plus" @click="openGroup()"></el-button>
            <el-button type="primary" icon="delete" @click="deleteItems"></el-button>
        </el-button-group>

        <table class="cic-table">
            <thead>
                <tr>
                    <th v-if="$root.user.hasRole('ROLE_ADMIN')"></th>
                    <th>Name</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="group in $root.user.groups">
                    <td v-if="$root.user.hasRole('ROLE_ADMIN')">
                        <el-checkbox @change="onRowSelected(group.groupId)" />
                    </td>
                    <td class="primary" @click="openGroup(group)">{{ group.name }}</td>
                </tr>
            </tbody>
        </table>

        <group-form v-if="formOpen" :value="editing" :users="users" @blur="close" @input="saveItem"/>
    </div>
</template>

<script>
    import { Button, ButtonGroup, Checkbox } from 'element-ui';
    import Group from '../models/group';
    import GroupForm from '../components/GroupForm.vue';
    let Http = require('../mixins/Http');

    export default {
        name: 'group-list',
        mixins: [ Http ],
        components: {
            GroupForm,
            'el-button': Button,
            'el-button-group': ButtonGroup,
            'el-checkbox': Checkbox,
        },
        data: function () {
            return {
                editing: new Group(),
                formOpen: false,
                selected: [],
                users: [],
            };
        },

        methods: {
            close () {
                this.formOpen = false;
            },

            deleteItems () {
                this.delete('groups/delete', { groupIds: this.selected }).then(function(response) {
                    if (response.body.status === 'OK') {
                        for (let i in this.$root.user.groups) {
                            if (this.selected.indexOf(this.$root.user.groups[i].groupId) !== -1) {
                                delete(this.$root.user.groups[i]);
                            }
                        }

                        this.selected = [];
                    }
                });
            },

            onRowSelected (val) {
                if (this.selected.indexOf(val) === -1) {
                    this.selected.push(val);
                } else {
                    this.selected.slice(this.selected.indexOf(val), 1);
                }
            },

            openGroup (group) {
                if (group) {
                    this.editing = new Group(group);
                } else {
                    this.editing = new Group();
                }

                if (this.editing.getId()) {
                    this.load('group/get', { groupId: this.editing.getId() }).then(function(response) {
                        this.editing = new Group(response.body.data);
                        this.users = response.body.users;
                        this.formOpen = true;
                    });
                } else {
                    this.formOpen = true;
                }
            },

            saveItem (group) {
                let data = JSON.stringify(group.serialise());

                this.save('group/save', { data: data }).then(function(response) {
                    if (response.body.status === 'OK') {
                        let groupIndex = this.$root.user.groups.findIndex(x => x.groupId === group.getId());
                        if (groupIndex !== -1) {
                            this.$root.user.groups[groupIndex] = group.serialise();
                        }
                        this.close();
                    }
                });
            },
        },
    };
</script>
