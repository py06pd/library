<template>
    <div>
        <el-dialog
            id="bookMenu"
            :visible="mode === 1"
            :before-close="close"
            :class="{ 'not-for-mobile': mode === 2 }"
            :title="book.getName()"
            :top="dialogYOffset">
            <ul id="bookInfo" class="for-mobile">
                <li><b>Type</b><span>{{ book.getTypeName() }}</span></li>
                <li>
                    <b>Author</b>
                    <span>
                        <span
                            v-for="a in book.getAuthors()"
                            :key="a.getId()"
                            class="author-link"
                            @click="selectAuthor(a.getId())">
                            {{ a.getName() }}
                        </span>
                    </span>
                </li>
                <li>
                    <b>Genre</b>
                    <span>{{ book.getGenreNames().join(', ') }}</span>
                </li>
                <li>
                    <b>Series</b>
                    <span>
                        <span
                            v-for="s in book.getSeries()"
                            :key="s.getId()"
                            class="series-link"
                            @click="selectSeries(s.getId())">
                            {{ s.getName() }}
                        </span>
                    </span>
                </li>
                <li><b>Owner</b><span>{{ book.getOwnerNames().join(', ') }}</span></li>
                <li><b>Read</b><span>{{ book.getReadByNames().join(', ') }}</span></li>
            </ul>
            <ul>
                <li v-if="$root.user.hasRole('ROLE_ADMIN') || ($root.user.hasRole('ROLE_LIBRARIAN') && book.isOnlyUser($root.user.getId()))">
                    <el-button @click="openEdit">Edit</el-button>
                </li>
                <li v-if="book.isOwnedBy($root.user.getId())">
                    <el-button @click="disownBook">I don't own this</el-button>
                </li>
                <template v-else>
                    <li v-if="book.hasOwner() && book.canRequest($root.user.getId())">
                        <el-button @click="borrowRequest">Borrow Request</el-button>
                    </li>
                    <li><el-button @click="wishlist">Add to Wishlist</el-button></li>
                    <li v-if="!$root.user.hasRole('ROLE_ADMIN')">
                        <el-button @click="ownBook">I own this</el-button>
                    </li>
                </template>
                <li v-if="$root.user.hasRole('ROLE_ADMIN')">
                    <el-select v-model="ownedBy" style="width: 68%">
                        <el-option
                            v-for="user in $root.user.getGroupUsers()"
                            :key="user.userId"
                            :value="user.userId"
                            :label="user.name"/>
                    </el-select>
                    <el-button style="width: 30%" @click="ownBook">own this</el-button>
                </li>
                <li v-if="book.hasBeenReadBy($root.user.getId())">
                    <el-button @click="unreadBook">I haven't read this</el-button>
                </li>
                <li v-else-if="!$root.user.hasRole('ROLE_ADMIN')">
                    <el-button @click="readBook">I've read this</el-button>
                </li>
                <li v-if="$root.user.hasRole('ROLE_ADMIN')">
                    <el-select v-model="readBy" style="width: 68%">
                        <el-option
                            v-for="user in $root.user.getGroupUsers()"
                            :key="user.userId"
                            :value="user.userId"
                            :label="user.name"/>
                    </el-select>
                    <el-button style="width: 30%" @click="readBook">read this</el-button>
                </li>
            </ul>
        </el-dialog>

        <book-form
            :id="book.getId()"
            :form-open="mode === 2"
            @change="close"/>
    </div>
</template>

<script>
import { Button, Dialog, Option, Select } from 'element-ui';
import Book from '../models/book';
import BookForm from './BookForm.vue';
import Http from '../mixins/Http';

export default {
    name: 'BookMenu',
    components: {
        'el-button': Button,
        'el-dialog': Dialog,
        'el-option': Option,
        'el-select': Select,
        BookForm,
    },
    mixins: [ Http ],
    props: {
        book : { type: Object, default: new Book() },
        mode: { type: Number, default: 0 },
    },
    data () {
        return {
            ownedBy: this.$root.user.userId,
            readBy: this.$root.user.userId,
        };
    },
    computed: {
        dialogYOffset () {
            if (window.innerWidth <= 600) {
                return '0';
            }

            return '15%';
        },
    },

    methods: {
        borrowRequest () {
            this.save('lending/request', { bookId: this.book.getId() }).then((response) => {
                if (response.body.status === 'OK') {
                    this.close();
                }
            });
        },

        close () {
            this.$emit('change', 0);
        },

        disownBook () {
            this.save('book/disown', { bookId: this.book.getId() }).then((response) => {
                if (response.body.status === 'OK') {
                    this.close();
                }
            });
        },

        openEdit () {
            this.$emit('change', 2);
        },

        ownBook () {
            this.save('book/own', { bookId: this.book.getId(), userId: this.ownedBy }).then((response) => {
                if (response.body.status === 'OK') {
                    this.close();
                }
            });
        },

        readBook () {
            this.save('book/read', { bookId: this.book.getId(), userId: this.readBy }).then((response) => {
                if (response.body.status === 'OK') {
                    this.close();
                }
            });
        },

        selectAuthor (authorId) {
            this.$router.push('/author/' + authorId);
        },

        selectSeries (seriesId) {
            this.$router.push('/series/' + seriesId);
        },

        unreadBook () {
            this.save('book/unread', { bookId: this.book.getId() }).then((response) => {
                if (response.body.status === 'OK') {
                    this.close();
                }
            });
        },

        wishlist () {
            this.save('book/wish', { bookId: this.book.getId() }).then((response) => {
                if (response.body.status === 'OK') {
                    this.close();
                }
            });
        },
    },
};
</script>
