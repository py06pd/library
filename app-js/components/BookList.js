import BookMenu from './BookMenu';
var Http = require('../mixins/Http');

export default {
    name: 'cic-book-list',
    template: require('./BookList.template.html'),
    mixins: [ Http ],
    components: {
        'book-menu': BookMenu,
    },
    data: function () {
        return {
            authors: [],
            genres: [],
            series: [],
            types: [],
            books: [],
            menu: { id: 0 },
            menuMode: 0,
            // filters
            fields: ['author', 'genre', 'owner', 'read', 'series', 'type'],
            operators: ['equals', 'does not equal'],
            values: [],
            filter: { field: '', operator: '', value: '' },
            filters: [],
            selected: [],
            start: 0,
        };
    },
    created: function () {
        this.loadBooks();
    },
    methods: {
        addFilter: function() {
            var alert = '';
            
            if (this.filter.field === '') {
                alert = 'Please choose field for filter';
            } else if (this.filter.operator === '') {
                alert = 'Please choose operator for filter';
            } else if (this.filter.value === '') {
                alert = 'Please choose value for filter';
            }
            
            if (alert !== '') {
                this.$notify({ title: 'Warning', message: alert, type: 'warning' });
            } else {
                this.filters.push({
                    field: this.filter.field,
                    operator: this.filter.operator,
                    value: this.filter.value,
                });
                this.loadBooks();
            }
        },
        
        bookMenuChange: function (val) {
            this.menuMode = val;
            
            if (val === 0) {
                this.loadBooks();
            }
        },
        
        deleteItems: function() {
            this.save('deleteItems', { ids: this.selected }).then(function() {
                this.loadBooks();
            });
        },

        filterFieldChange: function(val) {
            this.values = {};
            
            switch (val) {
                case 'author':
                    for (var a in this.authors) {
                        this.values[this.authors[a]] = this.authors[a];
                    }
                    break;
                case 'genre':
                    for (var g in this.genres) {
                        this.values[this.genres[g]] = this.genres[g];
                    }
                    break;
                case 'owner':
                case 'read':
                    for (var u in this.$root.users) {
                        this.values[u] = this.$root.users[u].name;
                    }
                    break;
                case 'series':
                    for (var s in this.series) {
                        this.values[this.series[s].id] = this.series[s].name;
                    }
                    break;
                case 'type':
                    for (var t in this.types) {
                        this.values[this.types[t]] = this.types[t];
                    }
                    break;
            }
        },

        loadBooks: function() {
            this.load('getData', { filters: JSON.stringify(this.filters), start: this.start }).then(function(response) {
                if (this.start === 0) {
                    this.books = response.body.data;
                } else {
                    this.books.push.apply(this.books, response.body.data);
                }
                this.authors = response.body.authors;
                this.genres = response.body.genres;
                this.series = response.body.series;
                this.types = response.body.types;
                this.$root.user = { id: 0, name: '', role: '' };
                if (response.body.user !== null) {
                    this.$root.user = response.body.user;
                }
                this.$root.requests = response.body.requests;
            });
        },
        
        loadMore: function() {
            this.start += 15;
            this.loadBooks();
        },
        
        onRowSelected: function(val) {
            this.selected = [];
            for (var i in val) {
                this.selected.push(val[i].id);
            }
        },
        
        openAdd: function() {
            this.menu = { id: -1 };
            this.menuMode = 2;
        },
        
        openMenu: function(book) {
            this.menu = book;
            this.menuMode = 1;
        },
        
        removeFilter: function(filterIndex) {
            this.filters.splice(filterIndex, 1);
            this.loadBooks();
        },
        
        
        selectAuthor: function (id) {
            this.$router.push('/author/' + id);
        },
        
        selectSeries: function (id) {
            this.$router.push('/series/' + id);
        },
    },
};
