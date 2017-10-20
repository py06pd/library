var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-wishlist',
    template: require('./Wishlist.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            books: [],
            userid: 0,
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
    },
};
