import Vue from 'vue';
import Router from 'vue-router';

Vue.use(Router);

import AuthorBooks from '../components/AuthorBooks.vue';
import AuthorList from '../components/AuthorList.vue';
import BookList from '../components/BookList.vue';
import LendingList from '../components/LendingList.vue';
import MyAccount from '../components/MyAccount.vue';
import SeriesBooks from '../components/SeriesBooks.vue';
import SeriesList from '../components/SeriesList.vue';
import UserList from '../components/UserList';
import Wishlist from '../components/Wishlist.vue';

export default new Router({
    routes: [
        {
            path: '/',
            component: BookList,
        },
        {
            path: '/authors',
            component: AuthorList,
        },
        {
            path: '/author/:id',
            component: AuthorBooks,
            props: (route) => ({authorId: parseInt(route.params.id), showSeries: true }),
        },
        {
            path: '/lending',
            component: LendingList,
        },
        {
            path: '/me',
            component: MyAccount,
        },
        {
            path: '/series',
            component: SeriesList,
        },
        {
            path: '/series/:id',
            component: SeriesBooks,
            props: (route) => ({seriesId: parseInt(route.params.id)}),
        },
        {
            path: '/users',
            component: UserList,
        },
        {
            path: '/wishlist/:id',
            component: Wishlist,
            props: (route) => ({userId: parseInt(route.params.id)}),
        },
    ],
});
