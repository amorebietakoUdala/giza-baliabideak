import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
   static values = {
      url: String,
   }
   
   async onChange(e) {
      const url = this.urlValue;
      const params = new URLSearchParams({
         id: e.currentTarget.value,
      });
      console.log(this.urlValue);
      const response = await fetch(`${this.urlValue}?${params.toString()}`);
      const json = await response.text();
      const job = JSON.parse(json);
      console.log(job);
      this.dispatch('changed', { 'detail' : job });
   }
}