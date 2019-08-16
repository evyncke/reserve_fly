/*
   Copyright 2014-2019 Eric Vyncke

   Licensed under the Apache License, Version 2.0 (the "License");
   you may not use this file except in compliance with the License.
   You may obtain a copy of the License at

       http://www.apache.org/licenses/LICENSE-2.0

   Unless required by applicable law or agreed to in writing, software
   distributed under the License is distributed on an "AS IS" BASIS,
   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
   See the License for the specific language governing permissions and
   limitations under the License.

*/
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
