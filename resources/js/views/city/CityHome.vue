<template>

    <div class="container">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">

                <h1 class="display-5">City</h1>

        </div>
        <v-table
            :data="cities"
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
            <v-th sortKey="address">address</v-th>
            <v-th sortKey="login">login</v-th>
            <v-th sortKey="password">password</v-th>
            <v-th sortKey="password">online</v-th>
            </thead>
            <tbody slot="body" slot-scope="{displayData}">
            <tr>
                <td><input class="form-input input-sm" v-model="filters.id.value"></td>
                <td><input class="form-input input-lg" v-model="filters.name.value" placeholder="Select by name"></td>
                <td> <input class="form-input input-lg" v-model="filters.address.value"  placeholder="Select by address"></td>
                <td> <input class="form-input input-lg" v-model="filters.login.value"  placeholder="Select by login"></td>
                <td> <input class="form-input input-lg" v-model="filters.password.value"  placeholder="Select by password"></td>
                <td> <input class="form-input input-lg" v-model="filters.online.value"  placeholder="Select by online"></td>
                <td></td>
            </tr>
            <tr v-for="row in displayData" :key="row.id">
                <td>{{ row.id }}</td>

                <td> <input id="name" class="form-control" v-model.text="row.name" required ></td>
                <td><input id="address" class="form-control" v-model.text="row.address" required ></td>
                <td> <input id="login" class="form-control" v-model.text="row.login" required ></td>
                <td><input id="password" class="form-control" v-model.text="row.password" required ></td>
                <td>
                    <select id="online" class="form-control" v-model="row.online" required>
                        <option value="true">online</option>
                        <option value="false">no_online</option>
                    </select>
                </td>


                <td>
                    <div class="btn-group" role="group">
                        <button class="btn btn-success" @click="editCities(row.id, row.name, row.address, row.login, row.password, row.online)" style="margin-left: 5px">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save2" viewBox="0 0 16 16">
                                <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v4.5h2a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 5.5 6.5h2V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
                            </svg>
                        </button>
                        <button class="btn btn-danger" @click="deleteCities(row.id)" style="margin-left: 5px">
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
    name: "CityComponent",
    data: () => ({
        loading: true,
        cities: [],
        currentPage: 1,
        totalPages: 0,
        filters: {
            id: { value: "", keys: ["id"] },
            name: { value: "", keys: ["name"] },
            address: { value: "", keys: ["address"] },
            login: { value: "", keys: ["login"] },
            password: { value: "", keys: ["password"] },
            online: { value: "", keys: ["online"] },
        }
    }),
    mounted() {
        this.getCities()
    },
    methods: {
        getCities() {
            axios.get('/city/all')
                .then(
                    res => {
                        this.cities = res.data;
                        this.loading = false;
                    }
                )
        },

        deleteCities(id) {

            axios.get('/city/destroy/'+ id)
                .then(response => {
                    let i = this.cities.map(data => data.id).indexOf(id);
                    this.cities.splice(i, 1);
                    document.location.reload();
                    window.alert("Данные обновлены");
                });
        },
        editCities(id, name, address, login, password, online) {
            axios.get('/city/edit/'+ id +'/'+name+'/'+address+'/'+login+'/'+password+'/'+ online)
                .then(function(ret) {
                    console.log(ret.data);
                    // document.location.reload();
                    window.alert("Данные обновлены");
                })
        }

    }
}
</script>

<style scoped>

</style>
