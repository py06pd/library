import Vue from 'vue';
import Router from 'vue-router';

Vue.use(Router);

import Authors from '../components/Authors';
import BookList from '../components/BookList';
import EditAuthor from '../components/EditAuthor';
import EditSeries from '../components/EditSeries';
import Series from '../components/Series';

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
            path: '/series',
            component: Series,
        },
        {
            path: '/series/:id',
            component: EditSeries,
            props: (route) => ({id: parseInt(route.params.id)}),
        },
    ],
});
