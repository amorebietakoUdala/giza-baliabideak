import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
   static targets = ['historicList'];
   static values = {
      url: String,
   };

   connect() {
      //this.onPermissionChange(null);
   }

   async onPermissionChange(e) {
      if (!this.hasHistoricListTarget) {
         return;
      }
      this.historicListTarget.innerHTML = 'Loading...';
      const response = await fetch(`${this.urlValue}`);
      this.historicListTarget.innerHTML = await response.text();
   }
}
