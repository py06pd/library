// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue';
import VueResource from 'vue-resource';

// Import the router config, this will load the file ./router/index.js
import router from './router';

import BookList from './components/BookList';

import Navigator from './plugins/Navigator';

import {
    Button,
    Dialog,
    Form,
    FormItem,
    Input,
    Notification,
    Option,
    Select,
    Table,
    TableColumn,
} from 'element-ui';
import 'element-ui/lib/theme-default/index.css';
import lang from 'element-ui/lib/locale/lang/en';
import locale from 'element-ui/lib/locale';

Vue.use(VueResource);
//Vue.use(new Navigator(), { 'router' : router, 'messageBox' : MessageBox });

Vue.component(BookList.name, BookList);

// Register the Element UI components we're using
Vue.use(Button);
Vue.use(Dialog);
Vue.use(Form);
Vue.use(FormItem);
Vue.use(Input);
Vue.use(Option);
Vue.use(Select);
Vue.use(Table);
Vue.use(TableColumn);/*
Vue.use(DatePicker);
Vue.use(Progress);*/
Vue.prototype.$notify = Notification;/*
Vue.prototype.$alert = MessageBox.alert;
Vue.prototype.$confirm = MessageBox.confirm;*/

// Set the Element UI locale
locale.use(lang);

// Start the application
new Vue({
    el: '#app',
    router,
});
