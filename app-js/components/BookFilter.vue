<template>
    <div id="book-filter">
        <div>
            <el-button type="primary" icon="plus" @click="addFilter">Add Filter</el-button>

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
    import { Button, Option, Select, Tag } from 'element-ui';

    let Http = require('../mixins/Http');

    export default {
        name: 'book-filter',
        mixins: [ Http ],
        components: {
            'el-button': Button,
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
            };
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
                    let newFilter = true;
                    let newFilterValue = this.newFilter.value;
                    if (this.newFilter.field === 'author' || this.newFilter.field === 'series') {
                        newFilterValue = this.newFilter.value.substring(1);
                    }

                    for (let f = 0; f < this.filters.length; f++) {
                        if (this.filters[f].field === this.newFilter.field &&
                            this.filters[f].operator === this.newFilter.operator
                        ) {
                            this.filters[f].value.push(newFilterValue);
                            this.filters[f].label.push(this.values[this.newFilter.value]);
                            newFilter = false;
                        }
                    }
                    if (newFilter) {
                        this.filters.push({
                            field: this.newFilter.field,
                            operator: this.newFilter.operator,
                            value: [newFilterValue],
                            label: [this.values[this.newFilter.value]],
                        });
                    }
                    this.$emit('change', this.filters);
                }
            },

            filterFieldChange (val) {
                this.values = {};
                this.newFilter.value = '';

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
                        if (this.$root.user.groupUsers.length) {
                            for (let u in this.$root.user.groupUsers) {
                                let user = this.$root.user.groupUsers[u];
                                this.values[user.userId] = user.name;
                            }
                        } else {
                            this.values[this.$root.user.userId] = this.$root.user.name;
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
                                this.values['s' + this.series[s].seriesId] = this.series[s].name;
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

            removeFilter (filterIndex) {
                this.filters.splice(filterIndex, 1);
                this.$emit('change', this.filters);
            },
        },
    };
</script>

<style scoped>
    #book-filter {
        display: inline-block;
        margin-left: 10px;
    }
    #filter-tags {
        margin-top: 10px;
    }
</style>