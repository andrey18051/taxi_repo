import vueRouter from 'vue-router';
import Vue from 'vue';

Vue.use(vueRouter);

import Index from './views/Index';
import UserBlackListSet from './views/user/UserBlackListSet';
import Users from './views/user/UserHome';
import Services from './views/service/ServiceHome';
import UserEdit from './views/user/UserEdit';
import TaxiAccount from './views/taxi/account';
import CityHome from "./views/city/CityHome";
import CityHomePas1 from "./views/city/CityHome_PAS_1";
import CityHomePas2 from "./views/city/CityHome_PAS_2";
import CityHomePas4 from "./views/city/CityHome_PAS_4";
import CloseReasonHome from "./views/close_reason/CloseReasonHome";
import BonusHome from "./views/bonuses/BonusHome";
import FondyHome from "./views/fondy/FondyHome";
import UserMessages from "./views/user/UserMessages";
import NewMessage from "./views/user/NewMessage";
import UserMessagesEmail from "./views/user/UserMessagesEmail";
import NewMessageEmail from "./views/user/NewMessageEmail";
import Partners from './views/partners/PartnerHome';
import PartnerEmail from "./views/partners/PartnerEmail";
import NewPartnerEmail from "./views/partners/NewPartnerEmail";
import PartnerGroup from "./views/partners/PartnerGroup";
import WfpHome from "./views/wfp/WfpHome";
import CityTariffs from "./views/city/CityTariffs";

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
        path: "/admin/users/UserBlackListSet",
        component: UserBlackListSet
    },
    {
        path: "/admin/new_balance",
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
        path: "/admin/user_messages_email",
        component: UserMessagesEmail
    },
    {
        path: "/admin/new_message_email",
        component: NewMessageEmail
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
        path: "/admin/city-pas1",
        component: CityHomePas1
    },
    {
        path: "/admin/city-pas2",
        component: CityHomePas2
    },
    {
        path: "/admin/city-pas4",
        component: CityHomePas4
    },
    {
        path: "/admin/city-pas5",
        component: CityHomePas5
    },

    {
        path: "/admin/fondy",
        component: FondyHome
    },
    {
        path: "/admin/wfp",
        component: WfpHome
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
    },

    {
        path: "/admin/partners",
        component: Partners
    },

    {
        path: "/admin/partner_email",
        component: PartnerEmail
    },
    {
        path: "/admin/new-partner-email",
        component: NewPartnerEmail
    },
    {
        path: "/admin/partner_group",
        component: PartnerGroup
    },
    {
        path: "/admin/city-tariffs",
        component: CityTariffs
    },
    ];

    export default new vueRouter({
        mode: "history",
        routes
    })
