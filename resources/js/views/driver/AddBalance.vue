<template>

    <div class="container" style="overflow-x: auto;">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
            <h1 class="display-5">Водители</h1>
            <p class="lead">Пополнение баланса</p>
        </div>

        <v-table
            :data="users"
            :filters="filter"
            :hideSortIcons="true"
            class="my-2 table table-striped"
            :currentPage.sync="currentPage"
            :pageSize="2"
            @totalPagesChanged="totalPages = $event"
        >
            <thead slot="head">
            <v-th sortKey="name" style="width: 200px">ФИО</v-th>
            <v-th sortKey="email"style="width: 300px">Email</v-th>
            <v-th sortKey="user_phone"style="width: 200px">Телефон</v-th>
            <v-th sortKey="driverNumber"style="width: 200px">Позывной</v-th>
            <v-th sortKey="balance_current"style="width: 200px">Баланс</v-th>
            </thead>
            <tbody slot="body" slot-scope="{displayData}">
            <tr>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filter.name.value" placeholder="Поиск по имени"></td>
                <td><input class="form-input input-lg" style="width: 300px" v-model="filter.email.value"  placeholder="Поиск по email"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filter.phoneNumber.value"  placeholder="Поиск по номеру телефона"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filter.driverNumber.value"  placeholder="Поиск по позывному"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filter.balance_current.value"  placeholder="Поиск по балансу"></td>

                <td style="width: 100px"></td>
            </tr>
            <tr v-for="row in displayData" :key="row.id"  style="width: 30px" >

                <td><input id="name" class="form-control" style="width: 200px" v-model.text="row.name" readonly ></td>
                <td><input id="email" class="form-control" style="width: 300px" v-model.text="row.email" readonly ></td>
                <td><input id="phoneNumber" class="form-control" style="width: 200px" v-model.text="row.phoneNumber" readonly></td>
                <td><input id="driverNumber" class="form-control" style="width: 200px" v-model.text="row.driverNumber" readonly></td>
                <td><input id="balance_current" class="form-control" style="width: 200px" v-model.text="row.balance_current" readonly></td>
                <td><input id="uid" type="hidden" v-model.text="row.uid" readonly></td>
                <td>
                    <input type="checkbox" id="sent_message_info" style="width: 30px" v-model="row.sent" @change="handleCheckboxChange(row)">
                </td>
            </tr>
            </tbody>
        </v-table>
        <smart-pagination
            :currentPage.sync="currentPage"
            :totalPages="totalPages"
            :maxPageLinks="maxPageLinks"
        />
            <div class="card offset-4 col-4">
                <div class="card-body">
                        <label for="amount">Введите сумму пополнения баланса:</label>
                        <input v-model="amount" id="amount" class="form-control" value="">
                        <br>
                       <button class="btn btn-outline-success" @click="addToBalance">Пополнить</button>
            </div>

        </div>



    </div>


</template>

<script>
import axios from 'axios';
export default {
    name: "DriverBalanceAdd",
    data() {
        return {
            loading: true,
            users: [],
            messages: [],
            currentPage: 1,
            totalPages: 0,
            maxPageLinks: 10,
            filter: {
                id: { value: "", keys: ["id"] },
                name: { value: "", keys: ["name"] },
                email: { value: "", keys: ["email"] },
                uid: { value: "", keys: ["uid"] },
                phoneNumber: { value: "", keys: ["phoneNumber"] },
                driverNumber: { value: "", keys: ["driverNumber"] },
                balance_current: { value: "", keys: ["balance_current"] }
            },
            selectedUidDriver: [], // Новое свойство для хранения выбранного пользователя
            amount: '', // Новое свойство для хранения выбранного пользователя
        };
    },
    mounted() {
        this.getUsers();
    },



    methods: {
        getUsers() {
            axios.get('/driverAll')
                .then(
                    res => {
                        this.users = res.data;
                        this.loading = false;
                    }
                )
        },
        logSelectedDrivers() {
            console.log("Selected drivers:", this.selectedUidDriver);
        },

        handleCheckboxChange(row) {
            const uid = row.uid;

            if (row.sent) {
                // Если галочка установлена, добавляем uid в массив
                if (!this.selectedUidDriver.includes(uid)) {
                    this.selectedUidDriver.push(uid);
                }
            } else {
                // Если галочка снята, удаляем uid из массива
                this.selectedUidDriver = this.selectedUidDriver.filter(item => item !== uid);
            }

            console.log("Selected UIDs:", this.selectedUidDriver); // Лог для проверки
        },

        addToBalance() {
            if (!this.selectedUidDriver || this.selectedUidDriver.length === 0) {
                window.alert('Пожалуйста убедитесь, что выбран хотя бы один водитель.');
                return;
            }
            console.log("Selected drivers:", this.selectedUidDriver);  // Лог для проверки
            console.log("Amount:", this.amount);  // Лог для проверки
            console.log(`/addToBalanceDriver/${this.selectedUidDriver.join(',')}/${this.amount}`);  // Лог для проверки


            axios.get(`/addToBalanceDriver/${this.selectedUidDriver.join(',')}/${this.amount}`)
                .then(response => {
                    // Проверяем успешность операции
                    if (response.status === 200) {
                        window.alert("Данные успешно обновлены");
                        window.location.reload();
                    } else {
                        window.alert("Произошла ошибка при обновлении данных " + `/addToBalanceDriver/${this.selectedUidDriver.join(',')}/${this.amount}` + response.status);
                    }
                })
                .catch(error => {
                    console.error(error);
                    window.alert("Произошла ошибка при обновлении данных" + `/addToBalanceDriver/${this.selectedUidDriver.join(',')}/${this.amount}`+ error);
                });

            // Здесь вы можете использовать this.selectedUser и this.newMessage
            // для отправки сообщения, например, с использованием вашего бэкенда или других API-методов.
            // Очистите поля после успешной отправки, если это необходимо.
            this.selectedUidDriver = []; // Очистить массив выбранных email после отправки
        },
    }
}
</script>

<style scoped>

</style>
