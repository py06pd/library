<template>
    <div>
        <el-dialog id="frmBook" class="cic-dialog" title="Edit Book" :top="dialogYOffset" :visible="formOpen" @open="loadBook" :before-close="close">
            <el-form>
                <el-form-item label="Title">
                    <el-input class="name" v-model="book.name"></el-input>
                </el-form-item>
                <el-form-item label="Type">
                    <el-select
                        allow-create
                        filterable
                        v-model="book.type"
                        placeholder="Please select a type">
                        <el-option v-for="type in types" :label="type" :value="type"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="Authors">
                    <el-select
                        allow-create
                        filterable
                        multiple
                        :value="book.getAuthorValues()"
                        placeholder="Please select a author"
                        @input="authorChange">
                        <el-option v-for="author in authors" :label="author.getName()" :value="author.getId()"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="Genres">
                    <el-select
                        allow-create
                        filterable
                        multiple
                        v-model="book.genres"
                        placeholder="Please select a genre">
                        <el-option v-for="genre in genres" :label="genre" :value="genre"></el-option>
                    </el-select>
                </el-form-item>
                <el-form-item label="Series">
                    <ul id="selSeries">
                        <li v-for="(current, seriesIndex) in book.getSeries()">
                            <el-select
                                    allow-create
                                    clearable
                                    filterable
                                    :value="current.getValue()"
                                    placeholder="Please select a series"
                                    @input="function (val) { seriesChange(seriesIndex, val) }">
                                <el-option v-for="sitem in series" :label="sitem.getName()" :value="sitem.getId()"></el-option>
                            </el-select>
                            <el-input class="series-number" v-model="current.number"></el-input>
                        </li>
                        <li>
                            <el-select
                                    allow-create
                                    clearable
                                    filterable
                                    placeholder="Please select a series"
                                    :value="newSeries"
                                    @input="function (val) { seriesChange(-1, val) }">
                                <el-option v-for="sitem in series" :label="sitem.getName()" :value="sitem.getId()"></el-option>
                            </el-select>
                        </li>
                    </ul>
                </el-form-item>
            </el-form>
            <span slot="footer" class="dialog-footer">
                <el-button type="primary" @click="saveItem(true)">Save and Close</el-button>
                <el-button v-if="book.getId() === -1" type="primary" @click="saveItem(false)">Save</el-button>
                <el-button @click="close">Cancel</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
    import { Button, Dialog, Form, FormItem, Input, Option, Select } from 'element-ui';
    import Author from '../models/author';
    import Book from '../models/book';
    import Series from '../models/series';

    let Http = require('../mixins/Http');

    export default {
        name: 'book-form',
        mixins: [ Http ],
        props: ['id', 'formOpen'],
        components: {
            'el-button': Button,
            'el-dialog': Dialog,
            'el-form': Form,
            'el-form-item': FormItem,
            'el-input': Input,
            'el-option': Option,
            'el-select': Select,
        },
        data: function () {
            return {
                book: new Book(),
                newSeries: null,
                authors: [],
                genres: [],
                series: [],
                types: [],
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
            authorChange: function(val) {
                let authors = [];
                for (let i in val) {
                    if (val.hasOwnProperty(i)) {
                        if (isNaN(val[i])) {
                            authors.push(new Author({ forename: val[i] }));
                        } else {
                            let author = this.authors.find(x => x.getId() === val[i]);
                            if (author) {
                                authors.push(author);
                            }
                        }
                    }
                }

                this.book.setAuthors(authors);
            },

            close: function() {
                this.$emit('change');
            },

            loadBook: function() {
                this.load('book/get', { bookId: this.id }).then(function(response) {
                    this.book = new Book(response.body.data);
                    this.authors = response.body.authors.map (x => new Author(x));
                    this.genres = response.body.genres;
                    this.series = response.body.series.map (x => new Series(x));
                    this.types = response.body.types;
                });
            },

            saveItem: function(close) {
                let data = JSON.stringify(this.book.serialise());
                this.save('book/save', { data: data }).then(function(response) {
                    if (response.body.status === 'OK') {
                        for (let i in response.body.newAuthors) {
                            let a = new Author(response.body.newAuthors[i]);
                            this.authors.push(a);

                            for (let j in this.book.authors) {
                                if (this.book.authors[j].toString().trim() === a.getName()) {
                                    this.book.authors[j] = a.getId();
                                    break;
                                }
                            }
                        }

                        for (let i in response.body.newSeries) {
                            let s = response.body.newSeries[i];
                            this.series.push(s);

                            for (let j in this.book.series) {
                                if (this.book.series[j].name === s.name) {
                                    this.book.series[j].id = s.id;
                                    break;
                                }
                            }
                        }

                        if (close) {
                            this.close();
                        }
                    }
                });
            },

            seriesChange: function(index, val) {
                this.newSeries = null;
                if (val === '') {
                    if (this.book.series.length === 1) {
                        this.book.series = [];
                    } else {
                        this.book.series.splice(index, 1);
                    }
                } else {
                    // prevent duplicates
                    if (this.book.series.find(x => x.getValue() === val)) {
                        return;
                    }

                    let series = this.series.find(x => x.getId() === val);
                    if (!series) {
                        series = new Series({name: val});
                    }

                    if (this.book.series.hasOwnProperty(index)) {
                        this.book.series[index] = series;
                    } else {
                        this.book.series.push(series);
                    }
                }
            },
        },
    };
</script>