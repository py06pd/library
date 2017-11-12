var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-myaccount',
    template: require('./MyAccount.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            user: {
                name: '',
                username: '',
                password: '',
            },
        };
    },
    created: function () {
        this.user.name = this.$root.user.name;
        this.user.username = this.$root.user.username;
        this.user.password = '********';
    },
    methods: {
        updateAccount: function() {
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
                name: this.user.name,
                username: this.user.username,
                password: this.user.password,
            };
            
            this.save('myaccount/save', params).then(function(response) {
                if (response.body.status === 'OK') {
                    this.$root.user.name = this.user.name;
                    this.$root.user.username = this.user.username;
                    this.$root.user.password = this.user.password;
                }
            });
        },
    },
};
