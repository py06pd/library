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
            emptyBook: {
                name: '',
                type: '',
                genres: [],
                authors: [],
                owners: [],
                read: [],
                series: [],
            },
            formOpen: false,
            newSeries: { name: '', number: '' },
            index: -1,
            loading2: false,
        };
    },
    created: function () {
        this.loadBooks();
    },
    methods: {

        loadBooks: function() {
            this.loading2 = true;
            this.post('getData', {}).then(function(response) {
                this.loading2 = false;
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
            this.loading2 = true;
            this.post('getItem', { name: val.name }).then(function(response) {
                this.loading2 = false;
                this.editing = JSON.parse(JSON.stringify(response.body.data));
                this.formOpen = true;
            });
        },
    },
};
