<template>
    <div>
        <el-button-group id="controls">
            <template v-if="$root.user.hasRole('ROLE_ADMIN')">
                <el-button type="primary" icon="plus" @click="openAdd"></el-button>
                <el-button type="primary" icon="delete" @click="deleteItems"></el-button>
            </template>
            <el-button type="primary" icon="search" @click="openSearch"></el-button>
        </el-button-group>

        <book-filter v-show="searchOpen" @input="handleFilterChange" />

        <table class="cic-table" :class="{ 'not-for-mobile': menuMode === 2 }">
            <thead>
                <tr>
                    <th v-if="$root.user.hasRole('ROLE_ADMIN')" ></th>
                    <th>Title</th>
                    <th class="not-for-mobile">Type</th>
                    <th>Author</th>
                    <th class="not-for-mobile">Genre</th>
                    <th class="not-for-mobile">Series</th>
                    <th class="not-for-mobile">Owner</th>
                    <th class="not-for-mobile">Read</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="book in books" :class="{ 'book-wish' : book.isOnWishlist($root.user.getId()), 'book-read' : book.hasBeenReadBy($root.user.getId()), 'book-owned' : book.isOwnedBy($root.user.getId()) }">
                    <td v-if="$root.user.hasRole('ROLE_ADMIN')" >
                        <el-checkbox @click="onRowSelected(book.getId())" />
                    </td>
                    <td class="primary" @click="openMenu(book)">{{ book.getName() }}</td>
                    <td class="not-for-mobile">{{ book.getType() }}</td>
                    <td>
                        <span class="author-link" v-for="a in book.getAuthors()" @click="selectAuthor(a.getId())">
                            {{ a.getName() }}
                        </span>
                    </td>
                    <td class="not-for-mobile">{{ book.getGenres().join(', ') }}</td>
                    <td class="not-for-mobile">
                        <span class="series-link" v-for="s in book.getSeries()" @click="selectSeries(s.getId())">
                            {{ s.getName() }}
                        </span>
                    </td>
                    <td class="not-for-mobile">{{ book.getOwnerNames().join(', ') }}</td>
                    <td class="not-for-mobile">{{ book.getReadByNames().join(', ') }}</td>
                </tr>
            </tbody>
        </table>
        <el-button type="primary" style="width:100%" @click="loadMore">Load More</el-button>
        <book-menu :book="menu" :mode="menuMode" @change="bookMenuChange"></book-menu>
    </div>
</template>

<script>
    import { Button, ButtonGroup, Checkbox } from 'element-ui';
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
            'el-button-group' : ButtonGroup,
            'el-checkbox': Checkbox,
        },
        data: function () {
            return {
                books: [],
                menu: new Book(),
                menuMode: 0,
                filters: [],
                searchOpen: false,
                selected: [],
            };
        },
        created: function () {
            if (!this.$root.query) {
                this.loadBooks();
            }
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

            handleFilterChange (newFilters) {
                this.filters = newFilters;
                this.loadBooks(newFilters);
            },

            loadBooks: function(val) {
                if (typeof(val) !== 'undefined') {
                    this.books = [];
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

            openSearch: function() {
                this.searchOpen = !this.searchOpen;
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
