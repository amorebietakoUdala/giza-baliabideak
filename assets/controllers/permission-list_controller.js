import { Controller } from '@hotwired/stimulus';

import $ from 'jquery';

import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

export default class extends Controller {
	static targets = ['modal', 'modalTitle', 'modalBody', 'modalSaveButton'];
	static values = {
		permissionGrantUrl: String,
	}
	url = null;

	connect() {
		console.log('permission-list controller connected');
	}

	async showGrantPermissionForm(event) {
		// Params to initialize form
		//let params = event.currentTarget.dataset.params ? JSON.parse(event.currentTarget.dataset.params) : [];
		this.url = event.currentTarget.dataset.url;
		this.modalBodyTarget.innerHTML = 'Loading...';
		this.modal = new Modal(this.modalTarget);
		this.modal.show();
		this.modalBodyTarget.innerHTML = await $.ajax(this.url);
		$(this.modalSendButtonTarget).show(); 
		$(this.modalDontSendButtonTarget).show(); 
	}

	async submitPermissionGrantForm(e) {
		e.preventDefault();
		const send = e.currentTarget.dataset.send;
		this.url = this.url+'?send='+send;
		const $form = $(this.modalBodyTarget).find('form');
		try {
			await $.ajax({
				url: this.url,
				method: 'POST',
				data: $form.serialize()
			});
			this.modal.hide();
			this.dispatch('permission-granted');
		} catch(e) {
			this.modalBodyTarget.innerHTML = e.responseText;
		}
	}


}