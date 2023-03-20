import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
   static targets = ['permissionList'];
   static values = {
      url: String,
   };

   connect() {
      console.log(this.urlValue);
   }

   onJobChange(e) {
      const job = e.detail;
      this.reset(e);
      job.applications.forEach( (app) => {
         const input = e.currentTarget.querySelector('input[name="worker[applications][]"][value="'+app.id+'"]');
         input.checked = true;
      });
   }

   reset(e) {
      const inputs = e.currentTarget.querySelectorAll('input[name="worker[applications][]"]');
      inputs.forEach((input) => {
         input.checked = false;
      });
   }
}