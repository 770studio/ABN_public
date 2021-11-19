<template>
    <div >


        <b-modal ref="addAssignmentModal" id="addAssignmentModal" hide-footer  title="Добавить переуступку">

            <add-assignment  v-bind:cdata= "this.cdata"   ></add-assignment>


        </b-modal>








        <b-card   title="Переуступка прав" bg-variant="light">
            <b-button class="addRowBtn" variant="warning" @click="addNewAssignment">Добавить</b-button>

            <b-table striped   hover :items="items" :fields="fields"

                 responsive="sm"
                 :busy="cdata.loading"


        >
                <template slot="n" slot-scope="data">
                    {{ data.index + 1 }}
                </template>

                <template slot="drop" slot-scope="row">
                    <b-button size="sm" @click="row.toggleDetails">Удалить</b-button>
                </template>

            <div slot="table-busy" class="text-center text-danger my-2">
                <b-spinner class="align-middle"></b-spinner>
                <strong>Работаем...</strong>
            </div>


        </b-table>

        </b-card>

    </div>
</template>

<script>
    export default {



        props:{
            cdata: {
                type: Object,
                default: function () {
                    return {}
                }
            },
        },

        data() {

            return {
                 items: [

                ],

                fields: [
                    {
                        key: 'n',
                        sortable: false,
                        label: '№ п/п',
                    },
                    {
                        key: 'assignor',
                        sortable: false,
                        label: 'От кого'
                    },
                    {
                        key: 'cessionary',
                        sortable: false,
                        label: 'Кому',

                    },
                    {
                        key: 'base',
                        sortable: false,
                        label: 'На основании',

                    },
                    {
                        key: 'date',
                        sortable: false,
                        label: 'Дата',

                    },

                    {
                        key: 'comments',
                        sortable: false,
                        label: 'Комментарий',

                    },
                    {
                        key: 'drop',
                        sortable: false,
                        label: 'Удалить',

                    },

                ],
            }
        },

        mounted()   {



        },
        created: function() {

        },
        computed: {

            onReload() {

                return this.cdata.assignment ;

            },


        },
        watch: {


            onReload() {
                 this.items = this.cdata.assignment;
            },




        },

        methods: {
                //нажали Добавить
            addNewAssignment() {

                // показываем модальное окно
                app.$bvModal.show('addAssignmentModal')

            },



            onSubmit(evt) {
                evt.preventDefault()


                app.start_spin();

                axios.post('api/getAssignment',  { ...this.form,  ...this.cdata } )   //
                    .then(response => {
                        app.stop_spin();
                        if(response.data.error) {
                            app.showAlert ( response.data.error , 'danger' );

                        } else {

                            alert(response.data);
                            app.loadData(response.data);
                            app.showAlert( 'График платежей записан!' ,  'success' );


                        }



                    }) .catch(function(error){
                    app.showAlert ( 'Ошибка сервера. Обратитесь к администратору.' + error ,   'danger' )

                });
            },


        }
    }
</script>
