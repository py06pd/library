<template>
    <div id="wishlist">
        <table class="cic-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Authors</th>
                    <th>Notes</th>
                    <template v-if="$root.user.getId() === userId">
                        <th>Got</th>
                        <th>Remove</th>
                    </template>
                    <template v-else>
                        <th style="width:110px">Gift From</th>
                        <th>Gift</th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <tr v-for="book in books">
                    <td>{{ book.getName() }}</td>
                    <td>
                        <span v-for="a in book.getAuthors()" style="padding-right: 5px">
                            {{ a.getName() }}
                        </span>
                    </td>
                    <td>
                        <el-button size="small" icon="edit" @click="openNotes(book)"></el-button>
                        <span class="listNotes">
                            <template v-if="book.getNotes(userId)">
                                <template v-for="part in book.getNotes(userId).split(' ')">
                                    <a v-if="part.substring(0, 4) === 'http'" :href="part" target="_blank">{{ part }}</a>
                                    <span v-else>{{ part }}</span>
                                </template>
                            </template>
                        </span>
                    </td>
                    <template v-if="$root.user.getId() === userId">
                        <td><el-button size="small" icon="check" @click="own(book.getId())"></el-button></td>
                        <td><el-button size="small" icon="close" @click="remove(book.getId())"></el-button></td>
                    </template>
                    <template v-else>
                        <td>{{ book.getGiftedFrom(userId) }}</td>
                        <td><el-button v-if="!book.getGiftedFrom(userId)" size="small" icon="check" @click="gift(book.getId())"></el-button></td>
                    </template>
                </tr>
            </tbody>
        </table>

        <el-dialog id="notes" :visible.sync="notesOpen">
            <el-input type="textarea" :rows="2" v-model="notes.text"></el-input>
            <el-button @click="saveNotes">Save</el-button>
            <el-button @click="closeNotes">Close</el-button>
        </el-dialog>
    </div>
</template>

<script>
    import { Button, Dialog, Input } from 'element-ui';
    import Book from '../models/book';
    let Http = require('../mixins/Http');

    export default {
        name: 'book-wishlist',
        mixins: [ Http ],
        components: {
            'el-button': Button,
            'el-dialog': Dialog,
            'el-input': Input,
        },
        props: {
            userId: Number,
        },
        data: function () {
            return {
                books: [],
                notes: { id: 0, text: '' },
                notesOpen: false,
            };
        },
        created: function () {
            this.loadBooks();
        },
        methods: {
            closeNotes: function() {
                this.notesOpen = false;
                this.notes = { id: 0, text: '' };
            },

            gift: function(bookId) {
                this.save('wishlist/gift', { bookId: bookId, userId: this.userId }).then(function() {
                    this.loadBooks();
                });
            },

            loadBooks: function() {
                this.showStatus();
                this.load('wishlist/get', { userId: this.userId }, 'Loading...', false).then(function(response) {
                    this.clearStatus();
                    this.books = response.body.books.map(x => new Book(x));
                });
            },

            openNotes: function(book) {
                this.notes.text = book.getNotes();
                this.notes.id = book.getId();
                this.notesOpen = true;
            },

            own: function(bookId) {
                this.save('book/own', { bookId: bookId }).then(function() {
                    this.loadBooks();
                });
            },

            remove: function(bookId) {
                this.save('book/unwish', { bookId: bookId }).then(function() {
                    this.loadBooks();
                });
            },

            saveNotes: function() {
                this.save('notes/save', { bookId: this.notes.id, userId: this.userId, text: this.notes.text }).then(function() {
                    this.notes = { id: 0, text: '' };
                    this.notesOpen = false;
                    this.loadBooks();
                });
            },
        },
    };
</script>