jQuery(function ($) {
	console.log("Initializing Korapay Payment modal");
	var paymentRequest = async function () {
		var $form = $("form#payment-form, form#order_review"),
			korapay_referenceKey = $form.find("input.korapay_referenceKey");

		//Empty the reference Input
		korapay_referenceKey.val("");

		//Make sure that the amount is a number
		const total = Number(korapay_params.amount);

		var errorCallback = function (response) {
			let message = "failure";

			if (response.reference === null) {
				$form.append(
					'<input type="hidden" class="korapay_referenceKey" name="korapay_referenceKey" value="' +
						korapay_params.orderId +
						'"/>'
				);
				$form.append(
					'<input type="hidden" class="modal_failure" name="modal_failure" value="' +
						message +
						'"/>'
				);
				$form.submit();
				return;
			}

			//Append the reference and error to the form
			$form.append(
				'<input type="hidden" class="korapay_referenceKey" name="korapay_referenceKey" value="' +
					response.reference +
					'"/>'
			);
			$form.append(
				'<input type="hidden" class="trans_failure" name="trans_failure" value="' +
					message +
					'"/>'
			);

			//Subnit the form to the payment gateway
			$form.submit();

			//Create a backdrop
			$("body").block({
				message: null,
				overlayCSS: {
					background: "#fff",
					opacity: 0.6,
				},
				css: {
					cursor: "wait",
				},
			});
		};
		var successCallback = function (response) {
			//Include the reference in the form
			$form.append(
				'<input type="hidden" class="korapay_referenceKey" name="korapay_referenceKey" value="' +
					response.reference +
					'"/>'
			);
			$form.append('<input type="hidden" class="order_id" name="order_id" value="' + korapay_params.orderId + '"/>');

			//Subnit the form to the payment gateway
			$form.submit();

			$("body").block({
				message: "Processing Payment",
				overlayCSS: {
					background: "#000",
					opacity: 0.8,
				},
				css: {
					cursor: "wait",
				},
			});
		};

		//Initialize the transaction
		Korapay.initialize({
			key: korapay_params.Key,
			amount: total,
			currency: "NGN",
			customer: {
				name: korapay_params.name,
				email: korapay_params.email,
			},
			onClose: function () {
				//Remove the backdrop
				$(this.el).unblock();
			},
			onSuccess: function (response) {
				successCallback(response);
				return true;
			},
			onFailed: function (response) {
				errorCallback(response);
				return false;
			},
		});

		return false;
	};

	jQuery("#korapay-payment-button").click(function () {
		return paymentRequest();
	});
});
