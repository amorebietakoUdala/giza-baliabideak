import { Controller } from '@hotwired/stimulus';



export default class extends Controller {

   reset(e) {
      const form = e.currentTarget.form;
      const elements = form.querySelectorAll("input[type=text]");
      for (var i = 0; i <elements.length; i++) {
         document.getElementById(elements[i].id).value='';
      }
      const selects = form.querySelectorAll("select");
      for (var i = 0; i <selects.length; i++) {
         document.getElementById(selects[i].id).value='';
      }
   } 
}