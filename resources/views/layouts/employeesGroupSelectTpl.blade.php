$('#employeesGroupSelect').multiselect({
enableFiltering: true,
enableCaseInsensitiveFiltering:true,
filterPlaceholder: 'Поиск',
enableClickableOptGroups: true,
enableCollapsibleOptGroups: true,
collapseOptGroupsByDefault: true,
includeSelectAllOption: false,
selectAllJustVisible: false,
includeResetOption: true,
nonSelectedText: 'Выбрать',
allSelectedText: 'Выбраны все',
resetText: 'Удалить все',
selectAllText: 'Выбрать все',
nSelectedText: 'выбраны',
numberDisplayed: 1,
buttonContainer: '<div class="btn-group-multi" />',
templates: {

button: '<button type="button" class="dropdown-toggle btn-light" data-toggle="dropdown"><div class="filter-option"><div class="filter-option-inner"><div class="filter-option-inner-inner"></div></div> </div></button>',
ul: '<div class="dropdown-menu"></div>',
li: '<li><a class="dropdown-item itemLi" href="javascript:void(0);"><div class="checkboxItem"><label></label></div></a></li>',
liGroup: '<li class="multiselect-item group"><label class="multiselect-group"></label></li>',
resetButton:'<li class="bs-actionsbox"><div class="btn-group btn-group-sm btn-block"><a href="javascript:void(0);" class="actions-btn  btn btn-light"><label class="checkbox"><input type="checkbox" value="multiselect-all"> Выбрать все</label></a><a class="btn reset-btn btn-light actions-btn"></a></div></li>'
}
});