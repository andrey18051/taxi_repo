<template>

    <div class="container-fluid" style="overflow-x: auto;">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
            <h1 class="display-5">Partners</h1>
            <p class="lead">Partners' emails</p>
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
            <v-th sortKey="email"style="width: 300px">City</v-th>
            <v-th sortKey="phone"style="width: 200px">Phone</v-th>
            </thead>
            <tbody slot="body" slot-scope="{displayData}">
            <tr>
                <td><input class="form-input input-sm" style="width: 30px" v-model="filter.id.value" placeholder="Select by id"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filter.name.value" placeholder="Select by name"></td>
                <td><input class="form-input input-lg" style="width: 300px" v-model="filter.email.value"  placeholder="Select by email"></td>
                <td><input class="form-input input-lg" style="width: 300px" v-model="filter.city.value"  placeholder="Select by city"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filter.phone.value"  placeholder="Select by phone"></td>

                <td style="width: 100px"></td>
            </tr>
            <tr v-for="row in displayData" :key="row.id"  style="width: 30px" >
                <td>{{ row.id }}</td>


                <td><input id="name" class="form-control" style="width: 200px" v-model.text="row.name" readonly ></td>
                <td><input id="email" class="form-control" style="width: 300px" v-model.text="row.email" readonly ></td>
                <td><input id="city" class="form-control" style="width: 300px" v-model.text="row.city" readonly ></td>
                <td><input id="phone" class="form-control" style="width: 200px" v-model.text="row.phone" readonly></td>
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

                <div>

                    <label for="subject">Введите тему:</label>
                    <input id="subject" v-model="subject" class="form-control" style="width: 100%;">

                </div>
                <div>


                    <label for="newMessage">Введите новое сообщение:</label>
                    <textarea v-model="newMessage" id="newMessage" class="form-control" rows="3" style="width: 100%;"></textarea>

                    <br>
                    <!-- Кнопка для сохранения сообщения -->
                    <button class="btn btn-outline-success" @click="sendMessage">Сохранить сообщение</button>

                </div>
            </div>
        </div>

    </div>


</template>

<script>
import axios from 'axios';
export default {
    name: "PartnerMessages",
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
                city: { value: "", keys: ["city"] },
                phone: { value: "", keys: ["phone"] }
            },
            sent: '',
            city: '',
            selectedUser: '', // Новое свойство для хранения выбранного пользователя
            subject: 'інформація по корисним новинам для таксі від українських розробників', // Новое свойство для хранения нового сообщения
            newMessage: '', // Новое свойство для хранения нового сообщения
            selectedEmails: [],

        };
    },
    mounted() {
        this.getUsers();
    },



    methods: {
        getUsers() {
            axios.get('/partners/usersForEmail')
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
            let url = null;

                if (!this.subject || !this.newMessage || !this.selectedEmails || this.selectedEmails.length === 0) {
                    window.alert('Пожалуйста, проверьте ввод сообщения, а также убедитесь, что выбран хотя бы один email.');
                    return;
                } else{
                    const encodedNewMessage = encodeURIComponent(this.newMessage);
                    url = `https://m.easy-order-taxi.site/partners/newEmail/${this.selectedEmails.join(',')}/${this.subject}/${encodedNewMessage}`;


                }

            // window.alert( url);


            axios.get(url)
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
                    if (error.response) {
                        // Ошибка при получении ответа от сервера
                        console.error('Response error:', error.response.data);
                        window.alert("Произошла ошибка при обновлении данных. Ошибка сервера: " + error.response.status);
                    } else if (error.request) {
                        // Ошибка при выполнении запроса
                        console.error('Request error:', error.request);
                        window.alert("Произошла ошибка при выполнении запроса. Проверьте ваше подключение к сети." +  url );
                    } else {
                        // Другие ошибки
                        console.error('Error:', error.message);
                        window.alert("Произошла ошибка: " + error.message);
                    }
                });

            // Здесь вы можете использовать this.selectedUser и this.newMessage
            // для отправки сообщения, например, с использованием вашего бэкенда или других API-методов.
            // Очистите поля после успешной отправки, если это необходимо.

            this.newMessage = '';
            this.selectedEmails = []; // Очистить массив выбранных email после отправки
        },
        newMessageButton() {
            // Переход по адресу "/admin/new_message"
            this.$router.push('/admin/partner_email');
        }


    }
}
</script>

<style scoped>

</style>
