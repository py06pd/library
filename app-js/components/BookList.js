var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-book-list',
    template: require('./BookList.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            authors: [],
            genres: [],
            people: [],
            series: [],
            types: [],
            books: [],
            editing: {},
            emptyBook: {},
            formOpen: false,
            index: -1,
        };
    },
    created: function () {
        this.loadBooks();
    },
    methods: {
        loadBooks: function() {
            this.post('getData', {}).then(function(response) {
                this.books = response.body.data;
                this.authors = response.body.authors;
                this.genres = response.body.genres;
                this.people = response.body.people;
                this.series = response.body.series;
                this.types = response.body.types;
            });
        },
        
        openAdd: function() {
            this.index = -1;
            this.editing = JSON.parse(JSON.stringify(this.emptyBook));
            this.formOpen = true;
        },
        
        save: function() {
            if (this.index === -1) {
                this.books.add(JSON.parse(JSON.stringify(this.editing)));
            } else {
                this.books[this.index] = JSON.parse(JSON.stringify(this.editing));
            }
            
            this.formOpen = false;
        },
        
        onRowSelected: function(val) {
            this.post('getItem', { name: val.name }).then(function(response) {
                this.editing = JSON.parse(JSON.stringify(response.body.data));
                this.formOpen = true;
            });
        },
    },
};
