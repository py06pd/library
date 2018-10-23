<template>
    <div>
        <div class="series-head">
            <div>
                <h1 class="series-title">{{ series.getName() }}</h1>
                <el-button v-if="series.getId() > 0" title="track" :type="tracking?'primary':'default'" icon="share" @click="track"></el-button>
            </div>
            <el-progress
                class="series-progress"
                :text-inside="true"
                :stroke-width="18"
                :percentage="percentage"
                status="success">
            </el-progress>
        </div>
        <template v-if="main.length > 0">
            <h2 v-if="other.length > 0">Main Sequence</h2>

            <book-table
                :class="{ 'not-for-mobile': menuMode === 2 }"
                :books="main"
                :columns="['number','title','type','author','genre']"
                :series-id="series.getId()"
                @click="openMenu">
            </book-table>
        </template>

        <template v-if="other.length > 0">
            <h2 v-if="main.length > 0">Other</h2>

            <book-table
                :class="{ 'not-for-mobile': menuMode === 2 }"
                :books="other"
                :columns="['title','type','author','genre']"
                @click="openMenu">
            </book-table>
        </template>

        <book-menu :book="menu" :mode="menuMode" @change="bookMenuChange"></book-menu>
    </div>
</template>

<script>
    import { Button, Progress } from 'element-ui';
    import Book from '../models/book';
    import BookMenu from './BookMenu.vue';
    import BookTable from './BookTable.vue';
    import Series from '../models/series';
    let Http = require('../mixins/Http');

    export default {
        name: 'series-books',
        mixins: [ Http ],
        components: {
            'el-button': Button,
            'el-progress': Progress,
            BookMenu,
            BookTable,
        },
        props: {
            seriesId: Number,
            authorId: { Default:0 },
        },
        data: function () {
            return {
                series: new Series(),
                main: [],
                other: [],
                tracking: false,
                menuMode: 0,
                menu: new Book(),
            };
        },

        created: function () {
            this.loadBooks();
        },

        watch: {
            seriesId: function() {
                this.loadBooks();
            },
        },

        computed: {
            percentage: function() {
                if (this.main.length + this.other.length === 0) {
                    return 0;
                }

                let user = this.$root.user;
                let read = this.main.filter(x => x.hasBeenReadBy(user.getId()));
                return parseInt(read.length * 100 / (this.main.length + this.other.length));
            },
        },
        methods: {
            bookMenuChange: function (val) {
                this.menuMode = val;

                if (val === 0) {
                    this.loadBooks();
                }
            },

            loadBooks: function() {
                this.load('series/get', { seriesId: this.seriesId, authorId: this.authorId }).then(function(response) {
                    this.main = response.body.main.map(x => new Book(x));
                    this.other = response.body.other.map(x => new Book(x));
                    this.series = new Series(response.body.series);
                    this.tracking = response.body.tracking;
                });
            },

            openMenu: function(book) {
                this.menu = book;
                this.menuMode = 1;
            },

            track: function() {
                this.save(this.tracking ? 'series/untrack' : 'series/track', { seriesId: this.seriesId }).then(function(response) {
                    if (response.body.status === 'OK') {
                        this.tracking = !this.tracking;
                    }
                });
            },
        },
    };
</script>
