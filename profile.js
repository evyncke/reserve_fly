function checkboxChanged(elem) {
    document.getElementById('submitButton').disabled = ! elem.checked ;
}

function pilotChange(url, elem) {
    var displayedPilot = elem.value ;
	window.location.href = url + '?displayed_id=' + displayedPilot ;
}

function prefillDropdownMenus(selectId, valuesArray, selectedValue) {
        var select = document.getElementById(selectId) ;

        for (var i = 0; i < valuesArray.length; i++) {
                var option = document.createElement("option");
                option.text = valuesArray[i].name ;
			//	if (valuesArray[i].student) {  // after many attemps no way to add an icon after a student name...
			//		option.text += '<span class="glyphicon glyphicon-education"></span>' 
			//		option.text = '&#xe233;' + valuesArray[i].name;
			//		option.class = 'glyphicon glyphicon-education' ;
			//	}
                option.value = valuesArray[i].id ;
                option.selected = valuesArray[i].id == selectedValue ;
                select.add(option) ;
        }
}

function init(pilotDisplayed) {
// Prefill the select drop-down
        prefillDropdownMenus('pilotSelect', members, pilotDisplayed) ;
}
