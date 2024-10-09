<template>

    <div class="container" style="overflow-x: auto;">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
            <h1 class="display-5">Водители</h1>
            <p class="lead">Пополнение баланса</p>
        </div>
        <button class="btn btn-outline-primary" @click="newMessageButton()" style="margin-left: 5px">
            Список сообщений
        </button>
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
            <v-th sortKey="name" style="width: 200px">Name</v-th>
            <v-th sortKey="email"style="width: 300px">Email</v-th>
            <v-th sortKey="user_phone"style="width: 200px">Phone</v-th>
            <v-th sortKey="driverNumber"style="width: 200px">driverNumber</v-th>
            </thead>
            <tbody slot="body" slot-scope="{displayData}">
            <tr>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filter.name.value" placeholder="Select by name"></td>
                <td><input class="form-input input-lg" style="width: 300px" v-model="filter.email.value"  placeholder="Select by email"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filter.phoneNumber.value"  placeholder="Select by user_phone"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filter.driverNumber.value"  placeholder="Select by driverNumber"></td>

                <td style="width: 100px"></td>
            </tr>
            <tr v-for="row in displayData" :key="row.id"  style="width: 30px" >

                <td><input id="name" class="form-control" style="width: 200px" v-model.text="row.name" readonly ></td>
                <td><input id="email" class="form-control" style="width: 300px" v-model.text="row.email" readonly ></td>
                <td><input id="phoneNumber" class="form-control" style="width: 200px" v-model.text="row.phoneNumber" readonly></td>
                <td><input id="driverNumber" class="form-control" style="width: 200px" v-model.text="row.driverNumber" readonly></td>
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
                phoneNumber: { value: "", keys: ["phoneNumber"] },
                driverNumber: { value: "", keys: ["driverNumber"] }
            },
            selectedUser: '', // Новое свойство для хранения выбранного пользователя
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

        handleCheckboxChange(row) {
            const email = row.email;

            if (row.sent) {
                // Если галочка установлена, добавляем email в массив
                if (!this.selectedEmails.includes(email)) {
                    this.selectedEmails.push(email);
                }
            } else {
                // Если галочка снята, удаляем email из массива
                this.selectedEmails = this.selectedEmails.filter(item => item !== email);
            }
        },

        addToBalance() {
            if (!this.selectedEmails || this.selectedEmails.length === 0) {
                window.alert('Пожалуйста убедитесь, что выбран хотя бы один водитель.');
                return;
            }


            axios.get(`/addToBalanceDriver/${this.selectedEmails.join(',')}/${this.amount}`)
                .then(response => {
                    // Проверяем успешность операции
                    if (response.status === 200) {
                        window.alert("Данные успешно обновлены");
                        window.location.reload();
                    } else {
                        window.alert("Произошла ошибка при обновлении данных " + response.status);
                    }
                })
                .catch(error => {
                    console.error(error);
                    window.alert("Произошла ошибка при обновлении данных" + error);
                });

            // Здесь вы можете использовать this.selectedUser и this.newMessage
            // для отправки сообщения, например, с использованием вашего бэкенда или других API-методов.
            // Очистите поля после успешной отправки, если это необходимо.
            this.selectedEmails = []; // Очистить массив выбранных email после отправки
        },
    }
}
</script>

<style scoped>

</style>
