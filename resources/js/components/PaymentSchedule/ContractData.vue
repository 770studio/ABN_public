<template>
    <div >
        <b-container fluid>
        <b-card no-body>
        <b-container fluid class="bv-example-row" id="pContractData">

            <b-form @submit="onSubmit"    >

            <b-row>
                <b-col md="2" > Сумма договора без рассрочки:  <b-form-input v-inputmask-rub size="sm" v-model="cdata.contract_sum"  readonly> </b-form-input>  </b-col>
                <b-col md="2" > Ставка НДС:
                    <b-form-select  v-model="cdata.nds" :options="nds_options" size="sm" required ></b-form-select>
                </b-col>

                <b-col md="2" > Сумма НДС:  <b-form-input v-inputmask-rub  size="sm" v-model="nds_sum"     readonly>  </b-form-input>   </b-col>
                <b-col md="auto" >
                </b-col>

            </b-row>

            <b-row>
                <b-col  md="2" >  Сумма рассрочки: <b-form-input  v-inputmask-rub size="sm"  v-model="cdata.instalment_sum" readonly> </b-form-input> </b-col>
                <b-col  md="auto" ></b-col>
                <b-col > </b-col>
                <b-col > </b-col>
            </b-row>

            <b-row>
                <b-col md="4">  Общая сумма договора: <b-form-input v-inputmask-rub size="sm"  v-model="cdata.total_sum"  readonly> </b-form-input>   </b-col>
                <b-col sm>  </b-col>
                <b-col sm> </b-col>
            </b-row>
            <b-row>
                <b-col md="4">  Комментарий:
                    <b-form-textarea
                            id="comments"
                            v-model="cdata.comments"
                            placeholder=""
                            rows="3"
                            max-rows="3"
                    ></b-form-textarea>

                </b-col>
                <b-col  >  </b-col>
                <b-col  > </b-col>
            </b-row>
            <b-row>
                <b-col md="4">  Менеджер:  <b-form-input size="sm"  v-model="cdata.manager_name"  readonly></b-form-input>  </b-col>
                <b-col  >  </b-col>
                <b-col  > </b-col>

            </b-row>
                <b-row class="py-2">
                    <b-col><b-button type="submit" variant="warning">Сохранить параметры рассрочки</b-button> </b-col>
                    <b-col  >  </b-col>
                    <b-col  > </b-col>

                </b-row>

            </b-form>
        </b-container>

        </b-card>
        </b-container>
    </div>
</template>

<script>



    export default {

        props:{
            cdata:  null,
        },
        data() {
            return {
                nds_options: [
                    { value: 20, text: '20%' },
                    { value: 0, text: 'Без НДС' },

                ],

             }
        },
        created: function() {

        },
        watch: {
            reset( val )  {
                this.cdata.total_sum  = this.cdata.instalment_sum  = this.cdata.nds_sum = 0
            },
        },
        computed: {
            reset() {

                return this.cdata.reset ;

            },
            nds_sum: {
                get: function () {

                    return this.cdata.nds && this.cdata.contract_sum ? this.cdata.contract_sum - ( this.cdata.contract_sum / (1 + this.cdata.nds/100 )  ): ''

                },
                set: function (newValue) {
                    //this.nds_sum = newValue.parseFloat().toFixed(2)


                }
            },




        },
        methods: {

            onSubmit(evt) {
                evt.preventDefault()
                app.updateContractData()


            },
        } ,

        mounted()  {


        },

    }
</script>
