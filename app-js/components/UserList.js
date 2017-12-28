var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-user-list',
    template: require('./UserList.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            users: [],
            editing: {},
            formOpen: false,
            selected: [],
        };
    },
    created: function () {
        this.loadUsers();
    },
    methods: {
        addAccount: function() {
            if (this.editing.id !== -1 && this.editing.facebookToken === null) {
                this.load('users/auth', { id: this.editing.id }, 'Loading...', false).then(function(response) {
                    window.location = response.body.url;
                });
            }
        },

        deleteItems: function() {
            this.save('users/delete', { ids: this.selected }).then(function() {
                this.loadUsers();
            });
        },

        loadUsers: function() {
            this.load('users/get', {}).then(function(response) {
                this.users = response.body.users;
            });
        },
        
        onRowSelected: function(val) {
            this.selected = [];
            for (var i in val) {
                this.selected.push(val[i].id);
            }
        },
        
        openAdd: function() {
            this.editing = JSON.parse(JSON.stringify({
                id: -1,
                name: '',
                facebookToken: '',
            }));
            this.formOpen = true;
        },
        
        openEdit: function(id) {
            this.load('users/user', { id: id }).then(function(response) {
                this.editing = JSON.parse(JSON.stringify(response.body.data));
                this.formOpen = true;
            });
        },
        
        saveItem: function() {
            var data = JSON.stringify(this.editing);
            this.save('users/save', { data: data }).then(function() {
                this.loadUsers();
                this.showSucessMessage('Update successful');
                this.formOpen = false;
            });
        },
    },
};
