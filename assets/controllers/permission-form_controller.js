import { Controller } from '@hotwired/stimulus';

import $ from 'jquery';

export default class extends Controller {
   static targets = ['applicationInput', 'subApplicationInput', 'rolesInput'];
   static values = {
      applicationRolesUrl: String,
   };

   connect() {
      this.onApplicationChange();
   }

   onApplicationChange(e) {
      if (this.applicationInputTarget.value != 1) {
         if (this.hasSubApplicationInputTarget) {
            this.subApplicationInputTarget.parentElement.classList.add('visually-hidden');
            this.subApplicationInputTarget.classList.add('visually-hidden');
            this.subApplicationInputTarget.disabled = true;
         }
      } else {
         if (this.hasSubApplicationInputTarget) {
            this.subApplicationInputTarget.parentElement.classList.remove('visually-hidden');
            this.subApplicationInputTarget.classList.remove('visually-hidden');
            this.subApplicationInputTarget.disabled = false;
         }
      }
      this.getApplicationRoles(this.applicationInputTarget.value);
   }

   async getApplicationRoles(id) {
      try {
         const url = this.applicationRolesUrlValue;
         const params = new URLSearchParams({
            application: id,
         });
         const response = await fetch(`${url}?${params.toString()}`);
         const json = await response.text();
         const application = JSON.parse(json);
         this.fillRolesInputOptions(application.roles);
      } catch(e) {
         console.log(e);
      }
   }

   fillRolesInputOptions(roles) {
      this.removeOptions(this.rolesInputTarget);
      roles.forEach(element => {
         this.rolesInputTarget.options[this.rolesInputTarget.options.length] = new Option(element.nameEs,element.id);
      });
   }

   removeOptions(selectElement) {
      var i, L = selectElement.options.length - 1;
      for(i = L; i >= 0; i--) {
         selectElement.remove(i);
      }
   }
}