import { Controller } from '@hotwired/stimulus';

import { Popover } from 'bootstrap';

export default class extends Controller {

   connect() {
      document.querySelectorAll('.popover-dismiss').forEach(function (popoverElement) {
         const popover = new Popover(popoverElement, {
            trigger: 'focus'
         });
      });
   }
}

 