//   Copyright 2025 Eric Vyncke
//
//   Licensed under the Apache License, Version 2.0 (the "License");
//  you may not use this file except in compliance with the License.
//   You may obtain a copy of the License at
//
//       http://www.apache.org/licenses/LICENSE-2.0
//
//   Unless required by applicable law or agreed to in writing, software
//   distributed under the License is distributed on an "AS IS" BASIS,
//   WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
//   See the License for the specific language governing permissions and
//   limitations under the License.

var modalContent, modalElement, modalInstance ;

document.addEventListener("DOMContentLoaded", function () {
    modalContent = document.getElementById('detailModalContent');
    modalElement = document.getElementById('detailModal');
    modalInstance = new bootstrap.Modal(modalElement);
}) ;

// On button click: fetch content and show modal
function showDetails(bookingId) {
        // Fill the form with the booking data
        var bookingPilot = bookings[bookingId].r_pilot ;
        var readonly = (userIsBoardMember || userIsInstructor || bookingPilot == userId) ? false : true ;

        hideSpinner() ; // Just to be sure...

        // Let's disable all controls if not allowed to change them or enable them
        const div = document.getElementById('detailModalContent');
        const inputs = div.querySelectorAll('input');
        inputs.forEach(function(input) {
            input.readOnly = readonly;
            input.disabled = readonly ;
        });
        const selects = div.querySelectorAll('select');
        selects.forEach(function(select) {
            select.disabled = readonly ;
        });
        const textareas = div.querySelectorAll('textarea');
        textareas.forEach(function(textarea) {
            textarea.disabled = readonly ;
        });

        // Now fill the data
        document.getElementById("detailModalLabel").innerHTML = 'Réservation #' + bookingId ;
        document.getElementById("planeSelect").value = bookings[bookingId].r_plane ;
        document.getElementById("pilotSelect").value = bookings[bookingId].r_pilot ;
        document.getElementById("pilotPhone").innerHTML = '<a href="tel:' + bookings[bookingId].pcell_phone + '">' + bookings[bookingId].pcell_phone + ' <i class="bi bi-telephone-fill"></i></a>' ;
        document.getElementById("vcardLink").href = 'vcard.php?id=' + bookings[bookingId].r_pilot ;
        if (bookings[bookingId].r_instructor <= 0) {
            document.getElementById("instructorSelect").value = '-1' ;
            document.getElementById("instructorSpan").style.display = 'none' ;
        } else {
            document.getElementById("instructorSelect").value = bookings[bookingId].r_instructor ;
            document.getElementById("instructorSpan").style.display = 'inline' ;
        }
        document.getElementById("instructorPhone").innerHTML = '<a href="tel:' + bookings[bookingId].icell_phone + '">' + bookings[bookingId].icell_phone + ' <i class="bi bi-telephone-fill"></i></a>' ;
        document.getElementById("crewWantedInput").checked = bookings[bookingId].r_crew_wanted ;
        document.getElementById("paxWantedInput").checked = bookings[bookingId].r_pax_wanted ;
        if (bookings[bookingId].r_comment !== null && bookings[bookingId].r_comment != '')
            document.getElementById("commentTextArea").value = bookings[bookingId].r_comment ;
        else
            document.getElementById("commentTextArea").value = '';
        document.getElementById("start").value = bookings[bookingId].r_start ;
        document.getElementById("stop").value = bookings[bookingId].r_stop ;
        document.getElementById("fromInput").value = bookings[bookingId].r_from ;
        document.getElementById("via1Input").value = bookings[bookingId].r_via1 ;
        document.getElementById("via2Input").value = bookings[bookingId].r_via2 ;
        document.getElementById("toInput").value = bookings[bookingId].r_to ;
        // Reset the picture in the div
        document.getElementById("pilotDetailsImage").src = '' ;
        document.getElementById("pilotDetailsImage").style.display = 'none' ;
        if (bookings[bookingId].avatar) {
            document.getElementById("pilotDetailsImage").src = bookings[bookingId].avatar ;
            document.getElementById("pilotDetailsImage").style.visibility = 'inherited' ;
            document.getElementById("pilotDetailsImage").style.display = 'inline' ;
        } else {
            document.getElementById("pilotDetailsImage").src = 'https://www.gravatar.com/avatar/' + bookings[bookingId].gravatar + '?s=80&d=blank&r=pg' ;
            document.getElementById("pilotDetailsImage").style.visibility = 'inherited' ;
            document.getElementById("pilotDetailsImage").style.display = 'inline' ;
        }
        // Show or hide the buttons depending on the rights and date in past or future
        if (!readonly) {
            document.getElementById("cancelButton").style.display = 'block' ;
            document.getElementById("cancelButton").onclick = cancelBooking.bind(this, bookingId) ;
            document.getElementById("modifyButton").style.display = 'block' ;
            document.getElementById("modifyButton").onclick = modifyBooking.bind(this, bookingId) ;
            if (isSqlDateInPast(bookings[bookingId].r_start)) {
                document.getElementById("indexButton").style.display = 'block' ;
                document.getElementById("indexButton").onclick = indexBooking.bind(this, bookingId) ;
            } else {
                document.getElementById("indexButton").style.display = 'none' ;
                document.getElementById("indexButton").onclick = null ;
            }
        } else { // Readonly: hide buttons
            document.getElementById("modifyButton").style.display = 'none' ;
            document.getElementById("modifyButton").onclick = null ;
            document.getElementById("cancelButton").style.display = 'none' ;
            document.getElementById("cancelButton").onclick = null ;
            document.getElementById("indexButton").style.display = 'none' ;
            document.getElementById("indexButton").onclick = null ;
        }
        // The add Button must always be hidden here
        document.getElementById("createButton").style.display = 'none' ;
        document.getElementById("createButton").onclick = null ;
        modalInstance.show();
}

