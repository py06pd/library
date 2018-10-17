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
                <el-option v-for="value in values" :label="value.label" :value="value"></el-option>
            </el-select>
        </div>
        <div style="margin-bottom:10px">
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

    export default {
        name: 'book-filter',
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
                newFilter: { field: '', operator: '', value: {} },
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
                    for (let f = 0; f < this.filters.length; f++) {
                        if (this.filters[f].field === this.newFilter.field &&
                            this.filters[f].operator === this.newFilter.operator
                        ) {
                            this.filters[f].value.push(this.newFilter.value.value);
                            this.filters[f].label.push(this.newFilter.value.label);
                            newFilter = false;
                        }
                    }
                    if (newFilter) {
                        this.filters.push({
                            field: this.newFilter.field,
                            operator: this.newFilter.operator,
                            value: [this.newFilter.value.value],
                            label: [this.newFilter.value.label],
                        });
                    }

                    this.$emit('change', this.filters);
                }
            },

            filterFieldChange (val) {
                this.values = [];

                switch (val) {
                    case 'author':
                        if (!this.authors.length) {
                            this.load('books/filters', { field: val }).then(function(response) {
                                this.authors = response.body.data;
                                this.filterFieldChange(val);
                            });
                        } else {
                            for (let a in this.authors) {
                                this.values.push({
                                    value: this.authors[a].id,
                                    label: this.authors[a].forename + ' ' + this.authors[a].surname,
                                });
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
                                this.values.push({
                                    value: this.genres[g],
                                    label: this.genres[g],
                                });
                            }
                        }
                        break;
                    case 'owner':
                    case 'read':
                        for (let u in this.$root.user.groupUsers) {
                            this.values.push({
                                value: this.$root.user.groupUsers[u].userId,
                                label: this.$root.user.groupUsers[u].name,
                            });
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
                                this.values.push({
                                    value: this.series[s].id,
                                    label: this.series[s].name,
                                });
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
                                this.values.push({
                                    value: this.types[t],
                                    label: this.types[t],
                                });
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
</style>