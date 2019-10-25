<template>
    <div>
        <div class="author-tracked">
            <div
                v-for="authorId in authorIds"
                :key="authorId"
                :class="{ 'selected':(selected === authorId) }">
                @click="select(authorId)">
                <author-books :author-id="authorId" :show-series="false"/>
            </div>
        </div>
        <div v-if="selected > 0" class="author-selected">
            <author-books :author-id="selected" :show-series="true"/>
        </div>
    </div>
</template>

<script>
import AuthorBooks from './AuthorBooks.vue';
import Http from '../mixins/Http';

export default {
    name: 'AuthorList',
    components: { AuthorBooks },
    mixins: [ Http ],
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