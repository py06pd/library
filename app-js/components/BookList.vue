<template>
    <div>
        <div id="controls">
            <el-button v-if="$root.user.role === 'ROLE_ADMIN'" type="primary" icon="plus" @click="openAdd">
                Add Entry
            </el-button>
            <el-button v-if="$root.user.role === 'ROLE_ADMIN'" type="primary" icon="delete" @click="deleteItems">
                Delete Selected
            </el-button>

            <book-filter @change="loadBooks" />
        </div>

        <table class="cic-table">
            <thead>
                <tr>
                    <th v-if="$root.user.role === 'ROLE_ADMIN'" ></th>
                    <th>Title</th>
                    <th>Type</th>
                    <th>Author</th>
                    <th>Genre</th>
                    <th>Series</th>
                    <th>Owner</th>
                    <th>Read</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="book in books">
                    <td v-if="$root.user.role === 'ROLE_ADMIN'" >
                        <el-checkbox @click="onRowSelected(book.getId())" />
                    </td>
                    <td class="primary" @click="openMenu(book)">{{ book.getName() }}</td>
                    <td>{{ book.getType() }}</td>
                    <td>
                        <span class="author-link" v-for="a in book.getAuthors()" @click="selectAuthor(a.getId())">
                            {{ a.getName() }}
                        </span>
                    </td>
                    <td>{{ book.getGenres().join(', ') }}</td>
                    <td>
                        <span class="series-link" v-for="s in book.getSeries()" @click="selectSeries(s.getId())">
                            {{ s.getName() }}
                        </span>
                    </td>
                    <td>{{ book.getOwnerNames().join(', ') }}</td>
                    <td>{{ book.getReadByNames().join(', ') }}</td>
                </tr>
            </tbody>
        </table>
        <el-button type="primary" style="width:100%" @click="loadMore">Load More</el-button>

        <book-menu :book="menu" :mode="menuMode" @change="bookMenuChange"></book-menu>
    </div>
</template>

<script>
    import { Button, Checkbox } from 'element-ui';
    import Book from '../models/book';
    import BookMenu from './BookMenu.vue';
    import BookFilter from './BookFilter.vue';
    let Http = require('../mixins/Http');

    export default {
        name: 'cic-book-list',
        mixins: [ Http ],
        components: {
            BookFilter,
            BookMenu,
            'el-button': Button,
            'el-checkbox': Checkbox,
        },
        data: function () {
            return {
                books: [],
                menu: new Book(),
                menuMode: 0,
                filters: [],
                selected: [],
                start: 0,
            };
        },
        created: function () {
            this.loadBooks();
        },
        methods: {
            bookMenuChange: function (val) {
                this.menuMode = val;

                if (val === 0) {
                    this.loadBooks(this.filters);
                }
            },

            deleteItems: function() {
                this.save('deleteItems', { ids: this.selected }).then(function() {
                    this.loadBooks();
                });
            },

            loadBooks: function(val) {
                if (typeof(val) !== 'undefined') {
                    this.books = [];
                    this.filters = val;
                    this.selected = [];
                }

                this.load('books/get', { filters: JSON.stringify(this.filters), start: this.books.length }).then(function(response) {
                    for (let i in response.body.data) {
                        this.books.push(new Book(response.body.data[i]));
                    }
                });
            },

            loadMore: function() {
                this.loadBooks();
            },

            onRowSelected: function(val) {
                if (this.selected.indexOf(val) === -1) {
                    this.selected.push(val);
                } else {
                    this.selected.slice(this.selected.indexOf(val), 1);
                }
            },

            openAdd: function() {
                this.menu = new Book();
                this.menuMode = 2;
            },

            openMenu: function(book) {
                this.menu = book;
                this.menuMode = 1;
            },

            selectAuthor: function (authorId) {
                this.$router.push('/author/' + authorId);
            },

            selectSeries: function (seriesId) {
                this.$router.push('/series/' + seriesId);
            },
        },
    };
</script>

<style scoped>
    #controls > .el-button {
        vertical-align: top;
    }
</style>
