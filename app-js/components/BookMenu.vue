<template>
    <div>
        <el-dialog id="bookMenu" :visible="mode === 1" :before-close="close" :class="{ 'not-for-mobile': mode === 2 }">
            <ul>
                <li><el-button @click="openEdit">Edit</el-button></li>
                <li v-if="book.isOwnedBy($root.user.getId())">
                    <el-button @click="disownBook">I don't own this</el-button>
                </li>
                <template v-else>
                    <li v-if="book.hasOwner() && book.canRequest($root.user.getId())">
                        <el-button @click="borrowRequest">Borrow Request</el-button>
                    </li>
                    <li><el-button @click="wishlist">Add to Wishlist</el-button></li>
                    <li><el-button @click="ownBook">I own this</el-button></li>
                </template>
                <li v-if="book.hasBeenReadBy($root.user.getId())">
                    <el-button @click="unreadBook">I haven't read this</el-button>
                </li>
                <li v-else><el-button @click="readBook">I've read this</el-button></li>
            </ul>
        </el-dialog>

        <book-form :id="book.getId()" :formOpen="mode === 2" @change="close"></book-form>
    </div>
</template>

<script>
    import { Button, Dialog } from 'element-ui';
    import Book from '../models/book';
    import BookForm from './BookForm.vue';
    let Http = require('../mixins/Http');

    export default {
        name: 'book-menu',
        mixins: [ Http ],
        components: {
            'el-button': Button,
            'el-dialog': Dialog,
            BookForm,
        },
        props: {
            book : { type: Object, default: new Book() },
            mode: { type: Number, default: 0 },
        },
        methods: {
            borrowRequest: function() {
                this.save('lending/request', { bookId: this.book.getId() }).then(function(response) {
                    if (response.body.status === 'OK') {
                        this.close();
                    }
                });
            },

            close: function() {
                this.$emit('change', 0);
            },

            openEdit: function() {
                this.$emit('change', 2);
            },

            ownBook: function() {
                this.save('book/own', { bookId: this.book.getId() }).then(function(response) {
                    if (response.body.status === 'OK') {
                        this.close();
                    }
                });
            },

            readBook: function() {
                this.save('book/read', { bookId: this.book.getId() }).then(function(response) {
                    if (response.body.status === 'OK') {
                        this.close();
                    }
                });
            },

            disownBook: function() {
                this.save('book/disown', { bookId: this.book.getId() }).then(function(response) {
                    if (response.body.status === 'OK') {
                        this.close();
                    }
                });
            },

            unreadBook: function() {
                this.save('book/unread', { bookId: this.book.getId() }).then(function(response) {
                    if (response.body.status === 'OK') {
                        this.close();
                    }
                });
            },

            wishlist: function() {
                this.save('book/wish', { bookId: this.book.getId() }).then(function(response) {
                    if (response.body.status === 'OK') {
                        this.close();
                    }
                });
            },
        },
    };
</script>