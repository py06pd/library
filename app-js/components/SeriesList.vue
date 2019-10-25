<template>
    <div>
        <div class="series-tracked">
            <div
                v-for="seriesId in seriesIds"
                :key="seriesId"
                :class="{ 'selected':(selected === seriesId) }"
                @click="select(seriesId)">
                <series-books :series-id="seriesId"/>
            </div>
        </div>
        <div v-if="selected > 0" class="series-selected">
            <series-books :series-id="selected"/>
        </div>
    </div>
</template>

<script>
import SeriesBooks from './SeriesBooks.vue';
import Http from '../mixins/Http';

export default {
    name: 'SeriesList',
    components: { SeriesBooks },
    mixins: [ Http ],
    data () {
        return {
            seriesIds: [],
            selected: 0,
        };
    },
    created () {
        this.loadSeries();
    },
    methods: {
        loadSeries () {
            this.load('series/tracked', {}).then((response) => {
                this.seriesIds = response.body.seriesIds;
            });
        },

        select (seriesId) {
            this.selected = seriesId;
        },
    },
};
</script>
