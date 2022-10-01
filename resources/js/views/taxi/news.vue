<template>


        <div class="container" style="display: block; overflow: auto; max-width: 100%;">

                <v-table
                :data="news"
                :filters="filters"
                :hideSortIcons="true"
                class="my-2 table table-striped"
                :currentPage.sync="currentPage"
                :pageSize="1"
                @totalPagesChanged="totalPages = $event"
                >
                <tbody slot="body" slot-scope="{displayData}">
                    <thead slot="head">
                      <v-th sortKey="id" defaultSort="desc" ></v-th>

                    </thead>
                    <tr v-for="row in displayData" :key="row.short">
                        <div class="container">
                        <div class="row">
                            <div class="col-12">
                                <td>
                                   <b> {{row.short}}</b>

                                </td>

                            </div>
                            <div class="col-12">
                                <td>
                                    <div class="container">
                                        <h5 class="marquee"><span>
                                             {{row.full}}
                                         </span>
                                        </h5>
                                    </div>
                                </td>

                            </div>
                        </div>
                        </div>
                    </tr>

                </tbody>
                 </v-table>
            <div class="container">
                <div class="row">
                    <div class="col-11 text-center">
                    <smart-pagination
                        :currentPage.sync="currentPage"
                        :totalPages="totalPages"
                        :maxPageLinks="maxPageLinks"
                    />
                    </div>
                </div>
            </div>
       </div>

</template>



<script>
import axios from 'axios';
export default {
    name: "News",
    data: () => ({
        loading: true,
        news: [],
        currentPage: 1,
        totalPages: 0,
        maxPageLinks: 15,
        filters: {
            short: { value: "", keys: ["short"] },
            }
    }),
    mounted() {
        this.getNews()
    },
    methods: {
        getNews() {
            axios.get('/news-short')
                .then(
                    res => {
                        this.news = res.data;
                        this.loading = false;
                    }
                )
        }
    }
}


</script>

<style scoped>

</style>
