module.exports = {
    name: 'cic-menu',
    template: require('./Menu.template.html'),
    data: function () {
        return {
            showMenu: false,
        };
    },
    methods: {
        onOptionSelected: function(option) {
            this.$emit('select', option);
        },
        toggleMenu: function() {
            this.showMenu = !this.showMenu;
        },
    },
};
