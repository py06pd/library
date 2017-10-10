var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-login',
    template: require('./Login.template.html'),
    mixins: [ Http ],
    props: {
        value: { type: Object },
        users: { type: Object },
    },
    data: function () {
        return {
            forceLogin: false,
            user: {},
            username: '',
            password: '',
        };
    },
    methods: {
        login: function() {
            this.requestLogin({ id: this.user.id, username: this.username, password: this.password });
        },

        onOptionSelected: function(option) {
            this.forceLogin = false;
            if (option > 0) {
                this.user = JSON.parse(JSON.stringify(this.users[option]));
                this.requestLogin({ id: option });
            } else {
                this.post('logout');
                this.$emit('input', { id: 0, name: '', role: '' });
            }
        },
        
        requestLogin: function(params) {
            this.post('login', params).then(function(response) {
                if (response.body.status === 'OK') {
                    this.$emit('input', this.user);
                } else if (response.body.status === 'forceLogin') {
                    console.log(2);
                    this.username = '';
                    this.password = '';
                    this.forceLogin = true;
                }
            });
        },
    },
};
