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
            editing: { id: 0 },
            formOpen: false,
            menu: { id: 0 },
            menuOpen: false,
            newSeries: { id: '', name: '', number: '' },
            // filters
            fields: ['author', 'genre', 'owner', 'read', 'series', 'type'],
            operators: ['equals', 'does not equal'],
            values: [],
            filter: { field: '', operator: '', value: '' },
            filters: [],
            selected: [],
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
        
        closeBookMenu: function (val) {
            this.menuOpen = false;
            
            if (val === 1) {
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
            this.load('getData', { filters: JSON.stringify(this.filters) }).then(function(response) {
                this.books = response.body.data;
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
        
        onRowSelected: function(val) {
            this.selected = [];
            for (var i in val) {
                this.selected.push(val[i].id);
            }
        },
        
        openAdd: function() {
            this.editing = JSON.parse(JSON.stringify({
                id: -1,
                name: '',
                type: '',
                genres: [],
                authors: [],
                series: [],
            }));
            this.formOpen = true;
        },
        
        openEdit: function(val) {
            this.menuOpen = false;
            this.load('book/get', { id: val }).then(function(response) {
                this.editing = JSON.parse(JSON.stringify(response.body.data));
                this.formOpen = true;
            });
        },
        
        openMenu: function(book) {
            this.menu = book;
            this.menuOpen = true;
        },
        
        removeFilter: function(filterIndex) {
            this.filters.splice(filterIndex, 1);
            this.loadBooks();
        },
        
        saveItem: function(close) {
            var data = JSON.stringify(this.editing);
            this.save('book/save', { data: data }).then(function() {
                this.loadBooks();
                this.showSucessMessage('Update successful');
                if (close) {
                    this.formOpen = false;
                }
            });
        },
        
        selectSeries: function (id) {
            this.$router.push('/series/' + id);
        },
        
        seriesChange: function(val) {
            if (val === '') {
                for (var i in this.editing.series) {
                    if (this.editing.series[i].id === '') {
                        if (this.editing.series.length === 1) {
                            this.editing.series = [];
                        } else {
                            this.editing.series.splice(i, 1);
                        }
                        return;
                    }
                }
            } else {
                this.editing.series.push({ id: val, name: this.series[val].name, number: '' });
                this.newSeries = { id: '', number: '' };
            }
        },
    },
};
