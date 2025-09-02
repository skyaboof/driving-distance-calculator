(function ($, Drupal, drupalSettings) {
  'use strict';

  Drupal.behaviors.drivingDistancePriceCalc = {
    attach: function (context, settings) {
      var endpoint = drupalSettings.drivingDistanceCalculator && drupalSettings.drivingDistanceCalculator.priceEndpoint;
      if (!endpoint) {
        // Endpoint not provided; nothing to do.
        return;
      }

      // Try to find the moving_quote webform on the page. This looks for common Webform form IDs.
      var $forms = $(context).find('form').filter(function () {
        var id = (this.id || '').toString();
        if (!id) {
          return false;
        }
        // Match webform_client_form_moving_quote or webform_submission_moving_quote_add_form patterns.
        return id.indexOf('moving_quote') !== -1;
      }).once('driving-distance-price');

      if (!$forms.length) {
        return;
      }

      $forms.each(function () {
        var $form = $(this);

        // Fields we commonly want to send. Add or remove names to match your form.
        var fieldNames = [
          'service_type',
          'move_size_residential',
          'origin_address',
          'destination_address',
          'origin_access_conditions',
          'origin_stairs_flights',
          'destination_access_conditions',
          'destination_stairs_flights',
          'parking_situation',
          'packing_services',
          'cleaning_services',
          'vehicle_helper_needs',
          'service_speed'
        ];

        function collectData() {
          var payload = {};

          fieldNames.forEach(function (name) {
            // Handle checkboxes arrays (name[]), radios, selects and textfields.
            var $els = $form.find('[name="' + name + '"], [name="' + name + '[]"]');
            if (!$els.length) {
              return;
            }

            // Multiple elements with same name -> treat as array (checkboxes).
            if ($els.length > 1) {
              var vals = [];
              $els.each(function () {
                var $el = $(this);
                if ($el.is(':checkbox')) {
                  if ($el.is(':checked')) {
                    vals.push($el.val());
                  }
                } else if ($el.is(':radio')) {
                  if ($el.is(':checked')) {
                    vals.push($el.val());
                  }
                } else {
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

          // Also include any additional raw origin/destination values if present
          var extra = ['calculated_cost', 'calculated_distance', 'base_price'];
          extra.forEach(function (n) {
            var $e = $form.find('[name="' + n + '"]');
            if ($e.length) {
              payload[n] = $e.val();
            }
          });

          return payload;
        }

        var pending = null;
        function postData() {
          var data = collectData();

          // Cancel a pending request if the user is changing things rapidly.
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
            if (!resp) {
              return;
            }

            // Write formatted total to calculated_cost field (matches your form field).
            if (resp.total !== undefined) {
              var total = resp.total;
              // If backend returns number, format to currency with two decimals.
              var formatted = (typeof total === 'number') ? ('$' + total.toFixed(2)) : total;
              var $cost = $form.find('[name="calculated_cost"]');
              if ($cost.length) {
                $cost.val(formatted).trigger('change');
              }
            }

            // Distance meters -> calculated_distance field (or distance_m)
            if (resp.distance_m !== undefined) {
              var $dist = $form.find('[name="calculated_distance"]');
              if ($dist.length) {
                $dist.val(resp.distance_m).trigger('change');
              }
            }
          }).fail(function (xhr, status) {
            // Silently fail but log for debugging.
            if (window.console && console.warn) {
              console.warn('Price calc request failed:', status, xhr);
            }
          }).always(function () {
            pending = null;
          });
        }

        // Debounce changes to avoid excessive requests.
        var debounceTimer = null;
        function schedulePost() {
          if (debounceTimer) {
            clearTimeout(debounceTimer);
          }
          debounceTimer = setTimeout(function () {
            postData();
          }, 300);
        }

        // Bind to changes on relevant inputs.
        $form.on('change input', 'input, select, textarea', function () {
          schedulePost();
        });

        // Run once on attach to populate initial values.
        schedulePost();
      });
    }
  };
})(jQuery, Drupal, drupalSettings);