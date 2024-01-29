import vueRouter from 'vue-router';
import Vue from 'vue';

Vue.use(vueRouter);

import Index from './views/Index';
import Users from './views/user/UserHome';
import Services from './views/service/ServiceHome';
import UserEdit from './views/user/UserEdit';
import TaxiAccount from './views/taxi/account';
import CityHome from "./views/city/CityHome";
import CloseReasonHome from "./views/close_reason/CloseReasonHome";
import BonusHome from "./views/bonuses/BonusHome";
import FondyHome from "./views/fondy/FondyHome";
import UserMessages from "./views/user/UserMessages";
import NewMessage from "./views/user/NewMessage";

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
        path: "/admin/user_messages",
        component: UserMessages
    },
    {
        path: "/admin/new_message",
        component: NewMessage
    },
    {
        path: "/admin/services",
        component: Services
    },
    {
        path: "/admin/city",
        component: CityHome
    },
    {
        path: "/admin/fondy",
        component: FondyHome
    },
    {
        path: "/admin/bonus",
        component: BonusHome
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
        name: 'closeReason',
        path: "/admin/closeReason",
        component: CloseReasonHome
    },
    {
        name: 'destroyOrder',
        path: "/costhistory/orders/destroy/:id/:authorization"
    },
    {
        name: 'breakingNews',
        path: "/breakingNews/:id/"
    }
    ];

    export default new vueRouter({
        mode: "history",
        routes
    })
