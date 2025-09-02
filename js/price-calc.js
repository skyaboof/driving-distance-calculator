(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.drivingDistancePriceCalc = {
    attach: function (context, settings) {
      var endpoint = drupalSettings.drivingDistanceCalculator && drupalSettings.drivingDistanceCalculator.priceEndpoint;
      if (!endpoint) {
        return;
      }

      // Find forms explicitly marked by the form build using data attribute.
      var $forms = $(context).find('form[data-driving-distance-form="1"]').once('driving-distance-price');

      if (!$forms.length) {
        return;
      }

      $forms.each(function () {
        var $form = $(this);

        // Determine relevant field names dynamically by reading inputs you expect.
        var fieldNames = [
          'service_type',
          'move_size_residential',
          'origin_address',
          'destination_address'
        ];

        function collectData() {
          var payload = {};
          fieldNames.forEach(function (name) {
            var $els = $form.find('[name="' + name + '"], [name="' + name + '[]"]');
            if (!$els.length) {
              return;
            }
            if ($els.length > 1) {
              var vals = [];
              $els.each(function () {
                var $el = $(this);
                if ($el.is(':checkbox') && $el.is(':checked')) {
                  vals.push($el.val());
                } else if (!$el.is(':checkbox')) {
                  var v = $el.val();
                  if (v !== undefined && v !== null && v !== '') {
                    vals.push(v);
                  }
                }
              });
              payload[name] = vals;
            } else {
              var $el = $($els[0]);
              if ($el.is(':checkbox')) {
                payload[name] = $el.is(':checked') ? $el.val() : null;
              } else if ($el.is(':radio')) {
                var $checked = $form.find('[name="' + $el.attr('name') + '"]:checked');
                payload[name] = $checked.length ? $checked.val() : null;
              } else {
                payload[name] = $el.val();
              }
            }
          });
          return payload;
        }

        var pending = null;
        function postData() {
          var data = collectData();

          if (pending && pending.abort) {
            pending.abort();
          }

          pending = $.ajax({
            url: endpoint,
            method: 'POST',
            contentType: 'application/json; charset=utf-8',
            data: JSON.stringify(data),
            dataType: 'json',
            timeout: 10000
          });

          pending.done(function (resp) {
            if (!resp) { return; }
            if (resp.total !== undefined) {
              var formatted = (typeof resp.total === 'number') ? ('$' + resp.total.toFixed(2)) : resp.total;
              var $cost = $form.find('[name="calculated_cost"]');
              if ($cost.length) {
                $cost.val(formatted).trigger('input');
              }
            }
            if (resp.distance_m !== undefined) {
              var $dist = $form.find('[name="calculated_distance"]');
              if ($dist.length) {
                $dist.val(resp.distance_m).trigger('input');
              }
            }
          }).fail(function (xhr, status) {
            if (window.console && console.warn) {
              console.warn('Price calc request failed:', status, xhr);
            }
          }).always(function () {
            pending = null;
          });
        }

        // Debounce
        var debounceTimer = null;
        function schedulePost() {
          if (debounceTimer) { clearTimeout(debounceTimer); }
          debounceTimer = setTimeout(function () { postData(); }, 300);
        }

        $form.on('change input', 'input, select, textarea', function () {
          schedulePost();
        });

        // initial call
        schedulePost();
      });
    }
  };

})(jQuery, Drupal, drupalSettings);