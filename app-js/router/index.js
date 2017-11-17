import Vue from 'vue';
import Router from 'vue-router';
import BookList from '../components/BookList';
import EditSeries from '../components/EditSeries';
import Series from '../components/Series';

Vue.use(Router);

export default new Router({
    routes: [
        {
            path: '/',
            component: BookList,
        },
        {
            path: '/series',
            component: Series,
        },
        {
            path: '/series/:id',
            component: EditSeries,
            props: true,
        },
    ],
});
