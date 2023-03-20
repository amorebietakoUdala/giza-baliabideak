import { Controller } from '@hotwired/stimulus';

import $ from 'jquery';

import { Modal } from 'bootstrap';
import Swal from 'sweetalert2';

export default class extends Controller {
   static targets = ['modal', 'modalTitle', 'modalBody', 'modalSaveButton', 'rolesInput', 'permissionList', 'sendButton'];
   static values = {
       permissionAddUrl: String,
       permissionListUrl: String,
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
         if (response !== '' && this.hasSendButtonTarget) {
            this.sendButtonTarget.disabled = false;
         }
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