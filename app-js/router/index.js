import Vue from 'vue';
import Router from 'vue-router';

Vue.use(Router);

import Authors from '../components/Authors';
import BookList from '../components/BookList.vue';
import EditAuthor from '../components/EditAuthor';
import EditSeries from '../components/EditSeries';
import LendingList from '../components/LendingList';
import MyAccount from '../components/MyAccount.vue';
import Series from '../components/Series';
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
            component: Authors,
        },
        {
            path: '/author/:id',
            component: EditAuthor,
            props: (route) => ({id: parseInt(route.params.id), showSeries: true }),
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
            component: Series,
        },
        {
            path: '/series/:id',
            component: EditSeries,
            props: (route) => ({id: parseInt(route.params.id)}),
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
