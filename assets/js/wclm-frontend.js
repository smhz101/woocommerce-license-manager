jQuery( document ).ready(
	function ($) {
		// On variation selection
		$( ".variations_form" ).on(
			"found_variation",
			function (event, variation) {
				var is_license_product = variation.is_license_product === "yes";

				if (is_license_product) {
					var default_duration = variation.default_license_duration;
					if ( ! default_duration) {
						default_duration = 1;
					}

					var license_field_html =
					'<div class="license-duration">' +
					'<label for="license_duration">' +
					wclm_frontend_params.label +
					"</label>" +
					'<input type="number" id="license_duration" name="license_duration" value="' +
					default_duration +
					'" min="1" max="5" />' +
					"</div>";

					$( "#wclm_license_duration_field" ).html( license_field_html );
				} else {
					$( "#wclm_license_duration_field" ).empty();
				}
			}
		);

		// When no variation is selected
		$( ".variations_form" ).on(
			"reset_data",
			function () {
				$( "#wclm_license_duration_field" ).empty();
			}
		);
	}
);
