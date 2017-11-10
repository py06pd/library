var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-wishlist',
    template: require('./Wishlist.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            notes: { id: 0, text: '' },
            notesOpen: false,
        };
    },
    methods: {
        closeNotes: function() {
            this.notesOpen = false;
            this.notes = { id: 0, text: '' };
        },
        
        gift: function(id) {
            this.save('wishlist/gift', { id: id, userid: this.$root.params.userid }).then(function() {
                this.showStatus();
                this.post('wishlist/get', { userid: this.$root.params.userid }).then(function(response) {
                    this.clearStatus();
                    this.$root.params.books = response.body.books;
                });
            });
        },
        
        openNotes: function(book) {
            this.notes.text = book.notes;
            this.notes.id = book.id;
            this.notesOpen = true;
        },

        own: function(id) {
            this.save('wishlist/own', { id: id }).then(function() {
                this.showStatus();
                this.post('wishlist/get', { userid: this.$root.params.userid }).then(function(response) {
                    this.clearStatus();
                    this.$root.params.books = response.body.books;
                });
            });
        },
        
        remove: function(id) {
            this.save('wishlist/remove', { id: id }).then(function() {
                this.showStatus();
                this.post('wishlist/get', { userid: this.$root.params.userid }).then(function(response) {
                    this.clearStatus();
                    this.$root.params.books = response.body.books;
                });
            });
        },
        
        saveNotes: function() {
            this.save('notes/save', { id: this.notes.id, userid: this.$root.params.userid, text: this.notes.text }).then(function() {
                this.notes = { id: 0, text: '' };
                this.notesOpen = false;
                this.post('wishlist/get', { userid: this.$root.params.userid }).then(function(response) {
                    this.clearStatus();
                    this.$root.params.books = response.body.books;
                });
            });
        },
    },
};