function showSpinner() {
    document.getElementById('modalSpinner').classList.remove('d-none');
}

function hideSpinner() {
    document.getElementById('modalSpinner').classList.add('d-none');
}

function isSqlDateInPast(sqlDateString) {
    // Replace space with 'T' for ISO format if time is present
    const isoString = sqlDateString.replace(' ', 'T');
    const date = new Date(isoString);
    return date < new Date();
}

function indexBooking(bookingId) {
    showSpinner() ; // As this is a huge page taking seconds to load
    window.location.href = 'IntroCarnetVol.php?id=' + bookingId ;
}

function cancelBooking(bookingId) {
    var reason = prompt("Raison de l'annulation (optionnelle):", "") ;
    if (reason !== null) { // Not cancelled
        // User clicked OK
        // Send AJAX request to cancel the booking
        showSpinner() ;
        if (reason == '') reason = 'mobile_today.php' ; // Avoid null
        fetch('cancel_booking.php?id=' + encodeURIComponent(bookingId) + '&reason=' + encodeURIComponent(reason))
        .then(response => {
            if (!response.ok) throw new Error('Network error');
            return response.json(); // Parse response as JSON
        })
        .then(data => {
            // Use the JSON data here
            console.log(data);
            alert(data.message) ;
            modalInstance.hide();
        })
        .catch(error => {
            alert('Une erreur est survenue lors de l\'annulation. Prévenir webmaster@spa-aviation.be');
            console.error('Error:', error);
        })
        .finally(() => {
            hideSpinner(); // Possibly useless if we reload the page ;-)
            location.reload() ; // Refresh the page to show the updated bookings
        });
    }
}

function modifyBooking(bookingId) {
    // User clicked 'modify' button
    // Send AJAX request to modify the booking
    showSpinner() ;
    fetch('modify_booking.php?booking=' + encodeURIComponent(bookingId) + 
        '&plane=' + encodeURIComponent(document.getElementById("planeSelect").value) +
        '&pilotId=' + encodeURIComponent(document.getElementById("pilotSelect").value) +
        '&instructorId=' + encodeURIComponent(document.getElementById("instructorSelect").value) +
        '&start=' + encodeURIComponent(document.getElementById("start").value) +
        '&end=' + encodeURIComponent(document.getElementById("stop").value) +
        '&comment=' + encodeURIComponent(document.getElementById("commentTextArea").value) +
        '&crewWanted=' + (document.getElementById("crewWantedInput").checked ? '1' : '0') +
        '&paxWanted=' + (document.getElementById("paxWantedInput").checked ? '1' : '0') +
        '&fromApt=' + encodeURIComponent(document.getElementById("fromInput").value) +
        '&toApt=' + encodeURIComponent(document.getElementById("toInput").value) +
        '&via1Apt=' + encodeURIComponent(document.getElementById("via1Input").value) +
        '&via2Apt=' + encodeURIComponent(document.getElementById("via2Input").value)
    )
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json(); // Parse response as JSON
    })
    .then(data => {
        // Use the JSON data here
        console.log(data);
        alert(data.message) ;
        modalInstance.hide();
    })
    .catch(error => {
        alert('Une erreur est survenue lors de la modification. Prévenir webmaster@spa-aviation.be');
        console.error('Error:', error);
    })
    .finally(() => {
        hideSpinner(); // Possibly useless if we reload the page ;-)
        location.reload() ; // Refresh the page to show the updated bookings
    });
}

