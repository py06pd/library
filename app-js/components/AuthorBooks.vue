<template>
    <div>
        <div class="author-head">
            <h1 class="author-title">{{ author.getName() }}</h1>
            <el-button
                :type="tracking?'primary':'default'"
                icon="share"
                @click="track"/>
            <el-progress
                :text-inside="true"
                :stroke-width="18"
                :percentage="percentage"
                class="author-progress"
                status="success"/>
        </div>
        <template v-if="books.length > 0">
            <div v-if="showSeries">
                <div class="series-tracked">
                    <div
                        v-for="seriesId in seriesIds"
                        :key="seriesId"
                        :class="{ 'selected':(selected === seriesId) }"
                        @click="select(seriesId)">
                        <series-books :series-id="seriesId" :author-id="author.getId()"/>
                    </div>
                </div>
                <div v-if="selected > -1" class="series-selected">
                    <series-books :series-id="selected" :author-id="author.getId()"/>
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
import Http from '../mixins/Http';

export default {
    name: 'AuthorBooks',
    components: {
        'el-button': Button,
        'el-progress': Progress,
        SeriesBooks,
    },
    mixins: [ Http ],
    props: {
        authorId: {
            type: Number,
            required: true,
        },
        showSeries: {
            type: Boolean,
            default:true,
        },
    },
    data () {
        return {
            author: new Author(),
            books: [],
            selected: -1,
            seriesIds: [],
            tracking: false,
        };
    },

    computed: {
        percentage () {
            if (this.books.length === 0) {
                return 0;
            }

            let user = this.$root.user;
            let read = this.books.filter(x => x.hasBeenReadBy(user.getId()));
            return Math.floor(read.length * 100 / this.books.length);
        },
    },

    watch: {
        authorId () {
            this.loadAuthor();
        },
    },

    created () {
        this.loadAuthor();
    },

    methods: {
        loadAuthor () {
            let self = this;
            this.seriesIds = [];
            this.load('author/get', { authorId: this.authorId }).then((response) => {
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

        select (seriesId) {
            this.selected = seriesId;
        },

        track () {
            this.save(this.tracking ? 'author/untrack' : 'author/track', { authorId: this.authorId }).then((response) => {
                if (response.body.status === 'OK') {
                    this.tracking = !this.tracking;
                }
            });
        },
    },
};
</script>
