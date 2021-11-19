<template>
    <div>

        <b-form>
            <div class="d-block text-center">

                <slot v-if="this.ref_id=='add_payment'">
                    <h3>Дополнительный платеж</h3>
                    <b-container fluid>
                        <b-row class="">
                            <b-col sm="5">
                                <label> Дата платежа: </label>
                            </b-col>
                            <b-col sm="5">
                                <b-form-input v-model="rid.payment_date" type="date"></b-form-input>
                            </b-col>
                        </b-row>
                        <b-row class="">
                            <b-col sm="5">
                                <label> Сумма без процентов: </label>
                            </b-col>
                            <b-col sm="5">
                                <b-form-input v-model="rid.sum_payment" v-inputmask-rub
                                              placeholder="00.0"></b-form-input>
                            </b-col>
                        </b-row>
                    </b-container>
                </slot>

                <slot v-else-if="this.ref_id=='edit_sch_row'">
                    <h3>Редактировать платеж</h3>
                    <b-container fluid>
                        <b-row class="">
                            <b-col sm="5">
                                <label> Дата платежа: </label>
                            </b-col>
                            <b-col sm="5">
                                <b-form-input v-model="rid.payment_date" type="date"></b-form-input>
                            </b-col>
                        </b-row>


                        <b-row class="">
                            <b-col sm="5">
                                <label> Остаток выплат: </label>
                            </b-col>
                            <b-col sm="5">
                                <b-form-input v-model="rid.total_payings" v-inputmask-rub
                                              placeholder="00.0"></b-form-input>
                            </b-col>
                        </b-row>


                        <b-row class="">
                            <b-col sm="5">
                                <label> Сумма без процентов: </label>
                            </b-col>
                            <b-col sm="5">
                                <b-form-input v-model="rid.sum_payment" v-inputmask-rub
                                              placeholder="00.0"></b-form-input>
                            </b-col>
                        </b-row>
                        <b-row class="">
                            <b-col sm="5">
                                <label> Сумма процентов: </label>
                            </b-col>
                            <b-col sm="5">
                                <b-form-input v-model="rid.sum_prs" v-inputmask-rub placeholder="00.0"></b-form-input>
                            </b-col>
                        </b-row>
                        <b-row class="">
                            <b-col sm="5">
                                <label> Сумма с процентами: </label>
                            </b-col>
                            <b-col sm="5">
                                <b-form-input v-model="rid.sum_total" v-inputmask-rub placeholder="00.0"></b-form-input>
                            </b-col>
                        </b-row>


                    </b-container>
                </slot>
                <slot v-else-if="this.ref_id=='delete_rows'">
                    <h3>Удалить выбранные</h3>
                    Уверенны, что хотите удалить платежи №№: {{ SelectedNString }}




                </slot>

                <!--   <b-button class="mt-3" variant="outline-danger" block @click="$bvModal.hide('edit_sch_row')" >Отмена</b-button>
                   <b-button class="mt-2" variant="outline-success" block @click="">Внести изменения</b-button>-->

                <b-button class="mt-3" variant="outline-success" block @click="Ok()">ok</b-button>

            </div>

        </b-form>


    </div>
</template>

<script>


    export default {

        props: {
            ref_id: null,
            items: null,
            rid: {payment_date: ''},
            fields: {},
            cdata: null,
        },
        data() {
            return {}
        },
        created: function () {

        },
        watch: {},
        computed: {

            SelectedIdsString: function() {

                return _.map(this.rid, 'id').toString()
            },

            SelectedNString: function() {
                return _.map(this.rid, 'n').toString()
            },
        },
        methods: {

            Ok() {
                this.$bvModal.hide(this.ref_id)

                    switch (this.ref_id) {
                        case 'add_payment' :  this.AddPayment();
                            break;
                        case 'edit_sch_row' : this.EditRow();
                            break;
                        case 'delete_rows' : this.DeleteRows();
                            break;
                    }


            },
            DeleteRows() {
                //console.log('1111:', this.cdata.schedule_created);
                if(!this.cdata.schedule_created) {
                    this.$emit('updateItems', _.differenceBy( this.items, this.rid , 'n') );
                    return; // пока график не создан, редактировать на сервере нечего
                }


                app.start_spin();
                axios.post('api/deleteSchRows', {'del' : this.SelectedIdsString } )   //
                    .then(response => {
                        app.stop_spin();
                        if (response.data.error) {
                            app.showAlert(response.data.error, 'danger');

                        } else {
                            app.loadData(response.data);
                           // if(this.items.length == this.rid.length)
                            // if(!response.data.schedule_created) { this.$emit('schDeleted', 0   );  }

                            if(response.data.msg)  app.showAlert(response.data.msg, 'danger');
                            app.showAlert('График платежей обновлен!', 'success');


                        }


                    }).catch(function (error) {
                    app.showAlert('Ошибка сервера. Обратитесь к администратору.' + error, 'danger')

                });
            },
            EditRow() {
                //console.log(43754657798, this );
                if(!this.cdata.schedule_created) return; // пока график не создан, редактировать на сервере нечего
                app.start_spin();
                axios.post('api/editSchRow', this.rid)   //
                    .then(response => {
                        app.stop_spin();
                        if (response.data.error) {
                            app.showAlert(response.data.error, 'danger');

                        } else {
                            app.loadData(response.data);
                            app.showAlert('График платежей обновлен!', 'success');


                        }


                    }).catch(function (error) {
                    app.showAlert('Ошибка сервера. Обратитесь к администратору.' + error, 'danger')

                });
            },
            AddPayment() {

                if (this.cdata.schedule_created) {
                    app.start_spin();

                    axios.post('api/addPayment', this.rid)   //
                        .then(response => {
                            app.stop_spin();
                            if (response.data.error) {
                                app.showAlert(response.data.error, 'danger');

                            } else {
                                app.loadData(response.data);
                                app.showAlert('График платежей записан!', 'success');


                            }


                        }).catch(function (error) {
                        app.showAlert('Ошибка сервера. Обратитесь к администратору.' + error, 'danger')

                    });

                }


            }


        },

        mounted() {


        },

    }
</script>
