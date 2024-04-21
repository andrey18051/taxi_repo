/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

import Vue from "vue";

require('./bootstrap');

window.Vue = require('vue').default;

import Autocomplete from 'v-autocomplete'

// You need a specific loader for CSS files like https://github.com/webpack/css-loader
import 'v-autocomplete/dist/v-autocomplete.css'

Vue.use(Autocomplete)

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
Vue.component('city-component', require('./views/city/CityHome.vue').default);
Vue.component('city-pas1-component', require('./views/city/CityHome_PAS_1.vue').default);
Vue.component('city-pas2-component', require('./views/city/CityHome_PAS_2.vue').default);
Vue.component('city-pas4-component', require('./views/city/CityHome_PAS_4.vue').default);
Vue.component('closeReason-component', require('./views/close_reason/CloseReasonHome.vue').default);
Vue.component('bonuses-component', require('./views/bonuses/BonusHome.vue').default);
Vue.component('fondy-component', require('./views/fondy/FondyHome.vue').default);
Vue.component('user-messages', require('./views/user/UserMessages').default);
Vue.component('new-message', require('./views/user/NewMessage').default);
Vue.component('user-messages-email', require('./views/user/UserMessagesEmail').default);
Vue.component('new-message-email', require('./views/user/NewMessageEmail').default);
Vue.component('partner-email', require('./views/partners/PartnerEmail').default);
Vue.component('new-partner-email', require('./views/partners/NewPartnerEmail').default);
Vue.component('partner-group', require('./views/partners/PartnerGroup').default);

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
