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
                <tr v-for="book in books" :key="book.getId()">
                    <td>{{ book.getName() }}</td>
                    <td>
                        <span
                            v-for="a in book.getAuthors()"
                            :key="a.getId()"
                            style="padding-right: 5px">
                            {{ a.getName() }}
                        </span>
                    </td>
                    <td>
                        <el-button
                            size="small"
                            icon="edit"
                            @click="openNotes(book)"/>
                        <span class="listNotes">
                            <template v-if="book.getNotes(userId)">
                                <template v-for="part in book.getNotes(userId).split(' ')">
                                    <a
                                        v-if="part.substring(0, 4) === 'http'"
                                        :key="part"
                                        :href="part"
                                        target="_blank">{{ part }}</a>
                                    <span v-else :key="part">{{ part }}</span>
                                </template>
                            </template>
                        </span>
                    </td>
                    <template v-if="$root.user.getId() === userId">
                        <td>
                            <el-button
                                size="small"
                                icon="check"
                                @click="own(book.getId())"/>
                        </td>
                        <td>
                            <el-button
                                size="small"
                                icon="close"
                                @click="remove(book.getId())"/>
                        </td>
                    </template>
                    <template v-else>
                        <td>{{ book.getGiftedFrom(userId) }}</td>
                        <td>
                            <el-button
                                v-if="!book.getGiftedFrom(userId)"
                                size="small"
                                icon="check"
                                @click="gift(book.getId())"/>
                        </td>
                    </template>
                </tr>
            </tbody>
        </table>

        <el-dialog id="notes" :visible.sync="notesOpen">
            <el-input
                v-model="notes.text"
                :rows="2"
                type="textarea"/>
            <el-button @click="saveNotes">Save</el-button>
            <el-button @click="closeNotes">Close</el-button>
        </el-dialog>
    </div>
</template>

<script>
import { Button, Dialog, Input } from 'element-ui';
import Book from '../models/book';
import Http from '../mixins/Http';

export default {
    name: 'BookWishlist',
    components: {
        'el-button': Button,
        'el-dialog': Dialog,
        'el-input': Input,
    },
    mixins: [ Http ],
    props: {
        userId: {
            type: Number,
            required: true,
        },
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
            this.save('notes/save', { bookId: this.notes.id, userId: this.userId, text: this.notes.text }).then(() => {
                this.notes = { id: 0, text: '' };
                this.notesOpen = false;
                this.loadBooks();
            });
        },
    },
};
</script>