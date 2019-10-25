<template>
    <div>
        <template v-if="$root.user.hasRole('ROLE_LIBRARIAN')">
            <el-button-group id="controls">
                <el-button
                    type="primary"
                    icon="plus"
                    @click="openAdd"/>
                <el-button
                    type="primary"
                    icon="delete"
                    @click="deleteItems"/>
                <el-button
                    type="primary"
                    icon="search"
                    @click="openSearch"/>
            </el-button-group>
        </template>
        <el-button
            v-else
            type="primary"
            icon="search"
            @click="openSearch"/>

        <book-filter v-show="searchOpen" @input="handleFilterChange" />

        <book-table
            :class="{ 'not-for-mobile': menuMode === 2 }"
            :books="books"
            :columns="['selected','title','type','author','genre','series','owner','read']"
            :selected="selected"
            @click="openMenu"
            @input="onRowSelected"/>
        <el-button
            type="primary"
            style="width:100%"
            @click="loadMore">
            Load More
        </el-button>
        <book-menu
            :book="menu"
            :mode="menuMode"
            @change="bookMenuChange"/>
    </div>
</template>

<script>
import { Button, ButtonGroup } from 'element-ui';
import Book from '../models/book';
import BookFilter from './BookFilter.vue';
import BookMenu from './BookMenu.vue';
import BookTable from './BookTable.vue';
import Http from '../mixins/Http';

export default {
    name: 'BookList',
    components: {
        BookFilter,
        BookMenu,
        BookTable,
        'el-button': Button,
        'el-button-group' : ButtonGroup,
    },
    mixins: [ Http ],
    data () {
        return {
            books: [],
            menu: new Book(),
            menuMode: 0,
            filters: [],
            searchOpen: false,
            selected: [],
        };
    },
    created () {
        if (!this.$root.query) {
            this.loadBooks();
        }
    },
    methods: {
        bookMenuChange (val) {
            this.menuMode = val;

            if (val === 0) {
                this.loadBooks(this.filters);
            }
        },

        deleteItems () {
            this.save('books/delete', { bookIds: this.selected }).then(() => {
                this.loadBooks(this.filters);
            });
        },

        handleFilterChange (newFilters) {
            this.filters = newFilters;
            this.loadBooks(newFilters);
        },

        loadBooks (val) {
            if (typeof(val) !== 'undefined') {
                this.books = [];
                this.selected = [];
            }

            this.load('books/get', { filters: JSON.stringify(this.filters), start: this.books.length }).then((response) => {
                response.body.data.forEach(x => {
                    this.books.push(new Book(x));
                });
            });
        },

        loadMore () {
            this.loadBooks();
        },

        onRowSelected (val) {
            this.selected = val;
        },

        openAdd () {
            this.menu = new Book();
            this.menuMode = 2;
        },

        openMenu (book) {
            this.menu = book;
            this.menuMode = 1;
        },

        openSearch () {
            this.searchOpen = !this.searchOpen;
        },

        selectAuthor (authorId) {
            this.$router.push('/author/' + authorId);
        },

        selectSeries (seriesId) {
            this.$router.push('/series/' + seriesId);
        },
    },
};
</script>
