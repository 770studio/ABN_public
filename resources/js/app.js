/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */


require('./bootstrap');

window.Vue = require('vue');


//import Vue from 'vue'
import BootstrapVue from 'bootstrap-vue'
import VueCurrencyInput from 'vue-currency-input'
//var Inputmask = require('inputmask');
import VueMask from 'v-mask'
import 'bootstrap-vue/dist/bootstrap-vue.css'
Vue.config.devtools = true

window.Inputmask = require('inputmask');
//import Inputmask from 'inputmask';
//Vue.use(Inputmask);
Vue.use(BootstrapVue)
Vue.use(VueCurrencyInput, {
    globalOptions: {
        currency: 'RUB',

    }, })


//Vue.use(VueMask);


Vue.use(VueMask, {
    placeholders: {

        X: /[\d\.]/, // cyrillic letter as a placeholder
    }
})


Vue.component('search-contract', require('./components/PaymentSchedule/SearchContract.vue').default);
Vue.component('contract-data', require('./components/PaymentSchedule/ContractData.vue').default);
Vue.component('common-data', require('./components/PaymentSchedule/Common.vue').default);
Vue.component('schedule-data', require('./components/PaymentSchedule/Schedule.vue').default);
Vue.component('schedule-edit-row', require('./components/PaymentSchedule/ScheduleEditRow.vue').default);
Vue.component('penalty-data', require('./components/PaymentSchedule/Penalty.vue').default);
Vue.component('single-file', require('./components/PaymentSchedule/SingleFile.vue').default);
Vue.component('penalty-edit-row', require('./components/PaymentSchedule/PenaltyEditRow.vue').default);
Vue.component('schedule-history', require('./components/PaymentSchedule/ScheduleHistory.vue').default);
Vue.component('rate_history', require('./components/PaymentSchedule/RateHistory.vue').default);
Vue.component('refin-rates', require('./components/PaymentSchedule/RefinRates.vue').default);



//переуступка
Vue.component('assignment-data', require('./components/PaymentSchedule/Assignment.vue').default);
Vue.component('add-assignment', require('./components/PaymentSchedule/AddAssignment.vue').default);


//Vue.component('my-currency-input', require('./components/PaymentSchedule/MyCurrencyInput.vue').default);


/**
 * The following block of code may be used to automatically register your
 * Vue components. It will recursively scan this directory for the Vue
 * components and automatically register them with their "basename".
 *
 * Eg. ./components/ExampleComponent.vue -> <example-component></example-component>
 */

// const files = require.context('./', true, /\.vue$/i);
// files.keys().map(key => Vue.component(key.split('/').pop().split('.')[0], files(key).default));

//Vue.component('example-component', require('./components/ExampleComponent.vue').default);


/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

