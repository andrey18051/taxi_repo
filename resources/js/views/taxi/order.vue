<template>
    <div class="px-1 py-1 px-md-5 text-center text-lg-start" style="background-color: hsl(0, 0%, 96%)">
           <div class="container" style="text-align: center; margin-top: 5px">
               <h2><b>Київ та область</b></h2>
               <p class="text-center">Виберіть варіант і уточніть при необхідності параметри замовлення.</p>
           </div>
       <div class="container" style="display: block;
                  overflow: auto;
                  max-width: 100%;">

                <v-table
                :data="orders"
                :filters="filters"
                :hideSortIcons="true"
                class="my-2 table table-striped"
                :currentPage.sync="currentPage"
                :pageSize="5"
                @totalPagesChanged="totalPages = $event"
            >
                    <thead slot="head">
                    <v-th sortKey="flexible_tariff_name">Тариф </v-th>
                    <v-th sortKey="routefrom" >Звідки</v-th>
                    <v-th sortKey="routefromnumber">Будинок</v-th>
                    <v-th sortKey="routeto" >Куди</v-th>
                    <v-th sortKey="routetonumber">Будинок</v-th>
                    </thead>
                <tbody slot="body" slot-scope="{displayData}">
                <tr>
                    <td>
                        <input class="form-input input-sm" v-model="filters.flexible_tariff_name.value" placeholder="Пошук">
                    </td>
                    <td>
                        <input class="form-input input-lg" v-model="filters.routefrom.value" placeholder="Пошук">
                    </td>
                    <td></td>
                    <td>
                        <input class="form-input input-sm" v-model="filters.routeto.value" placeholder="Пошук">
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr v-for="row in displayData" :key="row.id">
                     <td>
                        <div style="width: 180px">
                            <input id="flexible_tariff_name" class="form-control" v-model.text="row.flexible_tariff_name" required >
                        </div>
                     </td>
                     <td>
                        <div style="width: 300px">
                            <input id="routefrom" class="form-control" v-model.text="row.routefrom" required >
                        </div>
                     </td>
                     <td>
                        <div style="width: 65px">
                            <input id="routefromnumber" class="form-control" style="text-align: right" v-model.text="row.routefromnumber" required >
                        </div>
                     </td>
                     <td>
                                            <div style="width: 300px">
                                                <input id="routeto" class="form-control" v-model.text="row.routeto" required >
                                            </div>
                                        </td>
                     <td>
                        <div style="width: 65px">
                            <input id=" routetonumber" class="form-control" style="text-align: right" v-model.text="row.routetonumber" required >
                        </div>
                     </td>
                     <td>
                        <div class="btn-group" role="group">
                            <router-link :to="{name: 'editOrder', params: { id: row.id }}" class="btn btn-success" target="_blank" title="Переглянути">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-binoculars" viewBox="0 0 16 16">
                                    <path d="M3 2.5A1.5 1.5 0 0 1 4.5 1h1A1.5 1.5 0 0 1 7 2.5V5h2V2.5A1.5 1.5 0 0 1 10.5 1h1A1.5 1.5 0 0 1 13 2.5v2.382a.5.5 0 0 0 .276.447l.895.447A1.5 1.5 0 0 1 15 7.118V14.5a1.5 1.5 0 0 1-1.5 1.5h-3A1.5 1.5 0 0 1 9 14.5v-3a.5.5 0 0 1 .146-.354l.854-.853V9.5a.5.5 0 0 0-.5-.5h-3a.5.5 0 0 0-.5.5v.793l.854.853A.5.5 0 0 1 7 11.5v3A1.5 1.5 0 0 1 5.5 16h-3A1.5 1.5 0 0 1 1 14.5V7.118a1.5 1.5 0 0 1 .83-1.342l.894-.447A.5.5 0 0 0 3 4.882V2.5zM4.5 2a.5.5 0 0 0-.5.5V3h2v-.5a.5.5 0 0 0-.5-.5h-1zM6 4H4v.882a1.5 1.5 0 0 1-.83 1.342l-.894.447A.5.5 0 0 0 2 7.118V13h4v-1.293l-.854-.853A.5.5 0 0 1 5 10.5v-1A1.5 1.5 0 0 1 6.5 8h3A1.5 1.5 0 0 1 11 9.5v1a.5.5 0 0 1-.146.354l-.854.853V13h4V7.118a.5.5 0 0 0-.276-.447l-.895-.447A1.5 1.5 0 0 1 12 4.882V4h-2v1.5a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5V4zm4-1h2v-.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5V3zm4 11h-4v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14zm-8 0H2v.5a.5.5 0 0 0 .5.5h3a.5.5 0 0 0 .5-.5V14z"/>
                                </svg>
                            </router-link>
                            <router-link :to="{name: 'destroyOrder', params: { id: row.id, authorization: authorization  }}" class="btn btn-danger" style="margin-left: 5px" target="_blank" title="Видалити">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                    <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                                </svg>
                            </router-link>
                        </div>
                    </td>
                </tr>
                </tbody>
            </v-table>

            <smart-pagination
                :currentPage.sync="currentPage"
                :totalPages="totalPages"
                :maxPageLinks="maxPageLinks"
            />
        </div>
   </div>
</template>

<script>
import axios from 'axios';
export default {
    name: "Order",
    props: {
        user_name: String,
        authorization: String,
    },
    data: () => ({
        loading: true,
        orders: [],
        currentPage: 1,
        totalPages: 0,
        maxPageLinks: 15,
        filters: {
            id: { value: "", keys: ["id"] },
            user_full_name: { value: "", keys: ["user_full_name"] },
            user_phone: { value: "", keys: ["user_phone"] },
            route_address_entrance_from: { value: "", keys: ["route_address_entrance_from"] },
            comment: { value: "", keys: ["comment"] },
            add_cost: { value: "", keys: ["add_cost"] },
            wagon: { value: "", keys: ["wagon"] },
            minibus: { value: "", keys: ["minibus"] },
            premium: { value: "", keys: ["premium"] },
            flexible_tariff_name: { value: "", keys: ["flexible_tariff_name"] },
            routefrom: { value: "", keys: ["routefrom"] },
            routefromnumber: { value: "", keys: ["routefromnumber"] },
            routeto: { value: "", keys: ["routeto"] },
            routetonumber: { value: "", keys: ["routetonumber"] },
            payment_type: { value: "", keys: ["payment_type"] },

}
    }),
    mounted() {
        this.getOrders()
    },
    methods: {
        getOrders() {
            axios.get('/costhistory-orders/' + this.user_name)
                .then(
                    res => {
                        this.orders = res.data;
                        this.loading = false;
                    }
                )
        }
    }
}
</script>

<style scoped>

</style>
