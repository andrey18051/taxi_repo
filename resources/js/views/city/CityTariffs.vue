<template>
    <div class="container-fluid" style="overflow-x: auto;">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
            <h1 class="display-5">City Tariffs</h1>
        </div>

        <button class="btn btn-outline-primary" @click="newTariffButton()" style="margin-left: 5px">
            Добавить тариф
        </button>

        <div class="container-fluid">
            <v-table
                :data="tariffs"
                :filters="filters"
                :hideSortIcons="true"
                class="my-2 table table-striped"
                :currentPage.sync="currentPage"
                :pageSize="10"
                @totalPagesChanged="totalPages = $event"
            >
                <thead slot="head">
                <v-th sortKey="id">#</v-th>
                <v-th sortKey="city">City</v-th>
                <v-th sortKey="base_price">Base Price</v-th>
                <v-th sortKey="base_distance">Base Distance</v-th>
                <v-th sortKey="price_per_km">Price per km</v-th>
                <v-th sortKey="is_test">Test Mode</v-th>
                <v-th>Actions</v-th>
                </thead>
                <tbody slot="body" slot-scope="{displayData}">
                <tr>
                    <td><input class="form-input input-sm" style="width: 30px" v-model="filters.id.value"></td>
                    <td><input class="form-input input-lg" style="width: 200px" v-model="filters.city.value" placeholder="Filter by city"></td>
                    <td><input class="form-input input-lg" style="width: 100px" v-model="filters.base_price.value" placeholder="Filter by price"></td>
                    <td><input class="form-input input-lg" style="width: 100px" v-model="filters.base_distance.value" placeholder="Filter by distance"></td>
                    <td><input class="form-input input-lg" style="width: 100px" v-model="filters.price_per_km.value" placeholder="Filter by km price"></td>
                    <td>
                        <select class="form-control" style="width: 120px" v-model="filters.is_test.value">
                            <option value="">All</option>
                            <option value="true">Test</option>
                            <option value="false">Regular</option>
                        </select>
                    </td>
                    <td></td>
                </tr>
                <tr v-for="row in displayData" :key="row.id">
                    <td style="width: 30px">{{ row.id }}</td>

                    <td>
                        <input class="form-control" style="width: 200px" v-model="row.city" required>
                    </td>
                    <td>
                        <input type="number" class="form-control" style="width: 100px" v-model.number="row.base_price" step="0.01" min="0" required>
                    </td>
                    <td>
                        <input type="number" class="form-control" style="width: 100px" v-model.number="row.base_distance" min="1" required>
                    </td>
                    <td>
                        <input type="number" class="form-control" style="width: 100px" v-model.number="row.price_per_km" step="0.01" min="0" required>
                    </td>
                    <td>
                        <select class="form-control" style="width: 120px" v-model="row.is_test" required>
                            <option :value="true">Test</option>
                            <option :value="false">Regular</option>
                        </select>
                    </td>

                    <td>
                        <div class="btn-group" role="group">
                            <button class="btn btn-success" @click="updateTariff(row)" style="margin-left: 5px">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save2" viewBox="0 0 16 16">
                                    <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v4.5h2a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 5.5 6.5h2V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
                                </svg>
                            </button>
                            <button class="btn btn-danger" @click="deleteTariff(row.id)" style="margin-left: 5px">
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
    name: "CityTariffs",
    data: () => ({
        loading: true,
        tariffs: [],
        currentPage: 1,
        totalPages: 0,
        maxPageLinks: 25,
        filters: {
            id: { value: "", keys: ["id"] },
            city: { value: "", keys: ["city"] },
            base_price: { value: "", keys: ["base_price"] },
            base_distance: { value: "", keys: ["base_distance"] },
            price_per_km: { value: "", keys: ["price_per_km"] },
            is_test: { value: "", keys: ["is_test"] },
        }
    }),
    mounted() {
        this.getTariffs()
    },
    methods: {
        getTariffs() {
            axios.get('/api/tariffs')
                .then(res => {
                    this.tariffs = res.data.data;
                    this.loading = false;
                })
                .catch(error => {
                    console.error("Ошибка при загрузке тарифов:", error);
                    window.alert("Ошибка при загрузке тарифов");
                });
        },

        deleteTariff(id) {
            if (confirm("Вы уверены, что хотите удалить этот тариф?")) {
                axios.delete(`/api/tariffs/${id}`)
                    .then(response => {
                        let i = this.tariffs.map(data => data.id).indexOf(id);
                        this.tariffs.splice(i, 1);
                        window.alert("Тариф удален");
                    })
                    .catch(error => {
                        console.error("Ошибка при удалении тарифа:", error);
                        window.alert("Ошибка при удалении тарифа");
                    });
            }
        },

        updateTariff(tariff) {
            axios.put(`/api/tariffs/${tariff.id}`, {
                city: tariff.city,
                base_price: tariff.base_price,
                base_distance: tariff.base_distance,
                price_per_km: tariff.price_per_km,
                is_test: tariff.is_test
            })
                .then(response => {
                    window.alert("Тариф обновлен");
                })
                .catch(error => {
                    console.error("Ошибка при обновлении тарифа:", error);
                    window.alert("Ошибка при обновлении тарифа");
                });
        },

        newTariffButton() {
            const newTariff = {
                city: 'New City',
                base_price: 100.00,
                base_distance: 3,
                price_per_km: 13.00,
                is_test: false
            };

            axios.post('/api/tariffs', newTariff)
                .then(response => {
                    this.tariffs.push(response.data.data);
                    window.alert("Новый тариф добавлен");
                })
                .catch(error => {
                    console.error("Ошибка при создании тарифа:", error);
                    window.alert("Ошибка при создании тарифа");
                });
        }
    }
}
</script>

<style scoped>
.form-control {
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
    padding: 0.375rem 0.75rem;
}

.btn {
    margin: 2px;
}

.table-striped tbody tr:nth-of-type(odd) {
    background-color: rgba(0, 0, 0, 0.05);
}
</style>
