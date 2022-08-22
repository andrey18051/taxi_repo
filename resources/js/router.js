import vueRouter from 'vue-router';
import Vue from 'vue';

Vue.use(vueRouter);

import Index from './views/Index';
import Users from './views/user/UserHome';
import UserEdit from './views/user/UserEdit';
import TaxiAccount from './views/taxi/account';

const  routes = [
    {
        path: "/admin",
        component: Index
    },
    {
        path: "/admin/users",
        component: Users
    },
    {
        name: 'UserEdit',
        path: "/admin/users/edit/:id",
        component: UserEdit

    },
    {
        name: 'TaxiAccount',
        path: "/taxi/account",
        component: TaxiAccount

    },
    {
        name: 'editOrder',
        path: "/costhistory/orders/edit/:id"
    },
    {
        name: 'destroyOrder',
        path: "/costhistory/orders/destroy/:id/:authorization"
    }
    ];

    export default new vueRouter({
        mode: "history",
        routes
    })
