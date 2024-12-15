jQuery(document).ready(function ($) {
	const productSelect = $("#product-id");
	const variationRow = $("#variation-row");
	const variationSelect = $("#variation-id");

	productSelect.on("change", function () {
		const productId = $(this).val();

		// Clear existing variations
		variationSelect.html(
			'<option value="">' + wclmAdmin.i18n.select_variation + "</option>"
		);
		variationRow.hide();

		if (productId) {
			$.ajax({
				url: wclmAdmin.ajax_url,
				type: "GET",
				data: {
					action: "get_variations",
					product_id: productId,
				},
				dataType: "json",
				success: function (response) {
					if (
						response.success &&
						response.data.variations &&
						response.data.variations.length > 0
					) {
						response.data.variations.forEach(function (variation) {
							variationSelect.append(
								'<option value="' +
									variation.id +
									'">' +
									variation.name +
									"</option>"
							);
						});
						variationRow.show();
					}
				},
				error: function (xhr, status, error) {
					console.error("Error fetching variations:", error);
				},
			});
		}
	});
});
