// The Vue build version to load with the `import` command
// (runtime-only or standalone) has been set in webpack.base.conf with an alias.
import Vue from 'vue';
import VueResource from 'vue-resource';

// Import the router config, this will load the file ./router/index.js
import router from './router';

import LogsList from './components/LogsList';
import AppMenu from './components/MainMenu.vue';
import User from './models/user';

import { MessageBox, Notification } from 'element-ui';
import 'element-ui/lib/theme-default/index.css';
import lang from 'element-ui/lib/locale/lang/en';
import locale from 'element-ui/lib/locale';

Vue.use(VueResource);

Vue.component(LogsList.name, LogsList);
Vue.component(AppMenu.name, AppMenu);

// Register the Element UI components we're using
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
            query: null,
            requests: 0,
            user: {},
        };
    },
    created: function() {
        var data = JSON.parse(document.getElementById('data').getAttribute('data'));
        if (Object.keys(data.query).length > 0) {
            this.query = data.query;
        }
        this.user = new User(data.user);
    },
});
