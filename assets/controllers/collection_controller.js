import { Controller } from '@hotwired/stimulus';

import $ from 'jquery';

export default class extends Controller {
   static values = {
      listSelector: String,
   }


   connect() {
      var list = $(this.listSelectorValue ?? '#collection-list');
      console.log(list);
   }
   /**
    * This elements are mandatory:
         data-prototype="{{ formMacros.printMemberRow(form.members.vars.prototype)|e('html_attr') }}"
         data-widget-tags="{{ '<li></li>'|e }}"
         data-widget-counter="{{ form.members|length }}">
    */
   addElement(e) {
      var list = $(this.listSelectorValue ?? '#collection-list');

      // Try to find the counter of the list or use the length of the list
      var counter = list.data('widget-counter') || list.children().length;

      // grab the prototype template
      var newWidget = list.attr('data-prototype');

      // replace the "__name__" used in the id and name of the prototype
      // with a number that's unique to your emails
      // end name attribute looks like name="contact[emails][2]"
      newWidget = newWidget.replace(/__name__/g, counter);

      // Increase the counter
      counter++;
      // And store it, the length cannot be used if deleting widgets is allowed
      list.data('widget-counter', counter);

      // create a new list element and add it to the list
      var newElem = $(list.attr('data-widget-tags')).html(newWidget);

      newElem.appendTo(list);
   }

   removeElement(e) {
      e.preventDefault();
      var memberFormLi = $(e.currentTarget).parent().parent();
      memberFormLi.remove();
   }
}

// export { collection };