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
        };
    },
    methods: {
        cancelLogin: function() {
            this.forceLogin = false;
        },

        login: function() {
            this.post('login', { username: this.username, password: this.password }).then(function(response) {
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
            this.$root.user = { id: 0, name: '', role: '' };
            this.post('logout');
        },
    },
};
