function checkboxChanged(elem) {
    document.getElementById('submitButton').disabled = ! elem.checked ;
}

function pilotChange(url, elem) {
    var displayedPilot = elem.value ;
	window.location.href = url + '?displayed_id=' + displayedPilot ;
}
