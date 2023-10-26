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
		if (valuesArray[i].last_name == '')
			option.innerHTML = valuesArray[i].name ;
		else
			option.innerHTML = valuesArray[i].last_name + ', ' + valuesArray[i].first_name ;
		if (valuesArray[i].student) {  // Add a student icon
			option.innerHTML += ' &#x1f4da;' ;
		}
                option.value = valuesArray[i].id ;
                option.selected = valuesArray[i].id == selectedValue ;
                select.add(option) ;
        }
}

function initProfile(pilotDisplayed) {
// Prefill the select drop-down
        prefillDropdownMenus('pilotSelect', members, pilotDisplayed) ;
}