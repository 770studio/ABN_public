<template>
    <div >








        <b-table striped hover :items="dataProvider" :fields="fields" class="mt-3" outlined
                 selectable
                 :select-mode='rowSelectMode'
                 :no-local-sorting=true
                 @row-selected="onRowSelected"
                  responsive="sm"
                 :busy="isBusy"

                 :current-page="currentPage"
                 :per-page="perPage"
                 caption-top
                 ref="RefRateHistoryTable"
                 :sort-by.sync="sortBy"
                 :sort-desc.sync="sortDesc"
        >


            <div slot="table-busy" class="text-center text-danger my-2">
                <b-spinner class="align-middle"></b-spinner>
                <strong>Работаем...</strong>
            </div>
            <template slot="n" slot-scope="data">
                {{ data.index + 1 }}
            </template>

        </b-table>
            <b-row>
                <b-col md="1" class="my-1 mb-3">
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="totalRows"
                        :per-page="perPage"
                        align="fill"
                        size="sm"
                        class="my-0"
                    ></b-pagination>
                </b-col>
            </b-row>






    </div>
</template>

<script>
    export default {

        props:{
            cdata:  null,
        },
        data() {

            return {
                selected: [],
                rowSelectMode: 'single',
                isBusy: false,

                totalRows: 1,
                currentPage: 1,
                perPage: 20,
                sortBy: 'id',
                sortDesc: false,





                fields: [
                    {
                        key: 'id',
                        sortable: false,
                        label: '№ п/п',
                    },

                    {
                        key: 'rate',
                        sortable: true,
                        label: 'Ставка, %'
                    },
                    {
                        key: 'start_date',
                        sortable: true,
                        label: 'Действует с:'
                    },
                    {
                        key: 'updated_at',
                        sortable: true,
                        label: 'Дата и время внесения записи',

                    },

                    {
                        key: 'user_name',
                        sortable: true,
                        label: 'Автор',

                    },

                ],
            }
        },

        mounted()   {



        },
        created: function() {


        },
        computed: {

            onSort () {
               //  return  this.cdata.penalty
            },

        },
        watch: {

            onSort () {
               // this.items =  this.cdata.penalty
                //this.totalRows = this.cdata.penalty.length
             },
        },

        methods: {

            onRowSelected(item) {



            },

            dataProvider (ctx) { console.log(ctx);
                // Here we don't set isBusy prop, so busy state will be
                // handled by table itself
                // this.isBusy = true
                //let promise = axios.get('api/getRefRateHistory', {'s':1, 'a':3})
                const promise = axios.post('api/getRefRateHistory',  ctx )

                return promise.then((data) => {
                   // console.log(data, data.items, data.data);
                    const items =  data.data
                    // Here we could override the busy state, setting isBusy to false
                    // this.isBusy = false
                    return(items)
                }).catch(error => {
                    // Here we could override the busy state, setting isBusy to false
                    // this.isBusy = false
                    // Returning an empty array, allows table to correctly handle
                    // internal busy state in case of error
                    return []
                })
            }

        }
    }
</script>
