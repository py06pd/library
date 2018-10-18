<template>
    <div id="wishlist">
        <table class="cic-table el-table">
            <thead>
                <tr>
                    <th class="is-leaf"><div class="cell">Title</div></th>
                    <th class="is-leaf"><div class="cell">Authors</div></th>
                    <th class="is-leaf"><div class="cell">Notes</div></th>
                    <template v-if="$root.user.getId() === userId">
                        <th class="is-leaf"><div class="cell">Got</div></th>
                        <th class="is-leaf"><div class="cell">Remove</div></th>
                    </template>
                    <template v-else>
                        <th class="is-leaf" style="width:110px"><div class="cell">Gift From</div></th>
                        <th class="is-leaf"><div class="cell">Gift</div></th>
                    </template>
                </tr>
            </thead>
            <tbody>
                <tr v-for="book in books">
                    <td><div class="cell">{{ book.getName() }}</div></td>
                    <td>
                        <div class="cell">
                            <span v-for="a in book.getAuthors()" style="padding-right: 5px">
                                {{ a.getName() }}
                            </span>
                        </div>
                    </td>
                    <td>
                        <div class="cell">
                            <el-button size="small" icon="edit" @click="openNotes(book)"></el-button>
                            <span class="listNotes">
                                <template v-if="book.getNotes(userId)">
                                    <template v-for="part in book.getNotes(userId).split(' ')">
                                        <a v-if="part.substring(0, 4) === 'http'" :href="part" target="_blank">{{ part }}</a>
                                        <span v-else>{{ part }}</span>
                                    </template>
                                </template>
                            </span>
                        </div>
                    </td>
                    <template v-if="$root.user.getId() === userId">
                        <td>
                            <div class="cell"><el-button size="small" icon="check" @click="own(book.getId())"></el-button></div>
                        </td>
                        <td>
                            <div class="cell"><el-button size="small" icon="close" @click="remove(book.getId())"></el-button></div>
                        </td>
                    </template>
                    <template v-else>
                        <td>
                            <div class="cell">{{ book.getGiftedFrom(userId) }}</div>
                        </td>
                        <td>
                            <div v-if="!book.getGiftedFrom(userId)" class="cell">
                                <el-button size="small" icon="check" @click="gift(book.getId())"></el-button>
                            </div>
                        </td>
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
    import { Button, Dialog } from 'element-ui';
    import Book from '../models/book';
    let Http = require('../mixins/Http');

    export default {
        name: 'book-wishlist',
        mixins: [ Http ],
        components: {
            'el-button': Button,
            'el-dialog': Dialog,
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

            gift: function(fromId) {
                this.save('wishlist/gift', { id: fromId, userId: this.userId }).then(function() {
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
                this.save('notes/save', { id: this.notes.id, userId: this.userId, text: this.notes.text }).then(function() {
                    this.notes = { id: 0, text: '' };
                    this.notesOpen = false;
                    this.loadBooks();
                });
            },
        },
    };
</script>