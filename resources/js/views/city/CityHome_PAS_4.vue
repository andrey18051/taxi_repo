<template>

    <div class="container-fluid" style="overflow-x: auto;">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">

                <h1 class="display-5">City PAS 4</h1>

        </div>
        <button class="btn btn-outline-primary" @click="newCityButton()" style="margin-left: 5px">
            Добавить сервер
        </button>
        <button class="btn btn-outline-primary" @click="cityPas1Button()" style="margin-left: 5px">
            PAS 1
        </button>
        <button class="btn btn-outline-primary" @click="cityPas2Button()" style="margin-left: 5px">
            PAS 2
        </button>
        <div class="container-fluid">
            <v-table
                :data="cities"
                :filters="filters"
                :hideSortIcons="true"
                class="my-2 table table-striped"
                :currentPage.sync="currentPage"
                :pageSize="10"
                @totalPagesChanged="totalPages = $event"
            >
                <thead slot="head">
                <v-th sortKey="id">#</v-th>
                <v-th sortKey="name" >Name</v-th>
                <v-th sortKey="card_max_pay">card_max_pay</v-th>
                <v-th sortKey="login">bonus_max_pay</v-th>
                <v-th sortKey="address">address</v-th>
                <v-th sortKey="login">login</v-th>
                <v-th sortKey="password">password</v-th>
                <v-th sortKey="online">online</v-th>
                <v-th sortKey="black_list">black_list</v-th>
                </thead>
                <tbody slot="body" slot-scope="{displayData}">
                <tr>
                    <td><input class="form-input input-sm"  style="width: 30px" v-model="filters.id.value"></td>
                    <td><input class="form-input input-lg"  style="width: 300px" v-model="filters.name.value" placeholder="Select by name"></td>
                    <td><input class="form-input input-lg"  style="width: 100px" v-model="filters.card_max_pay.value" placeholder="Select by card_max_pay" value="0"></td>
                    <td> <input class="form-input input-lg" style="width: 100px" v-model="filters.bonus_max_pay.value"  placeholder="Select by bonus_max_pay" value="0" ></td>
                    <td> <input class="form-input input-lg" style="width: 200px" v-model="filters.address.value"  placeholder="Select by address"></td>
                    <td> <input class="form-input input-lg" style="width: 150px" v-model="filters.login.value"  placeholder="Select by login"></td>
                    <td> <input class="form-input input-lg" style="width: 150px" v-model="filters.password.value"  placeholder="Select by password"></td>
                    <td> <input class="form-input input-lg" style="width: 120px" v-model="filters.online.value"  placeholder="Select by online"></td>
                    <td> <input class="form-input input-lg" style="width: 120px" v-model="filters.black_list.value"  placeholder="Select by black_list"></td>
                    <td></td>
                </tr>
                <tr v-for="row in displayData" :key="row.id">
                    <td style="width: 30px">{{ row.id }}</td>

                    <td> <input id="name" class="form-control" style="width: 300px" v-model.text="row.name" required ></td>
                    <td><input id="card_max_pay" class="form-control" style="width: 100px" v-model.text="row.card_max_pay"  ></td>
                    <td> <input id="bonus_max_pay" class="form-control" style="width: 100px" v-model.text="row.bonus_max_pay"  ></td>
                    <td><input id="address" class="form-control" style="width: 200px" v-model.text="row.address" required ></td>
                    <td> <input id="login" class="form-control" style="width: 150px" v-model.text="row.login" required ></td>
                    <td><input id="password" class="form-control" style="width: 120px" v-model.text="row.password" required ></td>
                    <td>
                        <select id="online" class="form-control" style="width: 120px" v-model="row.online" required>
                            <option value="true">online</option>
                            <option value="false">no_online</option>
                        </select>
                    </td>

                    <td>
                        <select id="black_list" class="form-control" style="width: 120px" v-model="row.black_list" required>
                            <option value="cards only">cards only</option>
                            <option value="cash">cash</option>
                        </select>
                    </td>
                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-success" @click="editCities(row.id, row.name, row.address, row.login, row.password, row.online, row.card_max_pay, row.bonus_max_pay, row.black_list)" style="margin-left: 5px">
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
    name: "CityHomePas4",
    data: () => ({
        loading: true,
        cities: [],
        currentPage: 1,
        totalPages: 0,
        maxPageLinks: 25,
        filters: {
            id: { value: "", keys: ["id"] },
            name: { value: "", keys: ["name"] },
            address: { value: "", keys: ["address"] },
            login: { value: "", keys: ["login"] },
            password: { value: "", keys: ["password"] },
            online: { value: "", keys: ["online"] },
            card_max_pay: { value: "", keys: ["card_max_pay"] },
            bonus_max_pay: { value: "", keys: ["bonus_max_pay"] },
            black_list: { value: "", keys: ["black_list"] },
        }
    }),
    mounted() {
        this.getCities()
    },
    methods: {
        getCities() {
            axios.get('/pas4/city/all')
                .then(
                    res => {
                        this.cities = res.data;
                        this.loading = false;
                    }
                )
        },

        deleteCities(id) {

            axios.get('/pas4/city/destroy/'+ id)
                .then(response => {
                    let i = this.cities.map(data => data.id).indexOf(id);
                    this.cities.splice(i, 1);
                    document.location.reload();
                    window.alert("Данные обновлены");
                });
        },
        editCities(id, name, address, login, password, online, card_max_pay, bonus_max_pay, black_list) {
            axios.get('/pas2/city/edit/'+ id +'/'+name+'/'+address+'/'+login+'/'+password+'/'+ online+'/'+card_max_pay+'/'+ bonus_max_pay+'/'+ black_list)
                .then(function(ret) {
                    console.log(ret.data);
                    // document.location.reload();
                    window.alert("Данные обновлены");
                })
        },
        newCityButton() {
            axios.get('/pas4/city/newCityCreat')
                .then(res => {
                    this.partners = res.data;
                    this.loading = false;
                })
                .catch(error => {
                    console.error("Ошибка при загрузке данных о новом партнере:", error);
                });
            this.getCities()

        },
        cityPas1Button() {
            this.$router.push('/admin/city-pas1');

        },
        cityPas2Button() {
            this.$router.push('/admin/city-pas2');

        },
        cityPas4Button() {
            this.$router.push('/admin/city-pas4');

        },

    }
}
</script>

<style scoped>

</style>
