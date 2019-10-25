<template>
    <div>
        <div class="series-head">
            <div>
                <h1 class="series-title">{{ series.getName() }}</h1>
                <el-button
                    v-if="series.getId() > 0"
                    :type="tracking?'primary':'default'"
                    title="track"
                    icon="share"
                    @click="track"/>
            </div>
            <el-progress
                :text-inside="true"
                :stroke-width="18"
                :percentage="percentage"
                class="series-progress"
                status="success"/>
        </div>
        <template v-if="main.length > 0">
            <h2 v-if="other.length > 0">Main Sequence</h2>

            <book-table
                :class="{ 'not-for-mobile': menuMode === 2 }"
                :books="main"
                :columns="['number','title','type','author','genre']"
                :series-id="series.getId()"
                @click="openMenu"/>
        </template>

        <template v-if="other.length > 0">
            <h2 v-if="main.length > 0">Other</h2>

            <book-table
                :class="{ 'not-for-mobile': menuMode === 2 }"
                :books="other"
                :columns="['title','type','author','genre']"
                @click="openMenu"/>
        </template>

        <book-menu
            :book="menu"
            :mode="menuMode"
            @change="bookMenuChange"/>
    </div>
</template>

<script>
import { Button, Progress } from 'element-ui';
import Book from '../models/book';
import BookMenu from './BookMenu.vue';
import BookTable from './BookTable.vue';
import Series from '../models/series';
import Http from '../mixins/Http';

export default {
    name: 'SeriesBooks',
    components: {
        'el-button': Button,
        'el-progress': Progress,
        BookMenu,
        BookTable,
    },
    mixins: [ Http ],
    props: {
        seriesId: {
            type: Number,
            required: true,
        },
        authorId: {
            type: Number,
            default:0,
        },
    },
    data () {
        return {
            series: new Series(),
            main: [],
            other: [],
            tracking: false,
            menuMode: 0,
            menu: new Book(),
        };
    },

    computed: {
        percentage () {
            if (this.main.length + this.other.length === 0) {
                return 0;
            }

            let user = this.$root.user;
            let read = this.main.filter(x => x.hasBeenReadBy(user.getId()));
            return Math.ceil(read.length * 100 / (this.main.length + this.other.length));
        },
    },

    watch: {
        seriesId () {
            this.loadBooks();
        },
    },

    created () {
        this.loadBooks();
    },

    methods: {
        bookMenuChange (val) {
            this.menuMode = val;

            if (val === 0) {
                this.loadBooks();
            }
        },

        loadBooks () {
            this.load('series/get', { seriesId: this.seriesId, authorId: this.authorId }).then((response) => {
                this.main = response.body.main.map(x => new Book(x));
                this.other = response.body.other.map(x => new Book(x));
                this.series = new Series(response.body.series);
                this.tracking = response.body.tracking;
            });
        },

        openMenu (book) {
            this.menu = book;
            this.menuMode = 1;
        },

        track () {
            this.save(this.tracking ? 'series/untrack' : 'series/track', { seriesId: this.seriesId }).then((response) => {
                if (response.body.status === 'OK') {
                    this.tracking = !this.tracking;
                }
            });
        },
    },
};
</script>
