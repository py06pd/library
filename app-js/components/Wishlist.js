var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-wishlist',
    template: require('./Wishlist.template.html'),
    mixins: [ Http ],
    props: {
        id: Number,
    },
    data: function () {
        return {
            books: [],
            notes: { id: 0, text: '' },
            notesOpen: false,
        };
    },
    created: function () {
        this.loadBooks();
    },
    methods: {
        closeNotes: function() {
            this.notesOpen = false;
            this.notes = { id: 0, text: '' };
        },
        
        gift: function(id) {
            this.save('wishlist/gift', { id: id, userid: this.id }).then(function() {
                this.loadBooks();
            });
        },
        
        loadBooks: function() {
            this.showStatus();
            this.load('wishlist/get', { userid: this.id }, 'Loading...', false).then(function(response) {
                this.clearStatus();
                this.books = response.body.books;
            });
        },
        
        openNotes: function(book) {
            this.notes.text = book.notes;
            this.notes.id = book.id;
            this.notesOpen = true;
        },

        own: function(id) {
            this.save('wishlist/own', { id: id }).then(function() {
                this.loadBooks();
            });
        },
        
        remove: function(id) {
            this.save('wishlist/remove', { id: id }).then(function() {
                this.loadBooks();
            });
        },
        
        saveNotes: function() {
            this.save('notes/save', { id: this.notes.id, userid: this.id, text: this.notes.text }).then(function() {
                this.notes = { id: 0, text: '' };
                this.notesOpen = false;
                this.loadBooks();
            });
        },
    },
};
