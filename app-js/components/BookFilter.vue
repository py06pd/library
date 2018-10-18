<template>
    <div id="book-filter">
        <div>
            <el-input id="book-search" :placeholder="'search titles and authors (case-sensitive)'" v-model="searchText"></el-input>
            <el-button type="primary" @click="search">Search</el-button>
        </div>
        <div>
            <el-select
                v-model="newFilter.field"
                placeholder="Please select a field"
                @change="filterFieldChange">
                <el-option :label="'author'" :value="'author'"></el-option>
                <el-option :label="'genre'" :value="'genre'"></el-option>
                <el-option :label="'owner'" :value="'owner'"></el-option>
                <el-option :label="'read'" :value="'read'"></el-option>
                <el-option :label="'series'" :value="'series'"></el-option>
                <el-option :label="'type'" :value="'type'"></el-option>
            </el-select>

            <el-select
                v-model="newFilter.operator"
                placeholder="Please select a operator">
                <el-option :label="'equals'" :value="'equals'"></el-option>
                <el-option :label="'does not equal'" :value="'does not equal'"></el-option>
            </el-select>

            <el-select
                v-model="newFilter.value"
                filterable
                placeholder="Please select a value">
                <el-option v-for="(label, value) in values" :label="label" :value="value"></el-option>
            </el-select>

            <el-button type="primary" icon="plus" @click="addFilter">Add Filter</el-button>
        </div>
        <div id="filter-tags">
            <el-tag
                v-for="(filter, filterIndex) in filters"
                :closable="true"
                type="primary"
                :close-transition="false"
                @close="removeFilter(filterIndex)">
                <span>{{ filter.field }}</span>
                <template v-if="filter.label.length > 1">
                    <span v-if="filter.operator === 'equals'"> either </span>
                    <span v-else> neither </span>
                    <span v-if="filter.operator === 'equals'">
                        '{{ filter.label.join("' or '") }}'
                    </span>
                    <span v-else>
                        '{{ filter.label.join("' nor '") }}'
                    </span>
                </template>
                <template v-else>
                    <span>{{ filter.operator }}</span>
                    <span>'{{ filter.label[0] }}'</span>
                </template>
            </el-tag>
        </div>
    </div>
</template>

