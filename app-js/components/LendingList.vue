<template>
    <div id="lending-list">
        <h2>Requested By You</h2>
        <table class="cic-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th style="width:160px">Date</th>
                    <th>From</th>
                    <th style="width:90px">Delivered</th>
                    <th style="width:90px">Cancel</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="book in requesting" :key="book.getId()">
                    <td>{{ book.getName() }}</td>
                    <td>{{ book.getRequestedTime($root.user.getId()) }}</td>
                    <td>{{ book.getRequestedBy($root.user.getId()) }}</td>
                    <td>
                        <el-button
                            size="small"
                            icon="check"
                            @click="delivered(book.getId())"/>
                    </td>
                    <td>
                        <el-button
                            size="small"
                            icon="close"
                            @click="cancel(book.getId())"/>
                    </td>
                </tr>
            </tbody>
        </table>
        <h2>Borrowed By You</h2>
        <table class="cic-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th style="width:160px">Date</th>
                    <th>From</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="book in borrowing" :key="book.getId()">
                    <td>{{ book.getName() }}</td>
                    <td>{{ book.getBorrowedTime($root.user.getId()) }}</td>
                    <td>{{ book.getBorrowedBy($root.user.getId()) }}</td>
                </tr>
            </tbody>
        </table>
        <h2>Requested From You</h2>
        <table class="cic-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th style="width:160px">Date</th>
                    <th>By</th>
                    <th style="width:90px">Reject</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="book in requested">
                    <tr v-for="userBook in book.getRequestedFrom($root.user.getId())" :key="userBook.getId()">
                        <td>{{ book.getName() }}</td>
                        <td>{{ userBook.requestedTime }}</td>
                        <td>{{ userBook.name }}</td>
                        <td>
                            <el-button
                                size="small"
                                icon="close"
                                @click="reject(book.getId(), userBook.userId)"/>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
        <h2>Borrowed From You</h2>
        <table class="cic-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th style="width:160px">Date</th>
                    <th>By</th>
                    <th style="width:90px">Returned</th>
                </tr>
            </thead>
            <tbody>
                <template v-for="book in borrowed">
                    <tr v-for="userBook in book.getBorrowedFrom($root.user.getId())" :key="userBook.getId()">
                        <td>{{ book.getName() }}</td>
                        <td>{{ userBook.borrowedTime }}</td>
                        <td>{{ userBook.name }}</td>
                        <td>
                            <el-button
                                size="small"
                                icon="check"
                                @click="returned(book.getId(), userBook.userId)"/>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>

<script>
import { Button } from 'element-ui';
import Book from '../models/book';
import Http from '../mixins/Http';

export default {
    name: 'LendingList',
    components: { 'el-button': Button },
    mixins: [ Http ],
    data () {
        return {
            borrowed: [],
            borrowing: [],
            requested: [],
            requesting: [],
        };
    },
    created () {
        this.loadBooks();
    },
    methods: {
        cancel (bookId) {
            this.save('lending/cancel', { bookId: bookId }).then(() => {
                this.loadBooks();
            });
        },

        delivered (bookId) {
            this.save('lending/delivered', { bookId: bookId }).then(() => {
                this.loadBooks();
            });
        },

        loadBooks () {
            this.load('lending/get', {}).then((response) => {
                this.borrowed = response.body.borrowed.map(x => new Book(x));
                this.borrowing = response.body.borrowing.map(x => new Book(x));
                this.requested = response.body.requested.map(x => new Book(x));
                this.requesting = response.body.requesting.map(x => new Book(x));
            });
        },

        reject (bookId, userId) {
            this.save('lending/reject', { bookId: bookId, userId: userId }).then(() => {
                this.loadBooks();
            });
        },

        returned (bookId, userId) {
            this.save('lending/returned', { bookId: bookId, userId: userId }).then(() => {
                this.loadBooks();
            });
        },
    },
};
</script>