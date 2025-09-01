(function (Drupal, drupalSettings, once) {
  Drupal.behaviors.drivingDistanceAdvanced = {
    attach: function (context) {
      once('drivingDistanceAdvanced', 'body', context).forEach(function () {
        // Configuration & API key
        const cfg = drupalSettings.drivingDistanceCalculator || {};
        const apiKey = cfg.googleApiKey || '';

        // Form fields
        const originInput        = document.querySelector('input[name="origin_address"]');
        const destinationInput   = document.querySelector('input[name="destination_address"]');
        const distanceField      = document.querySelector('input[name="calculated_distance"]');
        const costField          = document.querySelector('input[name="calculated_cost"]');
        const weightInput        = document.querySelector('input[name="shipment_weight"]');
        const fragileInput       = document.querySelector('input[name="fragile_items"]');
        const priorityInput      = document.querySelector('input[name="priority_shipping"]');
        const deliveryTimeInput  = document.querySelector('input[name="delivery_time"]');

        // Core cost calculation (from Code B), reading distanceField.value
        function recalc() {
          if (!distanceField || !costField) return;
          const km = parseFloat(distanceField.value) || 0;

          let cost = parseFloat(cfg.base_fee) || 0;
          cost += km * (parseFloat(cfg.per_km_rate) || 0.5);

          const weight   = parseFloat(weightInput?.value) || 0;
          const fragile  = fragileInput?.checked || false;
          const priority = priorityInput?.checked || false;
          const deliveryTime = deliveryTimeInput?.value;

          // Weight tier surcharge
          if (weight > 30) {
            cost += km * 0.5;
          }
          else if (weight > 10) {
            cost += km * 0.2;
          }

          // Fragile surcharge
          if (fragile) {
            cost += parseFloat(cfg.fragile_surcharge_flat || 10);
          }

          // Priority multiplier
          if (priority) {
            const mult = parseFloat(cfg.priority_multiplier || 1.25);
            cost *= mult;
          }

          // After‐hours surcharge
          if (deliveryTime) {
            const hour = new Date(deliveryTime).getHours();
            if (hour < 8 || hour >= 18) {
              const pct = parseFloat(cfg.after_hours_surcharge_pct || 0);
              cost *= (1 + pct / 100);
            }
          }

          costField.value = cost.toFixed(2);
        }

        // Initialize Google Maps distance calculator (from Code A)
        function initMaps() {
          if (!originInput || !destinationInput) return;

          const originAuto = new google.maps.places.Autocomplete(originInput);
          const destAuto   = new google.maps.places.Autocomplete(destinationInput);
          const service    = new google.maps.DistanceMatrixService();

          function calculateDistance() {
            const origin = originInput.value.trim();
            const dest   = destinationInput.value.trim();
            if (!origin || !dest) return;

            service.getDistanceMatrix({
              origins:      [origin],
              destinations: [dest],
              travelMode:   google.maps.TravelMode.DRIVING,
              unitSystem:   google.maps.UnitSystem.METRIC
            }, function (response, status) {
              if (status !== 'OK') return;
              const el = response.rows?.[0]?.elements?.[0];
              if (!el || el.status !== 'OK') return;

              const meters = el.distance.value;
              const km     = meters / 1000;
              
              // Populate distance field and run cost recalc
              distanceField.value = km.toFixed(2);
              recalc();
            });
          }

          originAuto.addListener('place_changed', calculateDistance);
          destAuto.addListener('place_changed', calculateDistance);

          // Recalc when ancillary inputs change
          ['change', 'keyup', 'blur'].forEach(evt => {
            [weightInput, fragileInput, priorityInput, deliveryTimeInput].forEach(inp => {
              if (inp) inp.addEventListener(evt, calculateDistance);
            });
          });
        }

        // Manual recalc if someone edits the distance directly
        function initManualHooks() {
          if (!distanceField) return;
          ['change', 'keyup', 'blur'].forEach(evt => {
            distanceField.addEventListener(evt, recalc);
            [weightInput, fragileInput, priorityInput, deliveryTimeInput].forEach(inp => {
              if (inp) inp.addEventListener(evt, recalc);
            });
          });
        }

        // Load Google Maps script if needed
        if (apiKey) {
          window.initDrivingDistCalcAdvanced = initMaps;
          if (!document.getElementById('google-maps-api')) {
            const script = document.createElement('script');
            script.id    = 'google-maps-api';
            script.src   = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places&callback=initDrivingDistCalcAdvanced`;
            script.async = true;
            script.defer = true;
            document.head.appendChild(script);
          }
          else if (window.google && window.google.maps) {
            initMaps();
          }
        }

        initManualHooks();
        // One initial recalc in case distance was prefilled
        recalc();
      });
    }
  };
})(Drupal, drupalSettings, once);
