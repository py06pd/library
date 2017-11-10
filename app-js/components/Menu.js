var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-menu',
    template: require('./Menu.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            activeIndex: 1,
            showMenu: false,
        };
    },
    methods: {
        onOptionSelected: function(option, index) {
            if (option === 'wishlist') {
                this.load('wishlist/get', { userid: this.$root.user.id}).then(function(response) {
                    this.$root.params = {
                        userid: this.$root.user.id,
                        books: response.body.books,
                    };
                });
            }
            
            this.$emit('input', option);
            this.activeIndex = index;
            this.showMenu = false;
        },
        toggleMenu: function() {
            this.showMenu = !this.showMenu;
        },
    },
};
