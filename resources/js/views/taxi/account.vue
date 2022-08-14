<template>
<div>
    <p>{{this.users['id']}}</p>
    <p>{{this.users['roles']}}</p>
    <p>{{this.users['version']}}</p>
    <p>{{this.users['login']}}</p>
    <p>{{this.users['user_full_name']}}</p>
    <p>{{this.users['user_first_name']}}</p>
    <p>{{this.users['user_last_name']}}</p>
    <p>{{this.users['user_middle_name']}}</p>
    <p>{{this.users['user_phone']}}</p>
    <p>{{this.users['user_balance']}}</p>
    <p>{{this.users['route_address_from']}}</p>
    <p>{{this.users['route_address_number_from']}}</p>
    <p>{{this.users['route_address_entrance_from']}}</p>
    <p>{{this.users['route_address_apartment_from']}}</p>
    <p>{{this.users['discount']['value']}}</p>
    <p>{{this.users['discount']['unit']}}</p>

    <p>{{this.users['client_bonuses']}}</p>
    <p>{{this.users['cclient_sub_cards']}}</p>
    <p>{{this.users['payment_type']}}</p>
    <p>{{this.users['corporate_account']}}</p>
    <p>{{this.users}}</p>


</div>

</template>

<script>
import axios from 'axios';
export default {
    name: "account",
    data: () => ({
        loading: true,
        users: [],
        currentPage: 1,
        totalPages: 0,
        filters: {
            id: { value: "", keys: ["id"] },

        }
    }),
    mounted() {
        this.getUsers()
    },
    methods: {
        getUsers() {
            axios.get('/axios/account')
                .then(
                    res => {
                        this.users = res.data;
                        this.loading = false;
                    }
                )
        },
    }
}
</script>

<style scoped>

</style>
