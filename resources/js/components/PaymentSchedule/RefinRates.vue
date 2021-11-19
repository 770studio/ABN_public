<template>
    <div>


        <h5>Ставка Банка России</h5>

        <b-table ref="RefRateHistoryTable" :busy.sync="isBusy" :current-page="currentPage" :fields="fields"
                 :items="items" :per-page="perPage"
                 :select-mode='rowSelectMode'
                 caption-top
                 class="mt-3"
                 hover
                 outlined
                 responsive
                 selectable
                 striped
                 @row-dblclicked="editRow"
                 @row-selected="onRowSelected"
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
            <b-col class="my-1 mb-3" md="1">
                <b-pagination
                    v-model="currentPage"
                    :per-page="perPage"
                    :total-rows="totalRows"
                    align="fill"
                    class="my-0"
                    size="sm"
                ></b-pagination>
            </b-col>
        </b-row>

        <b-button variant="danger" @click="$bvModal.show('edit_rate')">Добавить ставку</b-button>

        <b-button v-show="this.selected.length" variant="danger" @click="delete_selected">Удалить выбранные</b-button>

        <b-modal id="delete_refinrate_rows" ref="delete_refinrate_rows" hide-footer title="">

            <h3>Удалить выбранные</h3>
            Уверенны, что хотите удалить ставки№№: {{ SelectedIdsString }}
            <b-button block class="mt-3" variant="outline-success"
                      @click="$bvModal.hide('delete_refinrate_rows');DeleteRows()">ok
            </b-button>

        </b-modal>


        <b-modal id="edit_refinrate_row" ref="edit_refinrate_row" hide-footer title="">
            <h3>Редактировать ставку</h3>
            <b-container fluid>
                <b-row class="">
                    <b-col sm="5">
                        <label> Ставка: </label>
                    </b-col>
                    <b-col sm="5">
                        <b-form-input v-model="editingRow.rate"
                                      placeholder="00.00"></b-form-input>
                    </b-col>
                </b-row>


                <b-row class="">
                    <b-col sm="5">
                        <label> Дата начала действия: </label>
                    </b-col>
                    <b-col sm="5">
                        <b-form-input v-model="editingRow.start_date" type="date"></b-form-input>

                    </b-col>
                </b-row>

                <b-button block class="mt-3" variant="outline-success"
                          @click="$bvModal.hide('edit_refinrate_row');editRate()">Сохранить
                </b-button>


            </b-container>
        </b-modal>


    </div>
</template>

<script>
export default {

    props: {
        cdata: null,
    },
    data() {

        return {
            selected: [],
            rowSelectMode: 'multi',
            isBusy: false,
            items: null,
            totalRows: 1,
            currentPage: 1,
            perPage: 10,
            sortBy: 'id',
            sortDesc: false,
            editingRow: {},


            fields: [
                {
                    key: 'id',
                    sortable: false,
                    label: 'id',
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

    mounted() {


    },
    created: function () {
        this.dataProvider(
            this.getQueryParams()
        )

    },
    computed: {

        SelectedIdsString: function () {

            return _.map(this.selected, 'id').toString()
        },

    },
    watch: {},

    methods: {
        editRow(row) {
            this.editingRow = row
            app.$bvModal.show('edit_refinrate_row')
        },
        editRate() {
            this.dataProvider({'edit_refin_rate': this.editingRow, ...this.getQueryParams()})

        },
        /*
                add_row() {
                    alert(33333)
                },
        */

        delete_selected() {
            app.$bvModal.show('delete_refinrate_rows')
        },

        onRowSelected(items) {
            this.selected = items
        },

        dataProvider(ctx) {
            //console.log(ctx);

            this.isBusy = true

            let method = 'getRefRateHistory';
            switch (Object.keys(ctx)[0]) {
                case 'del_refin_rates' :
                    method = 'deleteRefinRateRows';
                    break;
                case 'edit_refin_rate' :
                    method = 'editRefinRateRow';
                    break;
            }


            const promise = axios.post('api/' + method, ctx)

            return promise.then((resp) => {
                // console.log(resp.data);
                this.items = resp.data.data
                this.isBusy = false
                this.totalRows = this.items.length //resp.data.total

                if (resp.data.error) {
                    app.showAlert(resp.data.error, 'danger');
                } else if (resp.data.msg) {
                    app.showAlert(resp.data.msg, 'success');
                }

                return (this.items)
            })
                .catch(error => {
                    this.isBusy = false
                    if (error.response) {
                        _.forEach(error.response.data.errors, function (value) {
                            console.log(value);
                            app.showAlert('Ошибка:' + value, 'danger')
                        });
                    } else {
                        app.showAlert('Ошибка сервера. Обратитесь к администратору.' + error, 'danger')

                    }


                })
        },
        DeleteRows() {
            this.dataProvider({'del_refin_rates': this.selected, ...this.getQueryParams()})

        },
        getQueryParams() {
            return {
                "currentPage": this.currentPage,
                "perPage": this.perPage,
                "sortBy": this.sortBy,
                "sortDesc": this.sortDesc
            }

        },


    },

}
</script>
