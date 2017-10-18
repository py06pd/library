var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-lending-list',
    template: require('./LendingList.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            borrowed: [],
            borrowing: [],
            requested: [],
            requesting: [],
        };
    },
    created: function () {
        this.loadBooks();
    },
    methods: {
        cancelled: function(id) {
            this.save('lending/cancelled', { id: id }).then(function() {
                this.loadBooks();
            });
        },

        delivered: function(id) {
            this.save('lending/delivered', { id: id }).then(function() {
                this.loadBooks();
            });
        },

        loadBooks: function() {
            this.load('lending/get', {}).then(function(response) {
                this.borrowed = response.body.borrowed;
                this.borrowing = response.body.borrowing;
                this.requested = response.body.requested;
                this.requesting = response.body.requesting;
            });
        },
        
        rejected: function(id) {
            this.save('lending/rejected', { id: id }).then(function() {
                this.loadBooks();
            });
        },
        
        returned: function(id) {
            this.save('lending/returned', { id: id }).then(function() {
                this.loadBooks();
            });
        },
    },
};
