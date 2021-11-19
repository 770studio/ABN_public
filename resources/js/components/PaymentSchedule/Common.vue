<template>
    <div >

        <b-container fluid  class="bv-example-row">
            <b-form @submit="onSubmit"    >
            <b-row>
                <b-col md="2" > Объект недвижимости:  <b-form-input   size="sm" v-model="cdata.complex"  readonly> </b-form-input>
                </b-col>
                <b-col md="2" > &nbsp;<b-form-input   size="sm" v-model="cdata.address"  readonly></b-form-input></b-col>
                <b-col sm >  Сделка по недвижимости: <a v-show="cdata.lead_id" target="_blank"  v-bind:href="'https://akbarsned.amocrm.ru/leads/detail/' + cdata.lead_id"> {{  cdata.lead_id }} </a>  </b-col>
                <b-col md="auto">
                </b-col>
            </b-row>
<!--
            <b-row>
                <b-col  md="4" > Клиент(клиенты): <b-form-input  size="sm" v-model="cdata.client_name" readonly>  </b-form-input> </b-col>
            </b-row>

            <b-row>
                    <b-col  md="4" > Доли: <b-form-input  size="sm" v-model="cdata.ownership_percent" readonly>  </b-form-input> </b-col>
            </b-row>
-->



                <b-row v-for="client in cdata.clients"
                       v-bind:data="client"
                       v-bind:key="client.name">


                    <b-col md="2"  > Клиент: <b-form-input  size="sm" v-model="client.name" readonly>  </b-form-input> </b-col>

                    <b-col md="2"  >  Доля:    <b-form-input   size="sm" v-model="client.prs"  readonly> </b-form-input></b-col>

                </b-row>





            <b-row>
                <b-col md="4">  Правообладатель: <b-form-input  size="sm"  v-model="cdata.owner"  readonly>  </b-form-input>   </b-col>
                <b-col sm>  </b-col>
                <b-col sm> </b-col>
            </b-row>

            <b-row>

                <b-col md="2">   Площадь по ЖК:  <b-form-input size="sm"  v-model="cdata.ttl_area"  >  </b-form-input>  </b-col>
                <b-col md="2" > Тип оплаты: <b-form-input size="sm"  v-model="cdata.payment_type" readonly >  </b-form-input></b-col>
                <b-col md="2" > Ипотека: <b-form-input size="sm"  v-model="cdata.bank_ipoteka" readonly> </b-form-input></b-col>
                <b-col md="2" > Рассрочка: <b-form-input size="sm"  v-model="cdata.installment" readonly> </b-form-input></b-col>

            </b-row>


            <b-row>

                <b-col md="2">  Площадь по СНиП:  <b-form-input size="sm" v-model="cdata.total_area"  readonly> </b-form-input>  </b-col>
                <b-col  >  </b-col>
                <b-col  > </b-col>
            </b-row>
            <b-row class="py-2">
                    <b-col><b-button type="submit" variant="warning">Сохранить</b-button> </b-col>
                    <b-col  >  </b-col>
                    <b-col  > </b-col>

                </b-row>
            </b-form>
        </b-container>



    </div>
</template>

<script>
    export default {

        props:{
            cdata: null
        },
        data() {
            return {

            }
        },
        created: function() {




        },
        watch: {

        },

        methods: {
            onSubmit(evt) {
                evt.preventDefault()
                app.updateContractData()


            },
        } ,

    }
</script>
