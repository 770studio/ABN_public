'Сегодня': [moment(), moment()],
'Вчера': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
'Последние 7 дней': [moment().subtract(6, 'days'), moment()],
'Последние 30 дней': [moment().subtract(29, 'days'), moment()],
'Текущий месяц': [moment().startOf('month'), moment().endOf('month')],
'Прошлый месяц': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
'С начала года': [moment().startOf('year'), moment()],
'Прошлый год': [moment().subtract(1, 'year').startOf('year'), moment().subtract(1, 'year').endOf('year')]