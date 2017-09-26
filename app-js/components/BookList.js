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
            formOpen: false,
            newSeries: { name: '', number: '' },
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
        
        deleteItems: function() {
            this.save('deleteItems', { ids: this.selected }).then(function() {
                this.loadBooks();
            });
        },

        filterFieldChange: function(val) {
            switch (val) {
                case 'author':
                    this.values = JSON.parse(JSON.stringify(this.authors));
                    break;
                case 'genre':
                    this.values = JSON.parse(JSON.stringify(this.genres));
                    break;
                case 'owner':
                    this.values = JSON.parse(JSON.stringify(this.people));
                    break;
                case 'read':
                    this.values = JSON.parse(JSON.stringify(this.people));
                    break;
                case 'series':
                    this.values = JSON.parse(JSON.stringify(this.series));
                    break;
                case 'type':
                    this.values = JSON.parse(JSON.stringify(this.types));
                    break;
            }
        },

        loadBooks: function() {
            this.load('getData', { filters: JSON.stringify(this.filters) }).then(function(response) {
                this.books = response.body.data;
                this.authors = response.body.authors;
                this.genres = response.body.genres;
                this.people = response.body.people;
                this.series = response.body.series;
                this.types = response.body.types;
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
                owners: [],
                read: [],
                series: [],
            }));
            this.formOpen = true;
        },
        
        openEdit: function(id) {
            this.load('getItem', { id: id }).then(function(response) {
                this.editing = JSON.parse(JSON.stringify(response.body.data));
                this.formOpen = true;
            });
        },
        
        removeFilter: function(filterIndex) {
            this.filters.splice(filterIndex, 1);
            this.loadBooks();
        },
        
        saveItem: function(close) {
            var data = JSON.stringify(this.editing);
            this.save('saveItem', { data: data }).then(function() {
                this.loadBooks();
                this.showSucessMessage('Update successful');
                if (close) {
                    this.formOpen = false;
                }
            });
        },
        
        seriesChange: function(val) {
            if (val === '') {
                for (var i in this.editing.series) {
                    if (this.editing.series[i].name === '') {
                        if (this.editing.series.length === 1) {
                            this.editing.series = [];
                        } else {
                            this.editing.series.splice(i, 1);
                        }
                        return;
                    }
                }
            } else {
                this.editing.series.push({ name: val, number: '' });
                this.newSeries = { name: '', number: '' };
            }
        },
    },
};
