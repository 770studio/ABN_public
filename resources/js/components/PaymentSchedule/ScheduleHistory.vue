<template>
    <div >



        <b-card   title="История графика платежей" bg-variant="light">
           действие над графиком : {{ this.cdata.sch_history[this.nsch].event }},
            дата :  {{ this.cdata.sch_history[this.nsch].updated_at }},
            основание: {{ this.cdata.bailouts[this.cdata.sch_history[this.nsch].bailout] }},
            менеджер:  {{  this.cdata.sch_history[this.nsch].user.name   }}
        </b-card>


        <b-card    bg-variant="light">
        <b-table striped   hover :items="items" :fields="fields"
                 responsive="sm"
                 :busy="cdata.loading"
                 :tbody-tr-class="rowClass"
                 id="history-table"
                 :current-page="currentPage"


        >
            <div slot="table-busy" class="text-center text-danger my-2">
                <b-spinner class="align-middle"></b-spinner>
                <strong>Работаем...</strong>
            </div>


        </b-table>


            <b-pagination
                    v-model="currentPage"
                    aria-controls="history-table"
                    :total-rows="rows"
                    :per-page="perPage"
                    @change="OnPageChange"
            ></b-pagination>

        </b-card>

    </div>
</template>

<script>
    export default {

        props:{
            cdata:  null
        },

        data() {

            return {
                rows: 1,
                perPage: 1,
                currentPage:1,
                items: [],
                nsch: 0,
                fields: [
                    {
                        key: 'n',
                        sortable: false,
                        label: '№ п/п',
                    },
                    {
                        key: 'payment_date',
                        sortable: false,
                        label: 'Дата платежа'
                    },
                    {
                        key: 'total_payings',
                        sortable: false,
                        label: 'Остаток выплат',
                        formatter: (value, key, item) => {
                            return format_currency(value )
                        }
                    },
                    {
                        key: 'sum_payment',
                        sortable: false,
                        label: 'Сумма без процентов',
                        formatter: (value, key, item) => {
                            return format_currency(value )
                        }
                    },
                    {
                        key: 'sum_prs',
                        sortable: false,
                        label: 'Сумма процентов',
                        formatter: (value, key, item) => {
                            return format_currency(value )
                        }
                    },

                    {
                        key: 'sum_total',
                        sortable: false,
                        label: 'Сумма с процентами',
                        formatter: (value, key, item) => {
                            return format_currency(value )
                        }
                    },

                ],
            }
        },

        mounted()   {

            // кол-во страниц = rows / perPage
            if(this.cdata.sch_history) {
                this.rows = this.cdata.sch_history.length
                this.items = this.getSchItems( 0 );
            } else {
                app.showAlert( 'История изменений пока пуста' ,  'warning' );
            }




        },
        created: function() {

        },
        computed: {



            loading() {

                return this.cdata.loading ;

            },



        },
        watch: {


        },

        methods: {
            OnPageChange(pn) {
                 this.nsch = pn-1;
                this.items = this.getSchItems( this.nsch );
            },
            getSchItems(n) {

                if( this.cdata.sch_history[n] ) {
                    return _.sortBy(this.cdata.sch_history[n].dump, ['n' ]);
                }
               return {};
            },
            rowClass(item, type) {
                if (!item) return
                if (item.added == 1) return 'table-warning'
            },

        }
    }
</script>
