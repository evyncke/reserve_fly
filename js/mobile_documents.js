/*
   Copyright 2026 Eric Vyncke & Claude code

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

// Finder-like folder tree: folders are collapsed by default, click to expand/collapse
document.addEventListener('DOMContentLoaded', function() {
	document.querySelectorAll('.doc-folder').forEach(function(folder) {
		folder.addEventListener('click', function(event) {
			event.preventDefault() ;
			const children = folder.nextElementSibling ;
			if (!children) return ;
			const icon = folder.querySelector('i.bi') ;
			const expanded = folder.getAttribute('aria-expanded') === 'true' ;
			children.classList.toggle('d-none', expanded) ;
			folder.setAttribute('aria-expanded', expanded ? 'false' : 'true') ;
			if (icon) {
				icon.classList.toggle('bi-folder', expanded) ;
				icon.classList.toggle('bi-folder2-open', !expanded) ;
			}
		}) ;
	}) ;
}) ;
