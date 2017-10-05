var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-login',
    template: require('./Login.template.html'),
    mixins: [ Http ],
    props: {
        value: { type: Object },
        users: { type: Object },
    },
    methods: {
        onOptionSelected: function(option) {
            if (option > 0) {
                var user = JSON.parse(JSON.stringify(this.users[option]));
                this.post('login', { id: option });
                this.$emit('input', user);
            } else {
                this.$emit('input', { id: 0, name: '', role: '' });
            }
        },
    },
};
