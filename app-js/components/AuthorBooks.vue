<template>
    <div>
        <div class="author-head">
            <h1 class="author-title">{{ author.getName() }}</h1>
            <el-button :type="tracking?'primary':'default'" icon="share" @click="track"></el-button>
            <el-progress
                    class="author-progress"
                    :text-inside="true"
                    :stroke-width="18"
                    :percentage="percentage"
                    status="success">
            </el-progress>
        </div>
        <template v-if="books.length > 0">
            <div v-if="showSeries">
                <div class="series-tracked">
                    <div v-for="seriesId in seriesIds" v-on:click="select(seriesId)" :class="{ 'selected':(selected === seriesId) }">
                        <series-books :seriesId="seriesId" :authorId="author.getId()"></series-books>
                    </div>
                </div>
                <div class="series-selected" v-if="selected > -1">
                    <series-books :seriesId="selected" :authorId="author.getId()"></series-books>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
    import { Button, Progress } from 'element-ui';
    import Author from '../models/author';
    import Book from '../models/book';
    import SeriesBooks from './SeriesBooks.vue';
    let Http = require('../mixins/Http');

    export default {
        name: 'author-books',
        mixins: [ Http ],
        components: {
            'el-button': Button,
            'el-progress': Progress,
            SeriesBooks,
        },
        props: {
            authorId: Number,
            showSeries: {
                Type: Boolean,
                Default:true,
            },
        },
        data: function () {
            return {
                author: new Author(),
                books: [],
                selected: -1,
                seriesIds: [],
                tracking: false,
            };
        },

        created: function () {
            this.loadAuthor();
        },

        watch: {
            authorId: function() {
                this.loadAuthor();
            },
        },

        computed: {
            percentage: function() {
                if (this.books.length === 0) {
                    return 0;
                }

                let user = this.$root.user;
                let read = this.books.filter(x => x.hasBeenReadBy(user.getId()));
                return parseInt(read.length * 100 / this.books.length);
            },
        },
        methods: {
            loadAuthor: function() {
                let self = this;
                this.seriesIds = [];
                this.load('author/get', { authorId: this.authorId }).then(function(response) {
                    this.author = new Author(response.body.author);
                    this.books = response.body.books.map(x => new Book(x));
                    this.books.map(function(x) {
                        if (x.series.length) {
                            x.series.forEach(function(y) {
                                if (self.seriesIds.indexOf(y.seriesId) === -1) {
                                    self.seriesIds.push(y.seriesId);
                                }
                            });
                        } else if (self.seriesIds.indexOf(0) === -1) {
                            self.seriesIds.push(0);
                        }
                    });
                    this.tracking = response.body.tracking;
                });
            },

            select: function(seriesId) {
                this.selected = seriesId;
            },

            track: function() {
                this.save(this.tracking ? 'author/untrack' : 'author/track', { authorId: this.authorId }).then(function(response) {
                    if (response.body.status === 'OK') {
                        this.tracking = !this.tracking;
                    }
                });
            },
        },
    };
</script>
