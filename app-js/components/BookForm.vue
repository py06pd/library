<template>
    <div>
        <el-dialog
            id="frmBook"
            :before-close="close"
            :top="dialogYOffset"
            :visible="formOpen"
            class="cic-dialog"
            title="Edit Book"
            @open="loadBook">
            <el-form>
                <el-form-item label="Title">
                    <el-input v-model="book.name" class="name"/>
                </el-form-item>
                <el-form-item label="Type">
                    <el-select
                        allow-create
                        filterable
                        :value="book.getTypeValue()"
                        placeholder="Please select a type"
                        @input="typeChange">
                        <el-option
                            v-for="type in types"
                            :key="type.getId()"
                            :label="type.getName()"
                            :value="type.getId()"/>
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
                        <el-option
                            v-for="author in authors"
                            :key="author.getId()"
                            :label="author.getName()"
                            :value="author.getId()"/>
                    </el-select>
                </el-form-item>
                <el-form-item label="Genres">
                    <el-select
                        allow-create
                        filterable
                        multiple
                        :value="book.getGenreValues()"
                        placeholder="Please select a genre"
                        @input="genreChange">
                        <el-option
                            v-for="genre in genres"
                            :key="genre.getId()"
                            :label="genre.getName()"
                            :value="genre.getId()"/>
                    </el-select>
                </el-form-item>
                <el-form-item label="Series">
                    <ul id="selSeries">
                        <li v-for="(current, seriesIndex) in book.getSeries()" :key="seriesIndex">
                            <el-select
                                :value="current.getValue()"
                                allow-create
                                clearable
                                filterable
                                placeholder="Please select a series"
                                @input="val => seriesChange(seriesIndex, val)">
                                <el-option
                                    v-for="sitem in series"
                                    :key="sitem.getId()"
                                    :label="sitem.getName()"
                                    :value="sitem.getId()"/>
                            </el-select>
                            <el-input v-model="current.number" class="series-number"/>
                        </li>
                        <li>
                            <el-select
                                :value="newSeries"
                                allow-create
                                clearable
                                filterable
                                placeholder="Please select a series"
                                @input="val => seriesChange(-1, val)">
                                <el-option
                                    v-for="sitem in series"
                                    :key="sitem.getId()"
                                    :label="sitem.getName()"
                                    :value="sitem.getId()"/>
                            </el-select>
                        </li>
                    </ul>
                </el-form-item>
            </el-form>
            <span slot="footer" class="dialog-footer">
                <el-button type="primary" @click="saveItem(true)">Save and Close</el-button>
                <el-button
                    v-if="book.getId() === -1"
                    type="primary"
                    @click="saveItem(false)">
                    Save
                </el-button>
                <el-button @click="close">Cancel</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
import { Button, Dialog, Form, FormItem, Input, Option, Select } from 'element-ui';
import Author from '../models/author';
import Book from '../models/book';
import Genre from '../models/genre';
import Series from '../models/series';
import Type from '../models/type';
import Http from '../mixins/Http';

export default {
    name: 'BookForm',
    components: {
        'el-button': Button,
        'el-dialog': Dialog,
        'el-form': Form,
        'el-form-item': FormItem,
        'el-input': Input,
        'el-option': Option,
        'el-select': Select,
    },
    mixins: [ Http ],
    props: {
        id: {
            type: Number,
            required: true,
        },
        formOpen: {
            type: Boolean,
            default: false,
        },
    },
    data () {
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
        authorChange (val) {
            let authors = [];
            val.forEach(x => {
                if (isNaN(x)) {
                    authors.push(new Author({ forename: x }));
                } else {
                    let author = this.authors.find(y => y.getId() === x);
                    if (author) {
                        authors.push(author);
                    }
                }
            });

            this.book.setAuthors(authors);
        },

        close () {
            this.$emit('change');
        },

        genreChange (val) {
            let genres = [];
            val.forEach(x => {
                if (isNaN(x)) {
                    genres.push(new Genre({ name: x }));
                } else {
                    let genre = this.genres.find(y => y.getId() === x);
                    if (genre) {
                        genres.push(genre);
                    }
                }
            });

            this.book.setGenres(genres);
        },

        typeChange (val) {
            if (isNaN(val)) {
                this.book.setType(new Type({ name: val }));
            } else {
                let type = this.types.find(x => x.getId() === val);
                if (type) {
                    this.book.setType(type);
                }
            }
        },

        loadBook () {
            this.load('book/get', { bookId: this.id }).then((response) => {
                this.book = new Book(response.body.data);
                this.authors = response.body.authors.map(x => new Author(x));
                this.genres = response.body.genres.map(x => new Genre(x));
                this.series = response.body.series.map(x => new Series(x));
                this.types = response.body.types.map(x => new Type(x));
            });
        },

        saveItem (close) {
            let data = JSON.stringify(this.book.serialise());
            this.save('book/save', { data: data }).then((response) => {
                if (response.body.status === 'OK') {
                    response.body.newAuthors.forEach(x => {
                        let a = new Author(x);
                        this.authors.push(a);

                        let author = this.book.authors.find(y => y.getName() === a.getName());
                        if (author) {
                            author.authorId = a.getId();
                        }
                    });

                    response.body.newGenres.forEach(x => {
                        let a = new Genre(x);
                        this.genres.push(a);

                        let genre = this.book.genres.find(y => y.getName() === a.getName());
                        if (genre) {
                            genre.genreId = a.getId();
                        }
                    });

                    response.body.newSeries.forEach(x => {
                        this.series.push(x);

                        let series = this.book.series.find(y => y.getName() === x.getName());
                        if (series) {
                            series.seriesId = x.getId();
                        }
                    });

                    response.body.newTypes.forEach(x => {
                        let a = new Type(x);
                        this.types.push(a);

                        if (this.book.type.getName() === a.getName()) {
                            this.book.type.typeId = a.getId();
                        }
                    });

                    if (close) {
                        this.close();
                    }
                }
            });
        },

        seriesChange (index, val) {
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