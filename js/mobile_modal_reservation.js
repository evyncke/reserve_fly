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

        // Let's disable all controls if not allowed to change them
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
            document.getElementById("commentSpan").innerHTML = bookings[bookingId].r_comment.replace(/\n/g, '<br/>') + '<br/>';
        else
            document.getElementById("commentSpan").innerHTML = '';
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
        if (!readonly) {
            document.getElementById("cancelButton").style.display = 'block' ;
            document.getElementById("cancelButton").onclick = cancelBooking.bind(this, bookingId) ;
            if (isSqlDateInPast(bookings[bookingId].r_start)) {
                document.getElementById("indexButton").style.display = 'block' ;
                document.getElementById("indexButton").onclick = indexBooking.bind(this, bookingId) ;
            } else {
                document.getElementById("indexButton").style.display = 'none' ;
                document.getElementById("indexButton").onclick = null ;
            }
        } else {
            document.getElementById("cancelButton").style.display = 'none' ;
            document.getElementById("cancelButton").onclick = null ;
            document.getElementById("indexButton").style.display = 'none' ;
            document.getElementById("indexButton").onclick = null ;
        }
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