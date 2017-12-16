import EditSeries from './EditSeries';
var Http = require('../mixins/Http');

export default {
    name: 'series',
    template: require('./Series.template.html'),
    mixins: [ Http ],
    components: {
        'edit-series': EditSeries,
    },
    data: function () {
        return {
            series: [],
            selected: 0,
        };
    },
    created: function () {
        this.loadSeries();
    },
    methods: {
        loadSeries: function() {
            this.load('series', {}).then(function(response) {
                this.series = response.body.series;
            });
        },
        
        select: function(id) {
            this.selected = id;
        },
    },
};