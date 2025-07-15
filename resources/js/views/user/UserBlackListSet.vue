<template>

    <div class="container-fluid" style="overflow-x: auto;">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
            <h1 class="display-5">Users</h1>
            <p class="lead">Information about users Black List</p>
        </div>
        <div class="container-fluid">
            <v-table
                :data="users"
                :filters="filters"
                :hideSortIcons="true"
                class="my-2 table table-striped"
                :currentPage.sync="currentPage"
                :pageSize="5"
                @totalPagesChanged="totalPages = $event"
            >
                <thead slot="head">
                <v-th sortKey="id" style="width: 30px">#</v-th>
                <v-th sortKey="name" style="width: 200px">Name</v-th>
                <v-th sortKey="email"style="width: 300px">Email</v-th>
                <v-th sortKey="user_phone" style="width: 300px">user_phone</v-th>
                <v-th sortKey="black_list" style="width: 30px">PAS1</v-th>
                <v-th sortKey="black_list" style="width: 30px">PAS2</v-th>
                <v-th sortKey="black_list" style="width: 30px">PAS4</v-th>
                </thead>
                <tbody slot="body" slot-scope="{displayData}">
                <tr>
                    <td><input class="form-input input-sm" style="width: 30px" v-model="filters.id.value" placeholder="Select by id"></td>
                    <td><input class="form-input input-lg" style="width: 200px" v-model="filters.name.value" placeholder="Select by name"></td>
                    <td><input class="form-input input-lg" style="width: 300px" v-model="filters.email.value"  placeholder="Select by email"></td>
                    <td><input class="form-input input-lg" style="width: 300px" v-model="filters.user_phone.value"  placeholder="Select by user_phone"></td>
                    <td><input class="form-input input-lg" style="width: 100px " v-model="filters.black_list_PAS1.value"  placeholder="Select by black_list"></td>
                    <td><input class="form-input input-lg" style="width: 100px" v-model="filters.black_list_PAS2.value"  placeholder="Select by black_list"></td>
                    <td><input class="form-input input-lg" style="width: 100px" v-model="filters.black_list_PAS4.value"  placeholder="Select by black_list"></td>
                    <td style="width: 30px"></td>
                </tr>
                <tr v-for="row in displayData" :key="row.id"  style="width: 30px" ><td>{{ row.id }}</td>

                    <td><input id="name" class="form-control" style="width: 200px" v-model.text="row.name" required ></td>
                    <td><input id="email" class="form-control" style="width: 300px" v-model.text="row.email" required ></td>
                    <td><input id="user_phone" class="form-control" style="width: 300px" v-model.text="row.user_phone" required ></td>

                    <td>
                        <input type="checkbox" id="black_list_PAS1" style="width: 30px" v-model="row.black_list_PAS1" >
                    </td>
                    <td>
                        <input type="checkbox" id="black_list_PAS2" style="width: 30px" v-model="row.black_list_PAS2" >
                    </td>
                    <td>
                        <input type="checkbox" id="black_list_PAS4" style="width: 30px" v-model="row.black_list_PAS4" >
                    </td>


                    <td>
                        <div  class="container-fluid" style="width:100px">
                            <div class="btn-group" role="group">
                                <button class="btn btn-success" @click="editUser(row.id, row.black_list_PAS1, row.black_list_PAS2,  row.black_list_PAS4)" style="margin-left: 5px">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save2" viewBox="0 0 16 16">
                                        <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v4.5h2a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 5.5 6.5h2V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
                                    </svg>
                                </button>

                            </div>
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
    name: "UserBlackListSetComponent",
    data: () => ({
        loading: true,
        users: [],
        currentPage: 1,
        totalPages: 0,
        maxPageLinks: 10,
        filters: {
            id: { value: "", keys: ["id"] },
            name: { value: "", keys: ["name"] },
            email: { value: "", keys: ["email"] },
            user_phone: { value: "", keys: ["user_phone"] },
            black_list_PAS1: { value: "", keys: ["black_list_PAS1"] },
            black_list_PAS2: { value: "", keys: ["black_list_PAS2"] },
            black_list_PAS4: { value: "", keys: ["black_list_PAS4"] }
        },

    }),

    mounted() {
        this.getUsers()
    },
    methods: {
        getUsers() {
            axios.get('/users/all')
                .then(
                    res => {
                        this.users = res.data;
                        this.loading = false;
                    }
                )
        },

        editUser(id, black_list_PAS1, black_list_PAS2, black_list_PAS4) {
            axios.get('/users/blackListSet/'+ id + '/' + black_list_PAS1 + '/' + black_list_PAS2 + '/' + black_list_PAS4 )
                .then(function(ret) {
                    console.log(ret.data);
                    // document.location.reload();
                    window.alert("Данные обновлены");
                })
        },

    }
}
</script>

<style scoped>

</style>
