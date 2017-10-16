module.exports = {
    name: 'cic-menu',
    template: require('./Menu.template.html'),
    data: function () {
        return {
            activeIndex: 1,
            showMenu: false,
        };
    },
    methods: {
        onOptionSelected: function(option, index) {
            this.$emit('input', option);
            this.activeIndex = index;
        },
        toggleMenu: function() {
            this.showMenu = !this.showMenu;
        },
    },
};
