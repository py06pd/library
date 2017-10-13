var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-login',
    template: require('./Login.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            forceLogin: false,
            username: '',
            password: '',
            selectedUser: { id: null, name: '', role: 'anon' },
        };
    },
    methods: {
        cancelLogin: function() {
            this.forceLogin = false;
        },

        login: function() {
            this.load('login', { id: this.selectedUser.id, username: this.username, password: this.password }).then(function(response) {
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
        },
        
        onLogoutClicked: function() {
            this.forceLogin = false;
            this.$root.user = { id: 0, name: '', role: 'anon' };
            this.load('logout');
        },
        
        selectUser: function(value) {
            this.selectedUser = this.$root.users[value];
            if (this.selectedUser.role == 'anon') {
                this.login();
            }
        },
    },
};
