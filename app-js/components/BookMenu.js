import EditBook from './EditBook';
var Http = require('../mixins/Http');

export default {
    name: 'bookmenu',
    template: require('./BookMenu.template.html'),
    mixins: [ Http ],
    components: {
        'book-form': EditBook,
    },
    props: {
        book : { type: Object, default: { id: 0 }},
        mode: { type: Number, default: 0 },
    },
    methods: {
        borrowRequest: function() {
            this.save('request', { id: this.book.id }).then(function() {
                this.close();
            });
        },
        
        close: function() {
            this.$emit('change', 0);
        },
        
        openEdit: function() {
            this.$emit('change', 2);
        },
        
        ownBook: function() {
            this.save('book/own', { id: this.book.id }).then(function() {
                this.close();
            });
        },
        
        readBook: function() {
            this.save('book/read', { id: this.book.id }).then(function() {
                this.close();
            });
        },
        
        unownBook: function() {
            this.save('book/unown', { id: this.book.id }).then(function() {
                this.close();
            });
        },
        
        unreadBook: function() {
            this.save('book/unread', { id: this.book.id }).then(function() {
                this.close();
            });
        },
        
        wishlist: function() {
            this.save('wishlist/add', { id: this.book.id }).then(function() {
                this.close();
            });
        },
    },
};
