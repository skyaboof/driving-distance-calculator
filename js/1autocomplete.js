(function ($, Drupal) {
  'use strict';

  // Google callback must be globally available.
  window.initMap = function () {
    try {
      if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
        console.warn('Google Maps API not available yet.');
        return;
      }

      // Use name attributes used in your webform YAML fields: origin_address and destination_address
      var origin = document.querySelector('[name="origin_address"]');
      var destination = document.querySelector('[name="destination_address"]');

      if (origin) {
        new google.maps.places.Autocomplete(origin, { types: ['geocode'] });
      }
      if (destination) {
        new google.maps.places.Autocomplete(destination, { types: ['geocode'] });
      }

      console.log('driving_distance_calculator: initMap executed; autocomplete bound if fields present.');
    } catch (e) {
      console.error('driving_distance_calculator initMap error:', e);
    }
  };

  Drupal.behaviors.drivingDistanceCalculator = {
    attach: function (context) {
      // Nothing required here; Google will call initMap when loaded.
    }
  };

})(jQuery, Drupal);
