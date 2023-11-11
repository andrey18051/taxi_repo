<template>

    <div class="container">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">

                <h1 class="display-5">Fondy</h1>

        </div>
        <div style="width: 1200px; overflow-x: auto;">
            <v-table
            :data="fondy_data"
            :filters="filters"
            :hideSortIcons="true"
            class="my-2 table table-striped"
            :currentPage.sync="currentPage"
            :pageSize="5"
            @totalPagesChanged="totalPages = $event"
        >
            <thead slot="head">
            <v-th sortKey="id"  style="width: 60px">id</v-th>
            <v-th sortKey="first"  style="width: 185px">дата</v-th>
            <v-th sortKey="cost"  style="width: 60px">грн</v-th>
            <v-th sortKey="fondy_order_id"  style="width: 250px">номер платежа</v-th>
            <v-th sortKey="fondy_status_pay"  style="width: 100px">статус</v-th>
<!--            <v-th ></v-th>-->
            <v-th sortKey="reason"  style="width: 180px">close_reason</v-th>
            <v-th sortKey="uid"  style="width: 300px">uid</v-th>
            </thead>
            <tbody slot="body" slot-scope="{displayData}">
            <tr>

                <td ><input class="form-input input-sm"  style="width: 60px" v-model="filters.id.value"></td>


                <td><input class="form-input input-sm" style="width: 185px" v-model="filters.first.value"></td>
                <td><input class="form-input input-lg" style="width: 60px" v-model="filters.cost.value"></td>
                <td> <input class="form-input input-lg"  style="width: 250px" v-model="filters.fondy_order_id.value"></td>
                <td> <input class="form-input input-lg" style="width: 180px" v-model="filters.fondy_status_pay.value"></td>
<!--                <td></td>-->
                <td> <input class="form-input input-lg"  style="width: 180px" v-model="filters.reason.value"></td>
                <td> <input class="form-input input-lg" style="width: 300px" v-model="filters.uid.value"></td>
            </tr>
            <tr v-for="row in displayData" :key="row.id">
                 <td style="width: 60px">{{ row.id }}</td>
                <td> <input id="first" class="form-control" style="width: 185px" v-model.text="row.first" required ></td>
                <td><input id="cost" class="form-control" style="width: 60px" v-model.text="row.cost" required ></td>
                <td> <input id="fondy_order_id" class="form-control" style="width: 250px" v-model.text="row.fondy_order_id" required ></td>
                <td><input id="fondy_status_pay" class="form-control" style="width: 180px" v-model.text="row.fondy_status_pay" required ></td>
<!--                <td>-->
<!--                    <div class="btn-group" role="group">-->
<!--                        <button class="btn btn-success" @click="saveFondy_data(row.fondy_order_id)" style="margin-left: 5px">-->
<!--                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save2" viewBox="0 0 16 16">-->
<!--                                <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v4.5h2a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 5.5 6.5h2V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>-->
<!--                            </svg>-->
<!--                        </button>-->

<!--                    </div>-->
<!--                </td>-->

                <td><input id="reason" class="form-control" style="width: 180px" v-model.text="row.reason" required ></td>
                <td><input id="uid" class="form-control" style="width: 300px" v-model.text="row.uid" required ></td>



            </tr>
            </tbody>
        </v-table>
        </div>

        <smart-pagination
            :currentPage.sync="currentPage"
            :totalPages="totalPages"
            :maxPageLinks="maxPageLinks"
        />
    </div>

</template>

<script>
import axios from 'axios';
export default {
    name: "FondyHome",
    data: () => ({
        loading: true,
        fondy_data: [],
        currentPage: 1,
        totalPages: 0,
        maxPageLinks: 25,
        filters: {
            id: { value: "", keys: ["id"] },
            first: { value: "", keys: ["first"] },
            cost: { value: "", keys: ["cost"] },
            fondy_order_id: { value: "", keys: ["fondy_order_id"] },
            fondy_status_pay: { value: "", keys: ["fondy_status_pay"] },
            uid: { value: "", keys: ["uid"] },
            reason: { value: "", keys: ["reason"] },
        }
    }),
    mounted() {
        this.getFondy_data()
    },
    methods: {
        getFondy_data() {
            axios.get('/fondyData/all')
                .then(
                    res => {
                        this.fondy_data = res.data;
                        this.loading = false;
                    }
                )
        },


        saveFondy_data(fondy_order_uid) {
            axios.get('/fondyStatusReviewAdmin/'+ fondy_order_uid)
                .then(function(ret) {
                    console.log(ret.data);
                    document.location.reload();
                    window.alert("Данные обновлены");
                })
        }

    }
}
</script>

<style scoped>

</style>
