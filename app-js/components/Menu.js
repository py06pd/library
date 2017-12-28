var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-menu',
    template: require('./Menu.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            page: 'books',
            showMenu: false,
        };
    },
    methods: {
        onOptionSelected: function(option) {
            if (option === 'wishlist') {
                this.$router.push('/wishlist/' + this.$root.user.id);
            } else if (option === 'books') {
                this.$router.push('/');
            } else {
                this.$router.push('/' + option);
            }
            
            this.page = option;
            this.showMenu = false;
        },
        toggleMenu: function() {
            this.showMenu = !this.showMenu;
        },
    },
};
