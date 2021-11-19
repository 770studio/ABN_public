<template>
    <div >

            <b-form>
            <div class="d-block text-center">

                <b-container fluid>
                    <b-row class="" >
                        <b-col sm="5">
                            <label > Дата начисления пени: </label>
                        </b-col>
                        <b-col sm="5">
                            <b-form-input v-model="_Item.penalty_date"  type="date"   ></b-form-input>
                        </b-col>
                    </b-row>





                    <b-row class="" v-show="!_Item.id" >
                        <b-col sm="5">
                            <label > Дата наступления просрочки: </label>
                        </b-col>
                        <b-col sm="5">
                            <b-form-input v-model="_Item.overdue_date"  type="date"   ></b-form-input>
                        </b-col>
                    </b-row>

                    <b-row class="" v-show="!_Item.id" >
                        <b-col sm="5">
                            <label > Сумма начисления: </label>
                        </b-col>
                        <b-col sm="5">
                            <b-form-input v-model="_Item.overdue_sum"  v-inputmask-rub  placeholder="00.0"    ></b-form-input>
                        </b-col>
                    </b-row>
                    <b-row class="" v-show="!_Item.id" >
                        <b-col sm="5">
                            <label > Просрочка, дней: </label>
                        </b-col>
                        <b-col sm="5">
                            <b-form-input v-model="_Item.overdue_days"  type="number"   ></b-form-input>
                        </b-col>
                    </b-row>






                    <b-row class=""  >
                        <b-col sm="5">
                            <label >  Сумма начисления пени: </label>
                        </b-col>
                        <b-col sm="5">
                            <b-form-input v-model="_Item.penalty_sum"   v-inputmask-rub  placeholder="00.0"    ></b-form-input>
                        </b-col>
                    </b-row>
                    <b-row class=""  >
                        <b-col sm="5">
                            <label >  Статус: </label>
                        </b-col>
                        <b-col sm="5">
                            <b-form-select size="sm"  v-model="_Item.status"  @change="OnStatusChange"  :options="penalty_status_options"  ></b-form-select>
                        </b-col>
                    </b-row>
                     <b-row class=""  >
                        <b-col sm="5">
                            <label >  Примечание: </label>
                        </b-col>
                        <b-col sm="5">

                            <b-form-textarea v-model="_Item.comments"
                                             id="textarea-small"
                                             size="sm"
                                             rows="3"
                                             max-rows="6"
                            ></b-form-textarea>




                        </b-col>
                    </b-row>
                    <b-row class=""  >
                        <b-col sm="5">
                            <label >  Основание: </label>
                        </b-col>
                        <b-col sm="5">
                            <b-form-select size="sm"  v-model="mod_reason"  :state="mod_reason>0"  :options="mod_reason_options"  ></b-form-select>
                        </b-col>
                    </b-row>

                    <b-row class=""  >
                        <b-col sm="5">
                            <label >  Отсрочка:  </label>
                        </b-col>
                        <b-col sm="5">
                            <b-form-checkbox  switch
                                    id="postponed"
                                    v-model="_Item.postponed"
                                    name="postponed"
                                    @change="OnPPChange"
                                              value="1"
                                              unchecked-value="0"
                            >

                            </b-form-checkbox>
                        </b-col>
                    </b-row>




                </b-container>




             <!--   <b-button class="mt-3" variant="outline-danger" block @click="$bvModal.hide('edit_sch_row')" >Отмена</b-button>
                <b-button class="mt-2" variant="outline-success" block @click="">Внести изменения</b-button>-->

                <b-button class="mt-3" variant="outline-success" block  @click="update" >ok</b-button>

            </div>

             </b-form>



    </div>
</template>

<script>



    export default {

        props:{
            add:null,
            cdata: null,
            penalty_status_options: null,
            item:  {},
         },
        data() {
            return {

                mod_reason : 0,
                mod_reason_options : [
                    { value: 1, text: 'заявление об отсрочке платежа' },
                    { value: 2, text: 'квитанция об оплате' },
                    { value: 3, text: 'служебная записка' },
                    { value: 4, text: 'заявление о досрочном погашении' },
                    { value: 5, text: 'другое' },

                 ]

             }
        },
        created: function() {
            this._Item = this.item
        },
        watch: {

        },
        computed: {

        },
        methods: {
            OnStatusChange(selected) {
                this.OnPPChange(this._Item.postponed) // проверка
            },
            OnPPChange(checked) {
                if(checked && this._Item.status != 3 ) {
                    // проверка  статус отменено + отсрочка

                    app.showAlert ( 'Для отсрочки статус должен быть в значении "отменено" ' ,   'warning' )
                        this.$nextTick(function () {
                            this._Item.postponed = 0
                        })

                }
            },

            update() {

                if(!this.mod_reason) {
                    app.showAlert ( 'Не выбрано основание для внесения изменений', 'warning' );
                    return;
                }

                this.cdata.loading  = true
                this._Item.update_reason = this.mod_reason
                if(this.add) {
                    this._Item.lead_id = this.cdata.lead_id

                }

                 axios.post( 'api/updatePenalty',
                    _.pick(this._Item, ['comments' , 'status', 'update_reason', 'id', 'postponed' , 'penalty_sum' , 'penalty_date', 'lead_id', 'overdue_date', 'overdue_sum', 'overdue_days'  ])
                ).then(response => {
                    this.cdata.loading  = false

                    if(response.data.error) {
                        app.showAlert ( response.data.error , 'danger' );

                        return;
                    }
                    if(  response.data ) {
                        if(this.add) {
                           //this.cdata.penalty.push(this._Item)
                            app.loadData(response.data);
                            app.$bvModal.hide('add_penalty_row')


                        }
                        else app.$bvModal.hide('edit_penalty_row')

                        app.showAlert ( 'Корректировка успешно внесена' ,   'success' )

                    } else {
                        app.showAlert ( 'Ошибка сервера. Попробуйте еше раз.' ,   'warning' )
                    }


                })
                    .catch(function(error){
                        app.showAlert ( 'Ошибка сервера. Обратитесь к администратору.' + error ,   'danger' )

                });

            }

        } ,

        mounted()  {


        },

    }
</script>
