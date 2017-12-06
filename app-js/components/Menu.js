var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-menu',
    template: require('./Menu.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            showMenu: false,
        };
    },
    methods: {
        onOptionSelected: function(option) {
            if (option === 'wishlist') {
                this.load('wishlist/get', { userid: this.$root.user.id }).then(function(response) {
                    this.$root.params = {
                        userid: this.$root.user.id,
                        books: response.body.books,
                    };
                });
            } else if (option === 'books') {
                this.$router.push('/');
            } else if (option === 'authors') {
                this.$router.push('/authors');
            } else if (option === 'series') {
                this.$router.push('/series');
            }
            
            this.$root.page = option;
            this.showMenu = false;
        },
        toggleMenu: function() {
            this.showMenu = !this.showMenu;
        },
    },
};
