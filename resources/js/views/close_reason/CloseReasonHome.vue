<template>

    <div class="container">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">

                <h1 class="display-5">CloseReason</h1>

        </div>
        <div style="width: 1200px; overflow-x: auto;">
            <v-table
            :data="close_reason_data"
            :filters="filters"
            :hideSortIcons="true"
            class="my-2 table table-striped"
            :currentPage.sync="currentPage"
            :pageSize="5"
            @totalPagesChanged="totalPages = $event"
        >
            <thead slot="head">
            <v-th sortKey="id"  style="width: 60px">id</v-th>
            <v-th sortKey="first"  style="width: 200px">дата</v-th>
            <v-th sortKey="name"  style="width: 200px">клиент</v-th>
            <v-th sortKey="from"  style="width: 400px">маршрут</v-th>
            <v-th sortKey="cost"  style="width: 60px">грн</v-th>
            <v-th sortKey="uid"  style="width: 250px">uid</v-th>
            <v-th sortKey="reason"  style="width: 200px">close_reason</v-th>
            </thead>
            <tbody slot="body" slot-scope="{displayData}">
            <tr>

                <td ><input class="form-input input-sm"  style="width: 60px" v-model="filters.id.value"></td>


                <td><input class="form-input input-sm" style="width: 200px" v-model="filters.first.value"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filters.name.value"></td>
                <td> <input class="form-input input-lg"  style="width: 400px" v-model="filters.from.value"></td>
                <td> <input class="form-input input-lg" style="width: 60px" v-model="filters.cost.value"></td>
                <td> <input class="form-input input-lg" style="width: 250px" v-model="filters.uid.value"></td>
                <td> <input class="form-input input-lg"  style="width: 200px" v-model="filters.reason.value"></td>
                <td></td>
            </tr>
            <tr v-for="row in displayData" :key="row.id">
                 <td style="width: 60px">{{ row.id }}</td>
                <td> <input id="first" class="form-control" style="width: 200px" v-model.text="row.first" required ></td>
                <td><input id="name" class="form-control" style="width: 200px" v-model.text="row.name" required ></td>
                <td> <input id="from" class="form-control" style="width: 400px" v-model.text="row.from" required ></td>
                <td><input id="cost" class="form-control" style="width: 60px" v-model.text="row.cost" required ></td>
                <td><input id="uid" class="form-control" style="width: 250px" v-model.text="row.uid" required ></td>
                <td><input id="reason" class="form-control" style="width: 200px" v-model.text="row.reason" required ></td>


                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-success" @click="saveClose_reason_data(row.uid)" style="margin-left: 5px">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save2" viewBox="0 0 16 16">
                                <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v4.5h2a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 5.5 6.5h2V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
                            </svg>
                        </button>

                    </div>
                </td>
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
    name: "CloseReasonHome",
    data: () => ({
        loading: true,
        close_reason_data: [],
        currentPage: 1,
        totalPages: 0,
        maxPageLinks: 25,
        filters: {
            id: { value: "", keys: ["id"] },
            first: { value: "", keys: ["first"] },
            name: { value: "", keys: ["name"] },
            from: { value: "", keys: ["from"] },
            cost: { value: "", keys: ["cost"] },
            uid: { value: "", keys: ["uid"] },
            reason: { value: "", keys: ["reason"] },
        }
    }),
    mounted() {
        this.getClose_reason_data()
    },
    methods: {
        getClose_reason_data() {
            axios.get('/closeReasonData/all')
                .then(
                    res => {
                        this.close_reason_data = res.data;
                        this.loading = false;
                    }
                )
        },


        saveClose_reason_data(dispatching_order_uid) {
            axios.get('/UIDStatusReviewAdmin/'+ dispatching_order_uid)
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
