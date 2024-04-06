<template>

    <div class="container" style="overflow-x: auto;">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
            <h1 class="display-5">Partner`s Groups</h1>
            <p class="lead">Information about groups of partnerGroups</p>
        </div>
        <button class="btn btn-outline-primary" @click="newPartnerGroupButton()" style="margin-left: 5px">
            Добавить группу партнеров
        </button>
        <button class="btn btn-outline-primary" @click="emailButton()" style="margin-left: 5px">
            Сообщение
        </button>
        <div style="width: 1200px; overflow-x: auto;">
            <v-table
                :data="partnerGroups"
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
                <v-th sortKey="description"style="width: 200px">description</v-th>
                </thead>
                <tbody slot="body" slot-scope="{displayData}">
                <tr>
                    <td><input class="form-input input-sm" style="width: 30px" v-model="filters.id.value" placeholder="Select by id"></td>
                    <td><input class="form-input input-lg" style="width: 200px" v-model="filters.name.value" placeholder="Select by name"></td>
                    <td><input class="form-input input-lg" style="width: 200px"v-model="filters.description.value"  placeholder="Select by description"></td>
                    <td style="width: 100px"></td>
                </tr>
                <tr v-for="row in displayData" :key="row.id"  style="width: 30px" ><td>{{ row.id }}</td>

                    <td><input id="name" class="form-control" style="width: 200px" v-model.text="row.name" required ></td>
                    <td><input id="description" class="form-control" style="width: 200px" v-model.text="row.description" required ></td>
                    <td>
                        <div  class="container-fluid" style="width:100px">
                            <div class="btn-group" role="group">
                                <button class="btn btn-success" @click="editPartnerGroup (row.id, row.name, row.description)" style="margin-left: 5px">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save2" viewBox="0 0 16 16">
                                        <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v4.5h2a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 5.5 6.5h2V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
                                    </svg>
                                </button>
                                <button class="btn btn-danger" @click="deletePartnerGroup(row.id)" style="margin-left: 5px">
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
    name: "PartnerGroup",
    data: () => ({
        loading: true,
        partnerGroups: [],
        currentPage: 1,
        totalPages: 0,
        maxPageLinks: 10,
        filters: {
            id: { value: "", keys: ["id"] },
            name: { value: "", keys: ["name"] },
            description: { value: "", keys: ["description"] },
        }
    }),
    mounted() {
        this.getPartnerGroups()
    },
    methods: {
        getPartnerGroups() {
            axios.get('/partnerGroups/showPartnerGroupsAll')
                .then(
                    res => {
                        this.partnerGroups = res.data;
                        this.loading = false;
                    }
                )
        },
        deletePartnerGroup(id) {

            axios.get('/partnerGroups/destroy/'+ id)
                .then(response => {
                    let i = this.partnerGroups.map(data => data.id).indexOf(id);
                    this.users.splice(i, 1);
                });
            window.location.reload();

        },
        editPartnerGroup(id, name, description) {
             axios.get('/partnerGroups/edit/'+ id +'/'+name+'/'+description)
                .then(function(ret) {
                    console.log(ret.data);
                    // window.location.reload();
                    window.alert("Данные обновлены");
                })
        },
        newPartnerGroupButton() {
            axios.get('/partnerGroups/create');
            this.getPartnerGroups()
        },
        emailButton () {
            this.$router.push('/admin/partner_email');
        }
    }
}
</script>

<style scoped>

</style>
