<template>

    <div class="container-fluid" style="overflow-x: auto;">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
            <h1 class="display-5">Users</h1>
            <p class="lead">Information about users</p>
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
                <v-th sortKey="user_phone"style="width: 300px">user_phone</v-th>
                <v-th sortKey="bonus"style="width: 100px">Bonus</v-th>
                <v-th sortKey="bonus"style="width: 100px">Bonus_pas_1</v-th>
                <v-th sortKey="bonus"style="width: 100px">Bonus_pas_2</v-th>
                <v-th sortKey="bonus"style="width: 100px">Bonus_pas_4</v-th>
                <v-th sortKey="bonus_pay" style="width: 30px">Бонус</v-th>
                <v-th sortKey="card_pay" style="width: 30px">Карта</v-th>
                <v-th sortKey="black_list" style="width: 30px">Black list</v-th>
                </thead>
                <tbody slot="body" slot-scope="{displayData}">
                <tr>
                    <td><input class="form-input input-sm" style="width: 30px" v-model="filters.id.value" placeholder="Select by id"></td>
                    <td><input class="form-input input-lg" style="width: 200px" v-model="filters.name.value" placeholder="Select by name"></td>
                    <td><input class="form-input input-lg" style="width: 300px"v-model="filters.email.value"  placeholder="Select by email"></td>
                    <td><input class="form-input input-lg" style="width: 300px"v-model="filters.user_phone.value"  placeholder="Select by user_phone"></td>
                    <td><input class="form-input input-lg" style="width: 100px"v-model="filters.bonus.value"  placeholder="Select by bonus"></td>
                    <td><input class="form-input input-lg" style="width: 100px"v-model="filters.bonus_pas_1.value"  placeholder="Select by bonus"></td>
                    <td><input class="form-input input-lg" style="width: 100px"v-model="filters.bonus_pas_2.value"  placeholder="Select by bonus"></td>
                    <td><input class="form-input input-lg" style="width: 100px"v-model="filters.bonus_pas_4.value"  placeholder="Select by bonus"></td>
                    <td><input class="form-input input-lg" style="width: 100px"v-model="filters.black_list.value"  placeholder="Select by black_list"></td>
                    <td style="width: 30px"></td>
                    <td style="width: 30px"></td>
                    <td style="width: 100px"></td>
                </tr>
                <tr v-for="row in displayData" :key="row.id"  style="width: 30px" ><td>{{ row.id }}</td>

                    <td><input id="name" class="form-control" style="width: 200px" v-model.text="row.name" required ></td>
                    <td><input id="email" class="form-control" style="width: 300px" v-model.text="row.email" required ></td>
                    <td><input id="user_phone" class="form-control" style="width: 300px" v-model.text="row.user_phone" required ></td>
                    <td><input id="bonus" class="form-control" style="width: 100px" v-model.text="row.bonus" value="0"></td>
                    <td><input id="bonus_pas_1" class="form-control" style="width: 100px" v-model.text="row.bonus_pas_1" value="0"></td>
                    <td><input id="bonus_pas_2" class="form-control" style="width: 100px" v-model.text="row.bonus_pas_2" value="0"></td>
                    <td><input id="bonus_pas_4" class="form-control" style="width: 100px" v-model.text="row.bonus_pas_4" value="0"></td>
                    <td>
                        <input type="checkbox" id="bonus_pay" style="width: 30px" v-model="row.bonus_pay" >
                    </td>
                    <td>
                        <input type="checkbox" id="card_pay" style="width: 30px" v-model="row.card_pay" >
                    </td>
                    <td>
                        <input type="checkbox" id="black_list" style="width: 30px" v-model="row.black_list" >
                    </td>






                    <td>
                        <div  class="container-fluid" style="width:100px">
                            <div class="btn-group" role="group">
                                <button class="btn btn-success" @click="editUser(row.id, row.name, row.email, row.bonus, row.bonus_pay, row.card_pay, row.black_list)" style="margin-left: 5px">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save2" viewBox="0 0 16 16">
                                        <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v4.5h2a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 5.5 6.5h2V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
                                    </svg>
                                </button>


                                <button class="btn btn-primary" @click="bonusAdmin(row.id, row.bonus)" style="margin-left: 5px">
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
    name: "UserComponent",
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
            bonus: { value: "", keys: ["bonus"] },
            bonus_pas_1: { value: "", keys: ["bonus_pas_1"] },
            bonus_pas_2: { value: "", keys: ["bonus_pas_2"] },
            bonus_pas_4: { value: "", keys: ["bonus_pas_4"] },
            bonus_pay: { value: "", keys: ["bonus_pay"] },
            card_pay: { value: "", keys: ["card_pay"] },
            black_list: { value: "", keys: ["black_list"] }
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
        deleteUser(id) {
            window.alert("Данные обновлены /users/destroy/" + id)
            axios.get('/users/destroy/'+ id)
                .then(response => {
                    let i = this.users.map(data => data.id).indexOf(id);
                    this.users.splice(i, 1);
                    document.location.reload();
                    window.alert("Данные обновлены");
                });
        },
        editUser(id, name, email, bonus, bonus_pay, card_pay, black_list) {
            axios.get('/users/edit/'+ id +'/'+name+'/'+email+'/'+ bonus + '/' + bonus_pay + '/' + card_pay+ '/' + black_list)
                .then(function(ret) {
                    console.log(ret.data);
                    // document.location.reload();
                    window.alert("Данные обновлены");
                })
        },
        bonusAdmin(id, bonus) {
            axios.get('/bonus/bonusAdmin/'+ id + '/'+ bonus + '/')
                .then(function(ret) {
                    console.log(ret.data);
                    // document.location.reload();
                    window.alert("Данные обновлены /bonus/bonusAdmin/"+ id + '/'+ bonus + '/');
                })
        }

    }
}
</script>

<style scoped>

</style>
