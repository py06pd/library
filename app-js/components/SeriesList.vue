<template>
    <div>
        <div class="series-tracked">
            <div v-for="seriesId in seriesIds" @click="select(seriesId)" :class="{ 'selected':(selected === seriesId) }">
                <series-books :seriesId="seriesId"></series-books>
            </div>
        </div>
        <div class="series-selected" v-if="selected > 0">
            <series-books :seriesId="selected"></series-books>
        </div>
    </div>
</template>

<script>
    import SeriesBooks from './SeriesBooks.vue';
    let Http = require('../mixins/Http');

    export default {
        name: 'series-list',
        mixins: [ Http ],
        components: { SeriesBooks },
        data: function () {
            return {
                seriesIds: [],
                selected: 0,
            };
        },
        created: function () {
            this.loadSeries();
        },
        methods: {
            loadSeries: function() {
                this.load('series/tracked', {}).then(function(response) {
                    this.seriesIds = response.body.seriesIds;
                });
            },

            select: function(seriesId) {
                this.selected = seriesId;
            },
        },
    };
</script>
