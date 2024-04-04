<template>

    <div class="container-fluid" style="overflow-x: auto;">
        <div class="pricing-header px-3 py-3 pt-md-5 pb-md-4 mx-auto text-center">
            <h1 class="display-5">Partners</h1>
            <p class="lead">Partners` emails</p>
        </div>
        <button class="btn btn-outline-primary" @click="newMessageButton()" style="margin-left: 5px">
           Новое сообщение
        </button>
        <button class="btn btn-outline-primary"  @click="sendMessage" style="margin-left: 5px">
           Повторить отправку
        </button>
        <v-table
            :data="messages"
            :filters="filters"
            :hideSortIcons="true"
            class="my-2 table table-striped"
            :currentPage.sync="currentPage"
            :pageSize="2"
            @totalPagesChanged="totalPages = $event"
        >
            <thead slot="head">
            <v-th sortKey="id" style="width: 30px">#</v-th>
            <v-th sortKey="user_id" style="width: 30px">user_id</v-th>
            <v-th sortKey="subject" style="width: 200px">subject</v-th>
            <v-th sortKey="text_message" style="width: 200px">text_message</v-th>
            <v-th sortKey="sent_message_info" style="width: 30px">sent</v-th>
            <v-th sortKey="updated_at" style="width: 200px">updated_at</v-th>
            <v-th sortKey="name" style="width: 200px">Name</v-th>
            <v-th sortKey="email"style="width: 200px">Email</v-th>
            <v-th sortKey="phone"style="width: 150px">Phone</v-th>
            </thead>
            <tbody slot="body" slot-scope="{displayData}">
            <tr>
                <td><input class="form-input input-sm" style="width: 30px" v-model="filters.id.value" placeholder="Select by id"></td>
                <td><input class="form-input input-sm" style="width: 30px" v-model="filters.user_id.value" placeholder="Select by user_id"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filters.subject.value" placeholder="Select by subject"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filters.text_message.value" placeholder="Select by text_message"></td>
                <td style="width: 30px"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filters.updated_at.value" placeholder="Select by updated_at"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filters.name.value" placeholder="Select by name"></td>
                <td><input class="form-input input-lg" style="width: 200px" v-model="filters.email.value"  placeholder="Select by email"></td>
                <td><input class="form-input input-lg" style="width: 150px" v-model="filters.phone.value"  placeholder="Select by phone"></td>

                <td style="width: 100px"></td>
            </tr>
            <tr v-for="row in displayData" :key="row.id"  style="width: 30px" >
                <td>{{ row.id }}</td>
                <td>{{ row.user_id }}</td>

                <td><input id="subject" class="form-control" style="width: 200px" v-model.text="row.subject"></td>
                <td>
                    <textarea id="text_message" class="form-control" style="width: 200px" v-model="row.text_message" required rows="3"></textarea>
                </td>
                <td>
                    <input type="checkbox" id="sent_message_info" style="width: 30px" v-model="row.sent_message_info" @change="handleCheckboxChange(row)">
                </td>
                <td><input id="updated_at" class="form-control" style="width: 200px" v-model.text="row.updated_at" readonly></td>
                <td><input id="name" class="form-control" style="width: 200px" v-model.text="row.name" readonly ></td>
                <td><input id="email" class="form-control" style="width: 200px" v-model.text="row.email" readonly ></td>
                <td><input id="phone" class="form-control" style="width: 150px" v-model.text="row.phone" readonly></td>

                <td>
                    <div  class="container-fluid" style="width:100px">
                        <div class="btn-group" role="group">
                            <button class="btn btn-success" @click="updateMessage(
                                     row.id,
                                     row.text_message,
                                     row.sent_message_info
                                    )" style="margin-left: 5px">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-save2" viewBox="0 0 16 16">
                                    <path d="M2 1a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1H9.5a1 1 0 0 0-1 1v4.5h2a.5.5 0 0 1 .354.854l-2.5 2.5a.5.5 0 0 1-.708 0l-2.5-2.5A.5.5 0 0 1 5.5 6.5h2V2a2 2 0 0 1 2-2H14a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V2a2 2 0 0 1 2-2h2.5a.5.5 0 0 1 0 1H2z"/>
                                </svg>
                            </button>
                            <button class="btn btn-danger" @click="destroyMessage(row.id)" style="margin-left: 5px">
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
    name: "PartnerEmail",
    data() {
        return {
            loading: true,
            messages: [],
            currentPage: 1,
            totalPages: 0,
            maxPageLinks: 10,

            filters: {
                id: { value: "", keys: ["id"] },
                user_id: { value: "", keys: ["user_id"] },
                subject: { value: "", keys: ["subject"] },
                text_message: { value: "", keys: ["text_message"] },
                sent_message_info: { value: "", keys: ["sent_message_info"] },
                created_at: { value: "", keys: ["created_at"] },
                updated_at: { value: "", keys: ["updated_at"] },
                name: { value: "", keys: ["name"] },
                email: { value: "", keys: ["email"] },
                phone: { value: "", keys: ["phone"] }
            },

            selectedUser: '', // Новое свойство для хранения выбранного пользователя
            newMessage: '', // Новое свойство для хранения нового сообщения
            selectedEmails: []
        };
    },
    mounted() {
        this.getMessages();
    },



    methods: {
       getMessages() {
            axios.get('/partners/showEmailsAll')
                .then(res => {
                    // Выводим данные в консоль для проверки
                    console.log('Data received:', res.data);

                    // Присваиваем данные переменной messages
                    this.messages = res.data;

                    // Устанавливаем флаг loading в false
                    this.loading = false;
                })
                .catch(error => {
                    console.error('Error fetching data:', error);
                });
        },

        // Удаление сообщения по id
        destroyMessage(id) {
            axios.delete(`/partners/emails/destroy/${id}`)
                .then(response => {
                    // Проверяем успешность операции
                    if (response.status === 204) {
                        // Фильтруем массив, оставляя только те сообщения, у которых id не равен удаляемому id
                        this.messages = this.messages.filter(message => message.id !== id);
                        window.alert("Данные успешно удалены");
                    } else {
                        window.alert("Произошла ошибка при удалении данных");
                    }
                })
                .catch(error => {
                    console.error(error);
                    window.alert("Произошла ошибка при удалении данных");
                });
        },

// Обновление сообщения по id
        updateMessage(id, text_message, sent_message_info) {
            const encodedNewMessage = encodeURIComponent(text_message);
            axios.get(`/partners/emails/update/${id}/${encodedNewMessage}/${sent_message_info}`)
                .then(response => {
                    // Проверяем успешность операции
                    if (response.status === 200) {
                        window.alert("Данные успешно обновлены");
                    } else {
                        window.alert("Произошла ошибка при обновлении данных " + response.status);
                    }
                })
                .catch(error => {
                    console.error(error);
                    window.alert("Произошла ошибка при обновлении данных" + error);
                });
        },
        newMessageButton() {
            this.$router.push('/admin/new-partner-email');
        },
        handleCheckboxChange(row) {
            const id = row.id;
            if (row.sent_message_info) {
                // window.alert("Данные успешно отправлены" + id)
                // Если галочка установлена, добавляем email в массив
                if (!this.selectedEmails.includes(id)) {
                    this.selectedEmails.push(id);
                }


            } else {
                // Если галочка снята, удаляем email из массива
                this.selectedEmails = this.selectedEmails.filter(item => item !== id);
            }

            // this.selectedEmails.forEach(selectedEmail => {
            //     window.alert("Selected Email: " + selectedEmail);
            // });
        },

        sendMessage() {
            if (!this.selectedEmails || this.selectedEmails.length === 0) {
                window.alert('Пожалуйста, проверьте , что выбран хотя бы одно сообщение.');
                return;
            }

            const url = `/partners/repeatEmail/${this.selectedEmails.join(',')}`
            axios.get(`/partners/repeatEmail/${this.selectedEmails.join(',')}`)
                .then(response => {
                    // Проверяем успешность операции
                    if (response.status === 200) {
                        window.alert("Данные успешно отправлены");
                        window.location.reload();
                    } else {
                        window.alert("Произошла ошибка при обновлении данных " + response.status);
                    }
                })
                .catch(error => {
                    console.error(error);
                    window.alert("Произошла ошибка при обновлении данных" + url);
                });
            this.selectedEmails = []; // Очистить массив выбранных email после отправки
        },
    }
}
</script>

<style scoped>

</style>
