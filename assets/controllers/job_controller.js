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
      const response = await fetch(`${this.urlValue}?${params.toString()}`);
      const json = await response.text();
      const job = JSON.parse(json);
      this.dispatch('changed', { 'detail' : job });
   }
}