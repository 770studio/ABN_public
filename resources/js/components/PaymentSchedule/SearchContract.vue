<template>
    <div >

        <b-modal ref="create_contract" id="create_contract" hide-footer title="">
            <div class="d-block text-center">
                <h3>Сделка найдена, создать договор для contract_number: <b>{{lead.contract_number }}</b>,
                    статус сделки: <b>{{lead.stage_name }}</b> ?</h3>
            </div>
            <b-button class="mt-3" variant="outline-danger" block @click="$bvModal.hide('create_contract')" >Нет, спасибо!</b-button>
            <b-button class="mt-2" variant="outline-success" block @click="onCreateContract">Да, создать!</b-button>
        </b-modal>
        <b-container fluid  class="bv-example-row" id="pSearch">

            <b-row>
                <b-col md="auto"> <span class="margin-text">Договор:</span>
                </b-col>
                <b-col md="4" >
                    <b-form-input autocomplete="off" v-model="stext" placeholder="Поиск по номеру договора"></b-form-input>

                        <b-form-select id="select-contract" size="sm"     v-model="pick_one_contract"  v-show="found_contracts.length"    :options="found_contracts"  >
                            <b-tooltip style="white-space: nowrap;" variant="info"   target="select-contract">lead_id | номер договора, cумма договора</b-tooltip>
                            <template slot="first">
                                <option value="0" disabled>-- Пожалуйста выберите договор --</option>
                            </template>

                        </b-form-select>

                </b-col>

                <b-col md="auto" class="mt-2">  от: &nbsp; <b-badge href="#" variant="secondary">{{lead.contract_date }} </b-badge>
                </b-col>
                <b-col md="auto" >   <b-spinner v-show="cdata.loading" label="Loading..."></b-spinner>
                </b-col>

                <b-col md="auto" >  <b-button variant="warning" @click="search">Обновить</b-button> &nbsp;

                </b-col>


            </b-row>

            <b-row>
                <b-col  md="6" > Статус: {{lead.stage_name }} </b-col>
                <b-col > </b-col>
                <b-col > </b-col>
            </b-row>

            <b-row>
                <b-col sm>  </b-col>
                <b-col sm>  </b-col>
                <b-col sm> </b-col>
                <b-col sm> </b-col>
            </b-row>


        </b-container>


       <!-- <div class="mt-2">Value: {{ text }}</div>-->
    </div>
</template>

<script>
    export default {
        props:{
            cdata: null,
            flow: null,
        },
        data() {
            return {
                found_contracts: {},
                pick_one_contract: null,
                stext: '',
                lead: {},
                inst: {},
                searchBy: false,
            }
        },
        created: function() {

           // this.showAlert('ничего не найдено!');

        },

        watch: {
            pick_one_contract: function(lead_id, oldText) {




                // если выбран конкретный договор-сделка , запрос по lead_id
                if(!lead_id) return;
                  this.searchBy = {lead_id :  lead_id }
                  this.stext = _.find(this.found_contracts, function(o) { return o.value == lead_id; }).text;
                  this.search();
                  this.found_contracts =  {}

/*

                if( this.stext == newText)  this.search(); // выбрали договор с номером совпадающим с запросом

                if(newText) {
                   this.stext = newText
                   this.found_contracts = {}
               }
*/

            },
            stext: function (newText, oldText  ) {
                   // console.log(newText, oldText)

                if(this.searchBy && this.searchBy.lead_id ) return; // поиск по лид ид

                this.cdata.loading  = false;
                    //TODO stop ajax

                    this.search();
            }
        },
        methods: {

            onCreateContract: function() {

                this.cdata.loading  = true
                app.$bvModal.hide('create_contract')
                axios.post('api/createContract', this.lead )
                    .then(response => {
                        this.cdata.loading  = false

                         if(response.data.error) {
                             app.showAlert ( response.data.error , 'danger' );

                         } else {

                            app.showAlert( 'Договор создан, загружаем данные...' ,  'success' );

                             //this.stext =

                             app.loadData(response.data);
                         }

//grtytu


                    });
            },

            search: _.debounce(function(e)  {


                app.cData =  { loading : true}

                   axios.post('api/searchContract', { stext: this.stext, search_by: this.searchBy  })
                                  .then(response => {
                                      //console.log(response)

                                      //console.log( response.data.lead, response.data.inst )
                                      this.cdata.loading = this.searchBy = false;

                                      if(response.data.search_result ) {
                                          // выбор
                                          this.pick_one_contract = 0
                                          this.found_contracts = response.data.lead

                                          return;
                                      }

                                      if(  response.data.lead ) {
                                           this.lead = response.data.lead;
                                          if( ! response.data.inst   ) {
                                              app.$bvModal.show('create_contract')
                                          } else {
                                              // есть рассрочка
                                              //this.inst = response.data.inst;


                                              app.loadData(response.data);
                                              app.showAlert ( 'Договор загружен' ,   'success' )
                                          }

                                      } else {
                                          app.cData = {}
                                          app.showAlert('ничего не найдено!');
                                      }




                                  });


            }, 2000) ,

            //отчет по договору
            makeReport(){
                axios.post('api/addAssignment')

              }

        } ,

    }
</script>
