<template>

    <div class="container">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
            <h1 class="display-5">Users</h1>
            <p class="lead">Information about users</p>
        </div>
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
            <v-th sortKey="id">#</v-th>
            <v-th sortKey="name" >Name</v-th>
            <v-th sortKey="email">Email</v-th>
            </thead>
            <tbody slot="body" slot-scope="{displayData}">
            <tr>
                <td><input class="form-input input-sm" v-model="filters.id.value" placeholder="Select by id"></td>
                <td><input class="form-input input-lg" v-model="filters.name.value" placeholder="Select by name"></td>
                <td> <input class="form-input input-lg" v-model="filters.email.value"  placeholder="Select by email"></td>
                <td></td>
            </tr>
            <tr v-for="row in displayData" :key="row.id">
                <td>{{ row.id }}</td>

                <td> <input id="name" class="form-control" v-model.text="row.name" required ></td>
                <td><input id="email" class="form-control" v-model.text="row.email" required ></td>
                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-success" @click="editUser(row.id, row.name, row.email)" style="margin-left: 5px">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save2" viewBox="0 0 16 16">
                                <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v4.5h2a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 5.5 6.5h2V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
                            </svg>
                        </button>
                        <button class="btn btn-danger" @click="deleteUser(row.id)" style="margin-left: 5px">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                                <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                            </svg>
                        </button>
                    </div>
                </td>
            </tr>
            </tbody>
        </v-table>

        <smart-pagination
            :currentPage.sync="currentPage"
            :totalPages="totalPages"
        />
    </div>

</template>

<script>
import axios from 'axios';
export default {
    name: "UserComponent",
    data: () => ({
        loading: true,
        users: [],
        currentPage: 1,
        totalPages: 0,
        filters: {
            id: { value: "", keys: ["id"] },
            name: { value: "", keys: ["name"] },
            email: { value: "", keys: ["email"] }
        }
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
        deleteUser(id) {

            axios.get('/users/destroy/'+ id)
                .then(response => {
                    let i = this.users.map(data => data.id).indexOf(id);
                    this.users.splice(i, 1);
                    document.location.reload();
                    window.alert("Данные обновлены");
                });
        },
        editUser(id, name, email) {
            axios.get('/users/edit/'+ id +'/'+name+'/'+email)
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
