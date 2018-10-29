<template>
    <div>
        <el-button-group id="controls">
            <template v-if="$root.user.hasRole('ROLE_LIBRARIAN')">
                <el-button type="primary" icon="plus" @click="openAdd"></el-button>
                <el-button type="primary" icon="delete" @click="deleteItems"></el-button>
            </template>
            <el-button type="primary" icon="search" @click="openSearch"></el-button>
        </el-button-group>

        <book-filter v-show="searchOpen" @input="handleFilterChange" />

        <book-table
            :class="{ 'not-for-mobile': menuMode === 2 }"
            :books="books"
            :columns="['selected','title','type','author','genre','series','owner','read']"
            :selected="selected"
            @click="openMenu"
            @input="onRowSelected">
        </book-table>
        <el-button type="primary" style="width:100%" @click="loadMore">Load More</el-button>
        <book-menu :book="menu" :mode="menuMode" @change="bookMenuChange"></book-menu>
    </div>
</template>

<script>
    import { Button, ButtonGroup } from 'element-ui';
    import Book from '../models/book';
    import BookFilter from './BookFilter.vue';
    import BookMenu from './BookMenu.vue';
    import BookTable from './BookTable.vue';
    let Http = require('../mixins/Http');

    export default {
        name: 'cic-book-list',
        mixins: [ Http ],
        components: {
            BookFilter,
            BookMenu,
            BookTable,
            'el-button': Button,
            'el-button-group' : ButtonGroup,
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
                this.save('books/delete', { bookIds: this.selected }).then(function() {
                    this.loadBooks(this.filters);
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
                this.selected = val;
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
