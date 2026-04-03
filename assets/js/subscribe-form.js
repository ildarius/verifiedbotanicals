(function () {
	var form = document.querySelector('[data-subscribe-form]');
	if (!form) return;

	var emailInput = form.querySelector('input[name="email"]');
	var submitButton = form.querySelector('input[type="submit"]');
	var status = document.querySelector('[data-subscribe-status]');

	var setStatus = function (message, type) {
		if (!status) return;
		status.textContent = message;
		status.className = 'subscribe-status ' + type;
	};

	form.addEventListener('submit', function (event) {
		event.preventDefault();

		var formData = new FormData(form);

		if (submitButton) {
			submitButton.disabled = true;
			submitButton.value = 'Submitting...';
		}

		setStatus('', '');

		fetch(form.action, {
			method: 'POST',
			body: formData,
			headers: {
				'X-Requested-With': 'XMLHttpRequest'
			}
		})
			.then(function (response) {
				return response.json().catch(function () {
					throw new Error('Invalid server response.');
				}).then(function (payload) {
					if (!response.ok || !payload.ok) {
						throw new Error(payload.message || 'Subscription failed.');
					}

					return payload;
				});
			})
			.then(function (payload) {
				form.reset();
				setStatus(payload.message, 'success');
				if (emailInput) {
					emailInput.blur();
				}
			})
			.catch(function (error) {
				setStatus(error.message || 'Subscription failed. Please try again shortly.', 'error');
			})
			.finally(function () {
				if (submitButton) {
					submitButton.disabled = false;
					submitButton.value = 'Subscribe';
				}
			});
	});
}());