function addBooking(bookingType) {
    // User clicked 'create' button
    // Send AJAX request to modify the booking
    showSpinner() ;
    fetch('create_booking.php?' + 
        'plane=' + encodeURIComponent(document.getElementById("planeSelect").value) +
        '&type=' + encodeURIComponent(bookingType) +
        '&pilotId=' + encodeURIComponent(document.getElementById("pilotSelect").value) +
        '&instructorId=' + encodeURIComponent(document.getElementById("instructorSelect").value) +
        '&start=' + encodeURIComponent(document.getElementById("start").value) +
        '&end=' + encodeURIComponent(document.getElementById("stop").value) +
        '&comment=' + encodeURIComponent(document.getElementById("commentTextArea").value) +
        '&crewWanted=' + (document.getElementById("crewWantedInput").checked ? '1' : '0') +
        '&paxWanted=' + (document.getElementById("paxWantedInput").checked ? '1' : '0') +
        '&fromApt=' + encodeURIComponent(document.getElementById("fromInput").value) +
        '&toApt=' + encodeURIComponent(document.getElementById("toInput").value) +
        '&via1Apt=' + encodeURIComponent(document.getElementById("via1Input").value) +
        '&via2Apt=' + encodeURIComponent(document.getElementById("via2Input").value)
    )
    .then(response => {
        if (!response.ok) throw new Error('Network error');
        return response.json(); // Parse response as JSON
    })
    .then(data => {
        // Use the JSON data here
        console.log(data);
        alert(data.message) ;
        modalInstance.hide();
    })
    .catch(error => {
        alert('Une erreur est survenue lors de la création. Prévenir webmaster@spa-aviation.be');
        console.error('Error:', error);
    })
    .finally(() => {
        hideSpinner(); // Possibly useless if we reload the page ;-)
        location.reload() ; // Refresh the page to show the updated bookings
    });
}

// Should add some JS to handle stop when start is changed...
function displayBookingForm(pilot, bookingType, plane = null, start = null, stop = null) {
    // Clear all fields
    // Let's disable all controls if not allowed to change them or enable them
    const div = document.getElementById('detailModalContent');
    const inputs = div.querySelectorAll('input');
    inputs.forEach(function(input) {
        input.disabled = false ;
        input.value = '' ;
    });
    const selects = div.querySelectorAll('select');
    selects.forEach(function(select) {
        select.disabled = false ;
    });
    const textareas = div.querySelectorAll('textarea');
    textareas.forEach(function(textarea) {
        textarea.disabled = false ;
        textarea.value = '' ;
    });
    // Set default values
    document.getElementById("detailModalLabel").innerHTML = 'Nouvelle réservation' ;
    document.getElementById("pilotSelect").value = pilot ;
    if (plane !== null)
        document.getElementById("planeSelect").value = plane ;
    if (start !== null)
        document.getElementById("start").value = start ;
    else {
        const now = new Date();
        // Format as YYYY-MM-DDTHH:mm
        const formattedDateTime = now.toISOString().slice(0, 16);
        document.getElementById("start").value = formattedDateTime ;
    }
    if (stop !== null)
        document.getElementById("stop").value = stop ;
    else {
        const now = new Date();
        now.setHours(now.getHours() + 1); // Add one hour
        // Format as YYYY-MM-DDTHH:mm
        const formattedDateTime = now.toISOString().slice(0, 16);
        document.getElementById("stop").value = formattedDateTime ;
    }

// Only the add Button must always be hidden here
    document.getElementById("createButton").style.display = 'block' ;
    document.getElementById("createButton").onclick = addBooking.bind(this, bookingType) ;
    document.getElementById("modifyButton").style.display = 'none' ;
    document.getElementById("modifyButton").onclick = null ;
    document.getElementById("cancelButton").style.display = 'none' ;
    document.getElementById("cancelButton").onclick = null ;
    document.getElementById("indexButton").style.display = 'none' ;
    document.getElementById("indexButton").onclick = null ;
    modalInstance.show();
}