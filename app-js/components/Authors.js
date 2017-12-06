import EditAuthor from './EditAuthor';
var Http = require('../mixins/Http');

export default {
    name: 'authors',
    template: require('./Authors.template.html'),
    mixins: [ Http ],
    components: {
        'edit-author': EditAuthor,
    },
    data: function () {
        return {
            authors: [],
            selected: 0,
        };
    },
    created: function () {
        this.loadAuthors();
    },
    methods: {
        loadAuthors: function() {
            this.load('authors', {}).then(function(response) {
                this.authors = response.body.ids;
            });
        },
        
        select: function(id) {
            this.selected = id;
        },
    },
};
