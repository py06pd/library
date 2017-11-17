import BookMenu from './BookMenu';
var Http = require('../mixins/Http');

export default {
    name: 'editseries',
    template: require('./EditSeries.template.html'),
    mixins: [ Http ],
    components: {
        'book-menu': BookMenu,
    },
    props: ['id'],
    data: function () {
        return {
            series: { name: '' },
            main: {},
            other: [],
            tracking: false,
            userbooks: {},
            menu: { id: 0 },
            menuOpen: false,
            editing: { id: 0 },
        };
    },
    created: function () {
        this.loadBooks();
    },
    computed: {
        percentage: function() {
            if (Object.keys(this.main).length + this.other.length == 0) {
                return 0;
            }
            
            var read = Object.values(this.userbooks).filter(function(b) { return b.read; });
            return parseInt(read.length * 100 / (Object.keys(this.main).length + this.other.length));
        },
    },
    methods: {
        bookClass: function(id) {
            if (Object.keys(this.userbooks).indexOf(id.toString()) !== -1) {
                if (this.userbooks[id.toString()].read) {
                    return 'series-read';
                } else if (this.userbooks[id.toString()].owned) {
                    return 'series-own';
                }
            }
            
            return;
        },

        closeBookMenu: function (val) {
            this.menuOpen = false;
            
            if (val === 1) {
                this.loadBooks();
            }
        },
        
        loadBooks: function() {
            this.load('series/get', { id: this.id }).then(function(response) {
                this.main = response.body.main;
                this.other = response.body.other;
                this.series = response.body.series;
                this.tracking = response.body.tracking;
                this.userbooks = response.body.userbooks;
            });
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
        
        track: function() {
            this.save(this.tracking ? 'series/untrack' : 'series/track', { id: this.id }).then(function(response) {
                if (response.body.status === 'OK') {
                    this.tracking = !this.tracking;
                }
            });
        },
    },
};
