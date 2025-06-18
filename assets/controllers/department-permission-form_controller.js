import { Controller } from '@hotwired/stimulus';

import $ from 'jquery';

import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

export default class extends Controller {
   static targets = ['modal', 'modalTitle', 'modalBody', 'modalSaveButton', 'rolesInput', 'permissionList', 'sendButton'];
   static values = {
       permissionAddUrl: String,
       permissionListUrl: String,
       status: String,
   };
   modal = null;

   connect() {
      this.updatePermissionList();
   }

   async showAddPermissionForm(event) {
       // Params to initialize form
       let params = event.currentTarget.dataset.params ? JSON.parse(event.currentTarget.dataset.params) : [];
       this.modalBodyTarget.innerHTML = 'Loading...';
       this.modal = new Modal(this.modalTarget);
       this.modal.show();
       this.modalBodyTarget.innerHTML = await $.ajax(this.permissionAddUrlValue,{
           data: params,
       });
       $(this.modalSaveButtonTarget).show(); 
   }

   async submitAddApplicationForm(e) {
      e.preventDefault();
      const $form = $(this.modalBodyTarget).find('form');
      const roles = $(this.rolesInputTarget);
      if (!this.almostOneRole(roles)) {
         Swal.fire({
            template: '#error-html'
        })
        return false;
      }
      try {
         await $.ajax({
            url: $form.attr('action'),
            method: 'POST',
            data: $form.serialize()
         });
         this.modal.hide();
         this.updatePermissionList(e);
      } catch(e) {
         this.modalBodyTarget.innerHTML = e.responseText;
      }
   }

   async updatePermissionList() {
      try {
         const response =  await $.ajax({
            url: this.permissionListUrlValue,
            method: 'GET',
         });
         this.permissionListTarget.innerHTML = response;
         // If status === 2 (Revision pending) the button must be disabled until at least 1 permission is set. 
         // In status (RRHH_NEW) is can be activated without permissions
         if (this.hasSendButtonTarget && ( response !== '' || this.statusValue !== 2)) {
            this.sendButtonTarget.disabled = false;
         }
         this.dispatch('updated', { detail: { status: this.statusValue } });
      } catch(e) {
         Swal.fire({
            template: '#error-html',
            html: e,
        })
      }
   }

   almostOneRole(roles) {
      let result = false;
      $(roles).each(function(){
         if (this.value != '') {
            result = true;
            return;
         }
      });
      return result;
   }
}