<script>
    import { Button, Input, Option, Select, Tag } from 'element-ui';

    let Http = require('../mixins/Http');

    export default {
        name: 'book-filter',
        mixins: [ Http ],
        components: {
            'el-button': Button,
            'el-input': Input,
            'el-option': Option,
            'el-select': Select,
            'el-tag': Tag,
        },
        data: function () {
            return {
                authors: [],
                genres: [],
                series: [],
                types: [],
                values: [],
                newFilter: { field: '', operator: '', value: '' },
                filters: [],
                searchText: '',
            };
        },

        created: function () {
            this.loadQueryFilters();
        },

        methods: {
            addFilter () {
                let alert = '';

                if (this.newFilter.field === '') {
                    alert = 'Please choose field for filter';
                } else if (this.newFilter.operator === '') {
                    alert = 'Please choose operator for filter';
                } else if (this.newFilter.value === '') {
                    alert = 'Please choose value for filter';
                }

                if (alert !== '') {
                    this.$notify({ title: 'Warning', message: alert, type: 'warning' });
                } else {
                    this.processFilter(this.newFilter);
                    this.$emit('input', this.filters);
                }
            },

            filterFieldChange (val) {
                this.values = {};
                this.newFilter.value = '';
                let groupUsers = this.$root.user.getGroupUsers();

                switch (val) {
                    case 'author':
                        if (!this.authors.length) {
                            this.load('books/filters', { field: val }).then(function(response) {
                                this.authors = response.body.data;
                                this.filterFieldChange(val);
                            });
                        } else {
                            for (let a in this.authors) {
                                this.values['a' + this.authors[a].authorId] = this.authors[a].forename + ' ' + this.authors[a].surname;
                            }
                        }
                        break;
                    case 'genre':
                        if (!this.genres.length) {
                            this.load('books/filters', { field: val }).then(function(response) {
                                this.genres = response.body.data;
                                this.filterFieldChange(val);
                            });
                        } else {
                            for (let g in this.genres) {
                                this.values[this.genres[g]] = this.genres[g];
                            }
                        }
                        break;
                    case 'owner':
                    case 'read':
                        if (groupUsers.length) {
                            for (let u in groupUsers) {
                                let user = groupUsers[u];
                                this.values['a' + user.userId] = user.name;
                            }
                        } else {
                            this.values['a' + this.$root.user.getId()] = this.$root.user.getName();
                        }
                        break;
                    case 'series':
                        if (!this.series.length) {
                            this.load('books/filters', { field: val }).then(function(response) {
                                this.series = response.body.data;
                                this.filterFieldChange(val);
                            });
                        } else {
                            for (let s in this.series) {
                                this.values['a' + this.series[s].seriesId] = this.series[s].name;
                            }
                        }
                        break;
                    case 'type':
                        if (!this.types.length) {
                            this.load('books/filters', { field: val }).then(function(response) {
                                this.types = response.body.data;
                                this.filterFieldChange(val);
                            });
                        } else {
                            for (let t in this.types) {
                                this.values[this.types[t]] = this.types[t];
                            }
                        }
                        break;
                }
            },

            loadQueryFilters () {
                if (!this.$root.query) {
                    return;
                }

                for (let i in this.$root.query) {
                    let newFilter = { field: '', operator: 'equals', value: [], label: [] };
                    newFilter.field = i;
                    newFilter.value = this.$root.query[i];
                    if (i.substring(i.length - 1) === '!') {
                        newFilter.operator = 'does not equal';
                        newFilter.field = i.substring(0, i.length - 1);
                    }

                    if (['author', 'owner', 'read', 'series'].indexOf(newFilter.field) !== -1) {
                        this.filterFieldChange(newFilter.field);
                        if (isNaN(newFilter.value)) {
                            let itemKey = Object.values(this.values).findIndex(x => x.toLowerCase() === newFilter.value.toLowerCase());
                            if (itemKey) {
                                newFilter.value = Object.keys(this.values)[itemKey];
                            }
                        } else {
                            newFilter.value = 'a' + newFilter.value;
                        }
                    }

                    this.processFilter(newFilter);
                }

                this.$emit('input', this.filters);
                this.$root.query = null;
            },

            processFilter (filter) {
                let newFilter = true;
                let newFilterValue = filter.value;

                if (['author', 'owner', 'read', 'series'].indexOf(filter.field) !== -1) {
                    newFilterValue = filter.value.substring(1);
                }

                for (let f = 0; f < this.filters.length; f++) {
                    if (this.filters[f].field === filter.field &&
                        this.filters[f].operator === filter.operator
                    ) {
                        this.filters[f].value.push(newFilterValue);
                        this.filters[f].label.push(this.values[filter.value]);
                        newFilter = false;
                    }
                }

                if (newFilter) {
                    this.filters.push({
                        field: filter.field,
                        operator: filter.operator,
                        value: [newFilterValue],
                        label: [this.values[filter.value]],
                    });
                }
            },

            removeFilter (filterIndex) {
                this.filters.splice(filterIndex, 1);
                this.$emit('input', this.filters);
            },

            search () {
                if (this.searchText.trim() !== '') {
                    this.filters.push({
                        field: 'title or author',
                        operator: 'like',
                        value: [this.searchText],
                        label: [this.searchText],
                    });
                    this.searchText = '';
                    this.$emit('input', this.filters);
                }
            },
        },
    };
</script>

<style scoped>
    #book-filter {
        display: inline-block;
        margin-right: 0px;
        margin-left: auto;
    }
    #book-filter > div { margin-bottom: 10px; }
    #filter-tags {
        margin-top: 10px;
    }
</style>