import EditSeries from './EditSeries';
var Http = require('../mixins/Http');

export default {
    name: 'editauthor',
    template: require('./EditAuthor.template.html'),
    mixins: [ Http ],
    components: {
        'edit-series': EditSeries,
    },
    props: {
        id: Number,
        showSeries: {
            Type: Boolean,
            Default:true,
        },
    },
    data: function () {
        return {
            author: { name: '' },
            series: [],
            selected: -1,
            tracking: false,
            total: 0,
            owned: 0,
            read: 0,
        };
    },
    
    created: function () {
        this.loadAuthor();
    },
    
    watch: {
        id: function() {
            this.loadAuthor();
        },
    },
    
    computed: {
        percentage: function() {
            if (this.total === 0) {
                return 0;
            }
            
            return parseInt(this.read * 100 / this.total);
        },
    },
    methods: {
        loadAuthor: function() {
            this.load('author/get', { id: this.id }).then(function(response) {
                this.total = response.body.total;
                this.read = response.body.read;
                this.owned = response.body.owned;
                this.series = response.body.series;
                this.author = response.body.author;
                this.tracking = response.body.tracking;
            });
        },
        
        select: function(id) {
            this.selected = id;
        },
        
        track: function() {
            this.save(this.tracking ? 'author/untrack' : 'author/track', { id: this.id }).then(function(response) {
                if (response.body.status === 'OK') {
                    this.tracking = !this.tracking;
                }
            });
        },
    },
};
