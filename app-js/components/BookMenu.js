var Http = require('../mixins/Http');

export default {
    name: 'bookmenu',
    template: require('./BookMenu.template.html'),
    mixins: [ Http ],
    props: {
        book : { type: Object, default: { id: 0 }},
        menuOpen: Boolean,
    },
    methods: {
        borrowRequest: function() {
            this.save('request', { id: this.book.id }).then(function() {
                this.$emit('blur');
            });
        },
        
        openEdit: function() {
            this.$emit('change', this.book.id);
        },
        
        ownBook: function() {
            this.save('book/own', { id: this.book.id }).then(function() {
                this.$emit('blur', 1);
            });
        },
        
        readBook: function() {
            this.save('book/read', { id: this.book.id }).then(function() {
                this.$emit('blur', 1);
            });
        },
        
        wishlist: function() {
            this.save('wishlist/add', { id: this.book.id }).then(function() {
                this.$emit('blur', 1);
            });
        },
    },
};
