/**
 * @file
 * Global utilities.
 *
 */
(function ($, Drupal) {

  'use strict';

  Drupal.behaviors.becaeducacional_barrio = {
    attach: function (context, settings) {

      $( "input#edit-payment-information-add-payment-method-billing-information-field-desea-factura-value" ).change( function() {
        if($( this ).is(":checked")){
          console.log($( this ).is(":checked"));
          $('div#edit-payment-information-add-payment-method-billing-information-field-rfc-wrapper').show();
          $('div#edit-payment-information-add-payment-method-billing-information-field-direccion-fiscal-wrapper').show();
        }
        else{
          $('div#edit-payment-information-add-payment-method-billing-information-field-rfc-wrapper').hide();
          $('div#edit-payment-information-add-payment-method-billing-information-field-direccion-fiscal-wrapper').hide();
        }
      });

      $( document ).ready( function() {
        if($("form#profile-customer-address-book-edit-form input#edit-field-desea-factura-value").length != 0){
          if($("form#profile-customer-address-book-edit-form input#edit-field-desea-factura-value").is(":checked")){
            console.log($( this ).is(":checked"));
            $('fieldset#datos-fiscales').show();
          }
          else{
            $('fieldset#datos-fiscales').hide();
          }
        }
        });


      $( "form#profile-customer-address-book-edit-form input#edit-field-desea-factura-value" ).change( function() {
        if($( this ).is(":checked")){
          console.log($( this ).is(":checked"));
          $('fieldset#datos-fiscales').show();
        }
        else{
          $('fieldset#datos-fiscales').hide();
        }
      });

    }
  };

})(jQuery, Drupal);
