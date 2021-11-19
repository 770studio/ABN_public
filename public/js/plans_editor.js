$(document).ready(function () {

    fetchPlans();

    function fetchPlans() {
        $.ajax({
            url: "/planseditor/plansFetch",
            dataType: "json",
            success: function (data) {
                var html = '';
                html += '<tr>';
                html += '<td> <select id="users" style="width: 100%"></select> </td>';
                html += '<td> <input type="text" class="form-control plans" placeholder="Укажите план" id="addPlans" style="height: 28px"></td>';
                html += '<td> <input type="text" class="form-control" placeholder="Выберите год" id="addYear" style="height: 28px"> </td>';
                html += '<td><button type="button" class="btn btn-success btn-xs" id="add">Добавить план</button></td></tr>';

                for (var count = 0; count < data.length; count++) {
                    html += '<tr>';
                    html += '<td class="employee_id" data-column_name="employee_id" data-id="' + data[count].employee_id + '">' + data[count].user_name + '</td>';
                    html += '<td> <input type="text" class="form-control plans update" id="actionPlans" style="height: 28px" value="' + data[count].plans + '"></td>';
                    html += '<td class="year" data-column_name="year" data-id="' + data[count].year + '">' + data[count].year + '</td>';
                    html += '<td>' +
                        '<button type="button" class="btn btn-danger btn-xs delete" data-userId="' + data[count].employee_id + '" data-year="' + data[count].year + '">Удалить план</button>' +
                        '</td>';
                    html += '</tr>';
                }

                $('tbody').html(html);

                uploadUsers();

                initializeSelect2($("#users"));
                initializeYearPicker($("#addYear"));
                initializeMoneyMask($(".plans"));
            }
        });
    }

    function initializeSelect2(selectElementObj) {
        selectElementObj.select2({
            placeholder: 'Выберите пользователя',
            tags: true
        });
    }

    function initializeYearPicker(selectElementObj) {
        selectElementObj.datepicker({
            minViewMode: 'years',
            autoclose: true,
            format: 'yyyy'
        });
    }

    function initializeMoneyMask(selectElementObj) {
        selectElementObj.mask('# ##0.00', {reverse: true});
    }


    var _token = $('input[name="_token"]').val();

    function uploadUsers() {
        $.ajax({
            url: "/planseditor/plansUploadUsers",
            method: "POST",
            data: {_token: _token},
            dataType: "json",
            success: function (data) {
                $('#users').append("<option></option>");
                data.forEach(function (user) {
                    $('#users').append("<option value='" + user.id + "'>" + user.user_name + "</option>");
                });
            }
        });

    }

    $(document).on('click', '#add', function () {
        var employee_id = $('#users').val();
        var plans = $('#addPlans').val();
        var year = $('#addYear').val();

        if (employee_id != '' && plans != '' && year != '') {
            $.ajax({
                url: "/planseditor/plansAdd",
                method: "POST",
                data: {employee_id: employee_id, plans: plans, year: year, _token: _token},
                statusCode: {
                    200: function (msg) {
                        $('#message').html("<div class='alert alert-success'>" + msg['message'] + "</div>");
                        fetchPlans();
                    },
                    400: function (msg) {
                        $('#message').html("<div class='alert alert-danger'>" + msg.responseJSON.message + "</div>");
                        fetchPlans();
                    }
                }
            });
        } else {
            $('#message').html("<div class='alert alert-danger'>Необходимо заполнить все поля</div>");
        }
    });

    $(document).on('change', '.update', function () {
        var id = $(this).parent().siblings('.employee_id').attr('data-id');
        var plans = $(this).val();
        var year = $(this).parent().siblings('.year').attr('data-id');

        if (confirm("Вы уверены, что хотите обновить текущий план?")) {
            $.ajax({
                url: "/planseditor/plansUpdate",
                method: "POST",
                data: {id: id, plans: plans, year: year, _token: _token},
                statusCode: {
                    200: function (msg) {
                        $('#message').html("<div class='alert alert-success'>" + msg['message'] + "</div>");
                    },
                    400: function (msg) {
                        $('#message').html("<div class='alert alert-danger'>" + msg.responseJSON.message + "</div>");
                    }
                }
            });
        }
    });

    $(document).on('click', '.delete', function () {
        var id = $(this).attr("data-userId");
        var year = $(this).attr("data-year");

        if (confirm("Вы уверены, что хотите удалить текущий план?")) {
            $.ajax({
                url: "/planseditor/plansDelete",
                method: "POST",
                data: {id: id, year: year, _token: _token},
                statusCode: {
                    200: function (msg) {
                        $('#message').html("<div class='alert alert-success'>" + msg['message'] + "</div>");
                        fetchPlans();
                    },
                    400: function (msg) {
                        $('#message').html("<div class='alert alert-danger'>" + msg.responseJSON.message + "</div>");
                        fetchPlans();
                    }
                }
            });
        }
    });

});
