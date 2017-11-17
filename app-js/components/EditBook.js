var Http = require('../mixins/Http');

export default {
    name: 'editbook',
    template: require('./EditBook.template.html'),
    mixins: [ Http ],
    props: ['id', 'formOpen'],
    data: function () {
        return {
            book: { id: 0 },
            newSeries: { id: '', name: '', number: '' },
            authors: [],
            genres: [],
            series: [],
            types: [],
        };
    },
    methods: {
        close: function() {
            this.$emit('change');
        },

        loadBook: function() {
            this.load('book/get', { id: this.id }).then(function(response) {
                this.book = JSON.parse(JSON.stringify(response.body.data));
                this.authors = response.body.authors;
                this.genres = response.body.genres;
                this.series = response.body.series;
                this.types = response.body.types;
            });
        },
        
        saveItem: function(close) {
            var data = JSON.stringify(this.book);
            this.save('book/save', { data: data }).then(function() {
                if (close) {
                    this.close();
                }
            });
        },
        
        seriesChange: function(val) {
            if (val === '') {
                for (var i in this.book.series) {
                    if (this.book.series[i].id === '') {
                        if (this.book.series.length === 1) {
                            this.book.series = [];
                        } else {
                            this.book.series.splice(i, 1);
                        }
                        return;
                    }
                }
            } else {
                // prevent duplicates
                for (var j in this.book.series) {
                    if (this.book.series[j].id === val) {
                        this.newSeries = { id: '', number: '' };
                        return;
                    }
                }
                
                if (Object.keys(this.series).indexOf(val) == -1) {
                    this.book.series.push({ id: val, name: val, number: '' });
                } else {
                    this.book.series.push({ id: val, name: this.series[val].name, number: '' });
                }
                
                this.newSeries = { id: '', number: '' };
            }
        },
    },
};
