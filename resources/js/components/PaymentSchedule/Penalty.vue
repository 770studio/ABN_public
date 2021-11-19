<template>
    <div >
        <b-modal ref="edit_penalty_row" id="edit_penalty_row" hide-footer title="Корректировать начисления">

            <penalty-edit-row v-bind:item= "selected"   v-bind:cdata= "this.cdata" v-bind:penalty_status_options= "penalty_status_options"    ></penalty-edit-row>

        </b-modal>

        <b-modal ref="add_penalty_row" id="add_penalty_row" hide-footer title="Добавить начисление">

            <penalty-edit-row v-bind:add="true"  v-bind:item= "{penalty_date: null, id: null, penalty_sum:0, comments: '', update_reason: null}"   v-bind:cdata= "this.cdata" v-bind:penalty_status_options= "penalty_status_options"    ></penalty-edit-row>

        </b-modal>


        <b-form @submit="onSubmit" @reset="onReset" >



        <b-card  title="Параметры начисления пеней" bg-variant="light">

            <b-row>
                <b-col md="auto" > Способ начисления:
                        <b-form-select size="sm"  v-model="cdata.penalty_type"  :state="cdata.penalty_type>0"  :options="penalty_type_options" ></b-form-select>
                </b-col>

                <b-col md="auto" v-show="cdata.penalty_type!=2 && cdata.penalty_type!=3" >  Величина пени : <b-form-input v-model="cdata.penalty_value" :state="cdata.penalty_value>0 || (cdata.penalty_type!=1 && cdata.penalty_type!=4)"  type="number"  min="0"  size="sm"  ></b-form-input></b-col>

                <b-col md="auto" > </b-col>
                <b-col md="auto" > </b-col>

            </b-row>

                <br>
                  <!--  <b-button variant="warning"  type="submit"  >Сохранить</b-button> &nbsp;
-->

        </b-card>


        <b-card    bg-variant="light">



        <b-table striped hover :items="items" :fields="fields"
                 selectable
                 :select-mode='rowSelectMode'
                 @row-selected="onRowSelected"
                  responsive="sm"
                 :busy="cdata.loading"
                 stacked="md"
                 :current-page="currentPage"
                 :per-page="perPage"
                 caption-top
                 ref="PenaltyTable"

        >
            <template slot="table-caption">Начисления пени</template>

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

            <b-button variant="danger" @click="add_penalty"  v-show="this.cdata.penalty">Добавить начисление</b-button>

         </b-card>
        </b-form>


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
                 penalty_type_options: [
                    { value: 0, text: 'Выберите способ '},
                    { value: 1, text: 'Величина пени в сумме' },
                    { value: 2, text: '1/360  ставки рефинансирования' },
                    { value: 3, text: '1/300  ставки рефинансирования' },
                    { value: 4, text: 'Величина пени в процентах' },
                ],
                penalty_status_options: [
                    { value: 0, text: 'не оплачено'},
                    { value: 1, text: 'оплачено' },
                    { value: 2, text: 'оплачено частично' },
                    { value: 3, text: 'отменено' },
                ],

                totalRows: 1,
                currentPage: 1,
                perPage: 20,


                items: [

                ],


                fields: [
                    {
                        key: 'n',
                        sortable: false,
                        label: '№ п/п',
                    },

                    {
                        key: 'penalty_date',
                        sortable: false,
                        label: 'Дата начисления'
                    },
                    {
                        key: 'overdue_date',
                        sortable: false,
                        label: 'Дата наступления просрочки'
                    },
                    {
                        key: 'overdue_sum',
                        sortable: false,
                        label: 'Сумма начисления',
                        formatter: (value, key, item) => {
                            return  format_currency(value )
                        }
                    },
                    {
                        key: 'overdue_days',
                        sortable: false,
                        label: 'Просрочка, дней',

                    },

                    {
                        key: 'penalty_sum',
                        sortable: false,
                        label: 'Начислено пени, руб',
                        formatter: (value, key, item) => {
                            return  format_currency(value )
                        }
                    },

                    {
                        key: 'status',
                        sortable: false,
                        label: 'Статус',
                        formatter: (value, key, item) => {
                            return _.find(this.penalty_status_options, { 'value': value  }).text
                        }
                    },

                    {
                        key: 'comments',
                        sortable: false,
                        label: 'Примечание',

                    },
                ],
            }
        },

        mounted()   {

                this.$root.$on('bv::modal::show', (bvEvent, modalId) => {
                    if(modalId == 'edit_penalty_row' ) {                     // передотвращение переоткрывания диалога
                        this.$refs.PenaltyTable.clearSelected()
                    }


                })


        },
        created: function() {


        },
        computed: {
            formChanged () {
               // return  this.cdata
            },
            penaltyChanged () {
                 return  this.cdata.penalty
            },

        },
        watch: {
            formChanged: {
                handler( ){
                  //  this.$emit('updateform', this.form   );
                },
                deep: true
            },
            penaltyChanged () {
                this.items =  this.cdata.penalty
                this.totalRows = this.items ? this.cdata.penalty.length : 0
             },
        },

        methods: {
            onSubmit(evt) {
                evt.preventDefault()

                // validation:

            },
            onReset(evt) {
                if(evt) evt.preventDefault()
                // Reset our form values


            },
            onRowSelected(item) {

                if( item.length ) this.selected = item[0]
                app.$bvModal.show('edit_penalty_row')




            },

            add_penalty() {
                //this.items =  this.cdata.penalty
                app.$bvModal.show('add_penalty_row')
            },

        }
    }
</script>
