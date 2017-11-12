var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-login',
    template: require('./Login.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            forceLogin: false,
            name: '',
            registerOpen: false,
            username: '',
            password: '',
            selectedUser: {
                id: null,
                name: '',
                username: '',
                password: '',
                role: 'anon',
            },
        };
    },
    methods: {
        cancelLogin: function() {
            this.forceLogin = false;
        },

        cancelRegister: function() {
            this.registerOpen = false;
        },

        login: function() {
            var params = {
                id: this.selectedUser.id,
                username: this.selectedUser.username,
                password: this.selectedUser.password,
            };
            
            this.load('login', params).then(function(response) {
                if (response.body.status === 'OK') {
                    this.$root.user = response.body.user;
                    this.forceLogin = false;
                } else if (response.body.status === 'forceLogin') {
                    this.username = '';
                    this.password = '';
                    this.forceLogin = true;
                }
            });
        },

        onLoginClicked: function() {
            this.forceLogin = true;
            this.registerOpen = false;
            this.resetUser();
        },
        
        onLogoutClicked: function() {
            this.forceLogin = false;
            this.load('logout');
            
            this.$root.params = {};
            this.$root.page = 'books';
            this.$root.user = { id: 0, name: '', role: 'anon' };
        },
        
        onRegisterClicked: function() {
            this.forceLogin = false;
            this.registerOpen = true;
            this.resetUser();
        },
        
        register: function() {
            if (this.selectedUser.name === '') {
                this.showWarningMessage('Display Name is a required field');
            }
            
            if (this.selectedUser.username === '') {
                this.showWarningMessage('Username is a required field');
            }
            
            if (this.selectedUser.password === '') {
                this.showWarningMessage('Password is a required field');
            }
            
            var params = {
                name: this.selectedUser.name,
                username: this.selectedUser.username,
                password: this.selectedUser.password,
            };
            
            this.load('login/register', params).then(function(response) {
                if (response.body.status === 'OK') {
                    this.$root.user = response.body.user;
                    this.registerOpen = false;
                    this.$root.users[this.$root.user.id] = this.$root.user;
                } else if (response.body.status === 'warn') {
                    this.showWarningMessage(response.body.errorMessage);
                }
            });
        },
        
        resetUser: function() {
            this.selectedUser = {
                id: null,
                name: '',
                username: '',
                password: '',
                role: 'anon',
            };
        },

        selectUser: function(value) {
            this.selectedUser.id = this.$root.users[value].id;
            this.selectedUser.role = this.$root.users[value].role;
            if (this.selectedUser.role == 'anon') {
                this.login();
            }
        },
    },
};
