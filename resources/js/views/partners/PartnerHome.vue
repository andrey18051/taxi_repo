<template>

    <div class="container" style="overflow-x: auto;">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
            <h1 class="display-5">Partners</h1>
            <p class="lead">Information about partners</p>
        </div>
        <button class="btn btn-outline-primary" @click="newPartnerButton()" style="margin-left: 5px">
            Добавить партнера
        </button>
        <button class="btn btn-outline-primary" @click="emailButton()" style="margin-left: 5px">
            Сообщение
        </button>
        <div style="width: 1200px; overflow-x: auto;">
            <v-table
                :data="partners"
                :filters="filters"
                :hideSortIcons="true"
                class="my-2 table table-striped"
                :currentPage.sync="currentPage"
                :pageSize="10"
                @totalPagesChanged="totalPages = $event"
            >
                <thead slot="head">
                <v-th sortKey="id" style="width: 30px">#</v-th>
                <v-th sortKey="name" style="width: 200px">Name</v-th>
                <v-th sortKey="email"style="width: 200px">Email</v-th>
                <v-th sortKey="service"style="width: 200px">Service</v-th>
                <v-th sortKey="city"style="width: 200px">City</v-th>
                <v-th sortKey="phone"style="width: 150px">Phone Number</v-th>
                </thead>
                <tbody slot="body" slot-scope="{displayData}">
                <tr>
                    <td><input class="form-input input-sm" style="width: 30px" v-model="filters.id.value" placeholder="Select by id"></td>
                    <td><input class="form-input input-lg" style="width: 200px" v-model="filters.name.value" placeholder="Select by name"></td>
                    <td><input class="form-input input-lg" style="width: 200px"v-model="filters.email.value"  placeholder="Select by email"></td>
                    <td><input class="form-input input-lg" style="width: 200px"v-model="filters.service.value"  placeholder="Select by service"></td>
                    <td><input class="form-input input-lg" style="width: 200px"v-model="filters.city.value"  placeholder="Select by city"></td>
                    <td><input class="form-input input-lg" style="width: 150px"v-model="filters.phone.value"  placeholder="Select by phone"></td>
                    <td style="width: 100px"></td>
                </tr>
                <tr v-for="row in displayData" :key="row.id"  style="width: 30px" ><td>{{ row.id }}</td>

                    <td><input id="name" class="form-control" style="width: 200px" v-model.text="row.name" required ></td>
                    <td><input id="email" class="form-control" style="width: 200px" v-model.text="row.email" required ></td>
                    <td><input id="service" class="form-control" style="width: 200px" v-model.text="row.service" required ></td>
                    <td><input id="city" class="form-control" style="width: 200px" v-model.text="row.city" required ></td>
                    <td><input id="phone" class="form-control" style="width: 150px" v-model.text="row.phone" ></td>

                    <td>
                        <div  class="container-fluid" style="width:100px">
                            <div class="btn-group" role="group">
                                <button class="btn btn-success" @click="editPartner(row.id, row.name, row.email,row.service,row.city, row.phone)" style="margin-left: 5px">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save2" viewBox="0 0 16 16">
                                        <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v4.5h2a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 5.5 6.5h2V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
                                    </svg>
                                </button>
                                <button class="btn btn-danger" @click="deletePartner(row.id)" style="margin-left: 5px">
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
    name: "Partners",
    data: () => ({
        loading: true,
        partners: [],
        currentPage: 1,
        totalPages: 0,
        maxPageLinks: 10,
        filters: {
            id: { value: "", keys: ["id"] },
            name: { value: "", keys: ["name"] },
            email: { value: "", keys: ["email"] },
            service: { value: "", keys: ["service"] },
            city: { value: "", keys: ["city"] },
            phone: { value: "", keys: ["phone"] },
        }
    }),
    mounted() {
        this.getPartners()
    },
    methods: {
        getPartners() {
            axios.get('/partners/all')
                .then(
                    res => {
                        this.partners = res.data;
                        this.loading = false;
                    }
                )
        },
        deletePartner(id) {

            axios.get('/partners/destroy/'+ id)
                .then(response => {
                    let i = this.partners.map(data => data.id).indexOf(id);
                    this.users.splice(i, 1);
                });
            window.location.reload();

        },
        editPartner(id, name, email, service, city, phone) {

            axios.get('/partners/edit/'+ id +'/'+name+'/'+email+'/'+ service+'/'+ city+'/'+ phone)
                .then(function(ret) {
                    console.log(ret.data);
                    // window.location.reload();

                    window.alert("Данные обновлены");
                })
        },
        newPartnerButton() {
            axios.get('/partners/create')
                .then(res => {
                    this.partners = res.data;
                    this.loading = false;
                })
                .catch(error => {
                    console.error("Ошибка при загрузке данных о новом партнере:", error);
                });
            this.getPartners()

        },
        emailButton () {
            this.$router.push('/admin/partner_email');
        }
    }
}
</script>

<style scoped>

</style>
