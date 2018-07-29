// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue';
import VueResource from 'vue-resource';

// Import the router config, this will load the file ./router/index.js
import router from './router';

import LogsList from './components/LogsList';
import AppMenu from './components/Menu';

import {
    Badge,
    Button,
    Dialog,
    Form,
    FormItem,
    Input,
    Menu,
    MenuItem,
    MessageBox,
    Notification,
    Option,
    Progress,
    Select,
    Table,
    TableColumn,
    Tag,
} from 'element-ui';
import 'element-ui/lib/theme-default/index.css';
import lang from 'element-ui/lib/locale/lang/en';
import locale from 'element-ui/lib/locale';

Vue.use(VueResource);
//Vue.use(new Navigator(), { 'router' : router, 'messageBox' : MessageBox });

Vue.component(LogsList.name, LogsList);
Vue.component(AppMenu.name, AppMenu);

// Register the Element UI components we're using
Vue.use(Badge);
Vue.use(Button);
Vue.use(Dialog);
Vue.use(Form);
Vue.use(FormItem);
Vue.use(Input);
Vue.use(Menu);
Vue.use(MenuItem);
Vue.use(Option);
Vue.use(Progress);
Vue.use(Select);
Vue.use(Table);
Vue.use(TableColumn);
Vue.use(Tag);/*
Vue.use(DatePicker);
Vue.use(Progress);*/
Vue.prototype.$notify = Notification;
Vue.prototype.$alert = MessageBox.alert;
//Vue.prototype.$confirm = MessageBox.confirm;

// Set the Element UI locale
locale.use(lang);

// Start the application
new Vue({
    el: '#app',
    router,
    data: function () {
        return {
            params: {},
            requests: 0,
            user: { id: 0, name: '', role: 'anon' },
            users: {},
        };
    },
    created: function() {
        var data = JSON.parse(document.getElementById('data').getAttribute('data'));
        this.user = (data.user && data.user.id > 0) ? data.user : { id: 0, name: '', role: 'anon' };
        this.users = data.users;
    },
});
