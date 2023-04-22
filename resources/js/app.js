/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import Vue from "vue";

require('./bootstrap');

window.Vue = require('vue').default;

/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const reports = require.context('./', true, /\.vue$/i)
// reports.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], reports(key).default))

Vue.component('example-component', require('./components/ExampleComponent.vue').default);
Vue.component('user-component', require('./views/user/UserHome.vue').default);
Vue.component('service-component', require('./views/service/ServiceHome.vue').default);
Vue.component('user-edit', require('./views/user/UserEdit.vue').default);
Vue.component('taxi-account', require('./views/taxi/account.vue').default);
Vue.component('order-component', require('./views/taxi/order.vue').default);
Vue.component('news-component', require('./views/taxi/news.vue').default);
/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

import router from "./router";
import SmartTable from 'vuejs-smart-table';
Vue.use(SmartTable);

const app = new Vue({
    el: '#app',
    router
});
