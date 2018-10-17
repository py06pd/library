import BookMenu from './BookMenu.vue';
var Http = require('../mixins/Http');

export default {
    name: 'editseries',
    template: require('./EditSeries.template.html'),
    mixins: [ Http ],
    components: {
        'book-menu': BookMenu,
    },
    props: {
        id: Number,
        authorid: { Default:0 },
    },
    data: function () {
        return {
            series: { name: '' },
            main: {},
            other: [],
            tracking: false,
            userbooks: {},
            menuMode: 0,
            menu: { id: 0 },
        };
    },
    
    created: function () {
        this.loadBooks();
    },
    
    watch: {
        id: function() {
            this.loadBooks();
        },
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

        bookMenuChange: function (val) {
            this.menuMode = val;
            
            if (val === 0) {
                this.loadBooks();
            }
        },
        
        loadBooks: function() {
            this.load('series/get', { id: this.id, authorid: this.authorid }).then(function(response) {
                this.main = response.body.main;
                this.other = response.body.other;
                this.series = response.body.series;
                this.tracking = response.body.tracking;
                this.userbooks = response.body.userbooks;
            });
        },
        
        openMenu: function(book) {
            this.menu = book;
            this.menuMode = 1;
        },
        
        selectAuthor: function (id) {
            this.$router.push('/author/' + id);
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
