var Http = require('../mixins/Http');

module.exports = {
    name: 'cic-logs-list',
    template: require('./LogsList.template.html'),
    mixins: [ Http ],
    data: function () {
        return {
            contents: '',
            files: [],
            showLogs: false,
        };
    },
    created: function () {
        this.loadFiles();
    },
    methods: {
        loadFiles: function() {
            this.load('getLogFiles', {}).then(function(response) {
                this.files = response.body.files;
            });
        },
        
        onFileSelected: function(file) {
            this.load('getLogFile', { file: file }).then(function(response) {
                this.contents = response.body.contents;
                this.showLogs = true;
            });
        },
    },
};
