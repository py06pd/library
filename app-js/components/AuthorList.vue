<template>
    <div>
        <div class="author-tracked">
            <div v-for="authorId in authorIds" @click="select(authorId)" :class="{ 'selected':(selected === authorId) }">
                <author-books :authorId="authorId" :showSeries="false"></author-books>
            </div>
        </div>
        <div class="author-selected" v-if="selected > 0">
            <author-books :authorId="selected" :showSeries="true"></author-books>
        </div>
    </div>
</template>

<script>
    import AuthorBooks from './AuthorBooks.vue';
    let Http = require('../mixins/Http');

    export default {
        name: 'author-list',
        mixins: [ Http ],
        components: { AuthorBooks },
        data: function () {
            return {
                authorIds: [],
                selected: 0,
            };
        },
        created: function () {
            this.loadAuthors();
        },
        methods: {
            loadAuthors: function() {
                this.load('authors/tracked', {}).then(function(response) {
                    this.authorIds = response.body.authorIds;
                });
            },

            select: function(authorId) {
                this.selected = authorId;
            },
        },
    };
</script>