 <!-- Single Dynamic Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content text-body">
            	<div class="modal-header">
                  <h5 class="modal-title" id="detailModalLabel"></h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
              	</div>
              	<div class="modal-body" id="detailModalContent">
					<div id="modalSpinner" class="d-none"
			           style="position:absolute; top:0; left:0; width:100%; height:100%; background:rgba(255,255,255,0.7); z-index:1051; display:flex; align-items:center; justify-content:center;">
        				<div class="spinner-border" style="width:4rem; height:4rem;" role="status"><span class="visually-hidden">En cours...</span></div>
					</div>
					<img id="pilotDetailsImage"><span id="pilotDetailsSpan"></span>
					<!-- TODO adding form-select in <select> use 2 lines rather than 1 line, Should use the col / row layout instead ? -->
					Avion: <select id="planeSelect"></select>
					<span id="planeComment"></span>
					<span id="pilotType"><br/></span>
					Pilote/élève: <select id="pilotSelect" data-paid-membership="true"> </select>
						 <a id="vcardLink"><i class="bi bi-person-vcard-fill" title="Ajouter le contact"></i></a><br/>
					Mobile pilote: <span id="pilotPhone"></span><br/>
					<span id="instructorSpan">
						Instructeur: <select id="instructorSelect"></select><br/>
						Mobile instructeur: <span id="instructorPhone"></span><br/>
					</span>
					Pilotes RAPCS: <input type="checkbox" id="crewWantedInput" value="true"> bienvenus en tant que co-pilotes.<br/>
					Membres RAPCS: <input type="checkbox" id="paxWantedInput" value="true"> bienvenus en tant que passagers.<br/>
					<textarea id="commentTextArea" class="text-bg-info"></textarea><br/>
					Début: <input type="datetime-local" id="start"><br/>
					Fin: <input type="datetime-local" id="stop"><br/>
					Route: <input type="text" id="fromInput" class="form-control d-inline-block" style="width: 4em;" minlength="3" maxlength="3" placeholder="de" required> -
						<input type="text" id="via1Input" class="form-control d-inline-block" style="width: 4em;" minlength="3" maxlength="3" placeholder="via"> -
						<input type="text" id="via2Input" class="form-control d-inline-block" style="width: 4em;" minlength="3" maxlength="3" placeholder="via"> -
						<input type="text" id="toInput" class="form-control d-inline-block" style="width: 4em;" minlength="3" maxlength="3" placeholder="à">
              	</div>
              	<div class="modal-footer">
					<button type="button" class="btn btn-info" id="indexButton"><i class="bi bi-stopwatch-fill"></i> Compteur</button>
					<button type="button" class="btn btn-danger" id="cancelButton"><i class="bi bi-trash3-fill"></i> Annuler la réservation</button>
					<button type="button" class="btn btn-primary" id="modifyButton"><i class="bi bi-pencil-fill"></i> Modifier la réservation</button>
                  	<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
              	</div>
        </div>
    </div>
</div>