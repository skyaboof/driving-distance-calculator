(function (Drupal, $) {
  Drupal.behaviors.priceCalc = {
    attach: function (context, settings) {
      var endpoint = settings.drivingDistanceCalculator && settings.drivingDistanceCalculator.priceEndpoint;
      if (!endpoint) { return; }

      // Trigger recalculation on change for inputs with data-price-calc attr
      $(context).find('[data-price-calc]').once('priceCalc').on('change', function () {
        var $form = $(this).closest('form');
        var data = {};
        $form.find(':input[name]').each(function () {
          var $el = $(this);
          // handle checkboxes and multiple selects simplistically
          var name = $el.attr('name');
          if (!$el.val()) {
            data[name] = '';
            return;
          }
          // If checkbox group: collect values
          if ($el.is(':checkbox')) {
            if (!data[name]) { data[name] = []; }
            if ($el.is(':checked')) {
              data[name].push($el.val());
            }
          }
          else {
            data[name] = $el.val();
          }
        });
        $.ajax({
          url: endpoint,
          method: 'POST',
          data: JSON.stringify(data),
          contentType: 'application/json; charset=utf-8'
        }).done(function (resp) {
          if (resp.total !== undefined) {
            $form.find('input[name="calculated_cost"]').val('$' + resp.total.toFixed(2));
            $form.find('input[name="calculated_distance"]').val(resp.distance_m);
            $form.find('input[name="final_calculated_quote"]').val(resp.total);
            // If you want to visually update a markup element, add it with id and update here.
            $form.find('.estimated-total-ajax').html(function () {
              var html = '';
              if (resp.details && resp.details.length) {
                resp.details.forEach(function (d) { html += '<p>' + d + '</p>'; });
              }
              html += '<p><strong>Total Estimated Cost: $' + resp.total.toFixed(2) + '</strong></p>';
              return html;
            });
          }
        });
      });
    }
  };
})(Drupal, jQuery);
