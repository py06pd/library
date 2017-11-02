var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-wishlist',
    template: require('./Wishlist.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            books: [],
            userid: 0,
            notes: { id: 0, text: '' },
            notesOpen: false,
        };
    },
    props: {
        ibooks: Array,
        iuserid: Number,
    },
    created: function () {
        this.books = this.ibooks;
        this.userid = this.iuserid;
    },
    methods: {
        closeNotes: function() {
            this.notesOpen = false;
            this.notes = { id: 0, text: '' };
        },
        
        openNotes: function(book) {
            this.notes.text = book.notes;
            this.notes.id = book.id;
            this.notesOpen = true;
        },

        own: function(id) {
            this.save('wishlist/own', { id: id }).then(function() {
                this.showSucessMessage('Update successful');
                this.showStatus();
                this.post('wishlist/get', { userid: this.userid }).then(function(response) {
                    this.clearStatus();
                    this.books = response.body.books;
                });
            });
        },
        
        remove: function(id) {
            this.save('wishlist/remove', { id: id }).then(function() {
                this.showSucessMessage('Update successful');
                this.showStatus();
                this.post('wishlist/get', { userid: this.userid }).then(function(response) {
                    this.clearStatus();
                    this.books = response.body.books;
                });
            });
        },
        
        saveNotes: function() {
            this.save('notes/save', { id: this.notes.id, userid: this.userid, text: this.notes.text }).then(function() {
                this.notes = { id: 0, text: '' };
                this.notesOpen = false;
                this.post('wishlist/get', { userid: this.userid }).then(function(response) {
                    this.clearStatus();
                    this.books = response.body.books;
                });             
            });
        },
    },
};
