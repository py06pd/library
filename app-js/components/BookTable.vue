<template>
    <table class="cic-table">
        <thead>
            <tr>
                <th v-if="$root.user.hasRole('ROLE_LIBRARIAN') && columns.indexOf('selected') !== -1"></th>
                <th v-if="columns.indexOf('number') !== -1" style="width:30px">#</th>
                <th v-if="columns.indexOf('title') !== -1">Title</th>
                <th v-if="columns.indexOf('type') !== -1" class="not-for-mobile">Type</th>
                <th v-if="columns.indexOf('author') !== -1">Author</th>
                <th v-if="columns.indexOf('genre') !== -1" class="not-for-mobile">Genre</th>
                <th v-if="columns.indexOf('series') !== -1" class="not-for-mobile">Series</th>
                <th v-if="columns.indexOf('owner') !== -1" class="not-for-mobile">Owner</th>
                <th v-if="columns.indexOf('read') !== -1" class="not-for-mobile">Read</th>
            </tr>
        </thead>
        <tbody>
            <tr v-for="book in books" :class="{ 'book-wish' : book.isOnWishlist($root.user.getId()), 'book-read' : book.hasBeenReadBy($root.user.getId()), 'book-owned' : book.isOwnedBy($root.user.getId()) }">
                <td v-if="$root.user.hasRole('ROLE_LIBRARIAN') && columns.indexOf('selected') !== -1">
                    <el-checkbox v-if="$root.user.hasRole('ROLE_ADMIN') || book.isOnlyUser($root.user.getId())" @change="onRowSelected(book.getId())" />
                </td>
                <td v-if="columns.indexOf('number') !== -1">
                    {{ book.getSeriesById(seriesId).getNumber() }}
                </td>
                <td v-if="columns.indexOf('title') !== -1" class="primary" @click="openMenu(book)">
                    {{ book.getName() }}
                </td>
                <td v-if="columns.indexOf('type') !== -1" class="not-for-mobile">
                    {{ book.getType() }}
                </td>
                <td v-if="columns.indexOf('author') !== -1">
                    <span class="author-link" v-for="a in book.getAuthors()" @click="selectAuthor(a.getId())">
                        {{ a.getName() }}
                    </span>
                </td>
                <td v-if="columns.indexOf('genre') !== -1" class="not-for-mobile">
                    {{ book.getGenres().join(', ') }}
                </td>
                <td v-if="columns.indexOf('series') !== -1" class="not-for-mobile">
                    <span class="series-link" v-for="s in book.getSeries()" @click="selectSeries(s.getId())">
                        {{ s.getName() }}
                    </span>
                </td>
                <td v-if="columns.indexOf('owner') !== -1" class="not-for-mobile">
                    {{ book.getOwnerNames().join(', ') }}
                </td>
                <td v-if="columns.indexOf('read') !== -1" class="not-for-mobile">
                    {{ book.getReadByNames().join(', ') }}
                </td>
            </tr>
        </tbody>
    </table>
</template>

<script>
    import { Checkbox } from 'element-ui';
    import BookMenu from './BookMenu.vue';

    export default {
        name: 'book-table',
        components: {
            BookMenu,
            'el-checkbox': Checkbox,
        },
        props: {
            books : { type: Array, default: function () { return []; } },
            columns : { type: Array, default: function () { return []; } },
            selected : { type: Array, default: function () { return []; } },
            seriesId : { type: Number, default: 0 },
        },
        methods: {
            onRowSelected: function(val) {
                if (this.selected.indexOf(val) === -1) {
                    this.selected.push(val);
                } else {
                    this.selected.slice(this.selected.indexOf(val), 1);
                }

                this.$emit('input', this.selected);
            },

            openMenu: function(book) {
                this.$emit('click', book);
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
