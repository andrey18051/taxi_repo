<template>

    <div class="container" style="overflow-x: auto;">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
            <h1 class="display-5">Users</h1>
            <p class="lead">User's messages</p>
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
            <v-th sortKey="id" style="width: 30px">#</v-th>
            <v-th sortKey="name" style="width: 200px">Name</v-th>
            <v-th sortKey="email"style="width: 300px">Email</v-th>
            <v-th sortKey="user_phone"style="width: 200px">Phone</v-th>
            </thead>
            <tbody slot="body" slot-scope="{displayData}">
            <tr>
                <td><input class="form-input input-sm" style="width: 30px" v-model="filter.id.value" placeholder="Select by id"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filter.name.value" placeholder="Select by name"></td>
                <td><input class="form-input input-lg" style="width: 300px" v-model="filter.email.value"  placeholder="Select by email"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filter.user_phone.value"  placeholder="Select by user_phone"></td>

                <td style="width: 100px"></td>
            </tr>
            <tr v-for="row in displayData" :key="row.id"  style="width: 30px" >
                <td>{{ row.id }}</td>


                <td><input id="name" class="form-control" style="width: 200px" v-model.text="row.name" readonly ></td>
                <td><input id="email" class="form-control" style="width: 300px" v-model.text="row.email" readonly ></td>
                <td><input id="user_phone" class="form-control" style="width: 200px" v-model.text="row.user_phone" readonly></td>
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


                        <!-- Новые поля для ввода -->
                        <div>
                            <label for="userSearch">Выберите приложение:</label>
                            <select v-model="selectedApp" id="userSearch" class="form-control">
                                <option value="ALL PASS">Все приложения</option>
                                <option value="PAS1">ПАС 1</option>
                                <option value="PAS2">ПАС 2</option>
                                <option value="PAS4">ПАС 4</option>
                            </select>

                        </div>
                    <div>
                            <label for="city">Выберите город:</label>
                            <select v-model="city" id="city" class="form-control">
                                <option value="ALL CITY">Все города</option>
                                <option value="Kyiv City"> Киев</option>
                                <option value="Dnipropetrovsk Oblast">Днепр</option>
                                <option value="Odessa">Одесса</option>
                                <option value="Zaporizhzhia">Запорожье</option>
                                <option value="Cherkasy Oblast">Черкассы</option>
                                <option value="foreign countries">Другое</option>
                            </select>

                        </div>


                        <!-- Новое поле для ввода нового сообщения -->
                        <label for="newMessage">Введите новое сообщение:</label>
                        <textarea v-model="newMessage" id="newMessage" class="form-control" rows="3"></textarea>
                        <br>
                        <!-- Кнопка для сохранения сообщения -->
                        <button class="btn btn-outline-success" @click="sendMessage">Сохранить сообщение</button>


            </div>

        </div>



    </div>

</template>

<script>
import axios from 'axios';
export default {
    name: "UserMessages",
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
                user_phone: { value: "", keys: ["user_phone"] }
            },
            sent: '',
            city: '',
            selectedUser: '', // Новое свойство для хранения выбранного пользователя
            newMessage: '', // Новое свойство для хранения нового сообщения
            selectedEmails: [],
            selectedApp: ''
        };
    },
    mounted() {
        this.getUsers();
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

            // this.selectedEmails.forEach(selectedEmail => {
            //     window.alert("Selected Email: " + selectedEmail);
            // });
        },

        sendMessage() {
            if (!this.city || !this.selectedApp || !this.newMessage || !this.selectedEmails || this.selectedEmails.length === 0) {
                window.alert('Пожалуйста, проверьте выбор приложения, города  и ввод сообщения, а также убедитесь, что выбран хотя бы один email.');
                return;
            }

            const encodedNewMessage = encodeURIComponent(this.newMessage);

            axios.get(`/newMessage/${this.selectedEmails.join(',')}/${encodedNewMessage}/${this.selectedApp}/${this.city}`)
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
            this.selectedApp = '';
            this.newMessage = '';
            this.selectedEmails = []; // Очистить массив выбранных email после отправки
        },
        newMessageButton() {
            // Переход по адресу "/admin/new_message"
            this.$router.push('/admin/user_messages');
        }


    }
}
</script>

<style scoped>

</style>
