/* global ajaxurl */
(function () {
	'use strict';

	const payloadEl = document.getElementById('wpcode-packs-payload');
	if (!payloadEl) {
		return;
	}
	const payload = JSON.parse(payloadEl.textContent);
	const packs = payload.packs;
	const nonce = payload.nonce;
	const i18n = payload.i18n;

	// ----- Install modal -----

	function openInstallModal(slug) {
		const data = packs[slug];
		if (!data || !data.snippets || !data.snippets.length) {
			return;
		}

		const tpl = document.getElementById('wpcode-packs-install-modal');
		const node = tpl.content.firstElementChild.cloneNode(true);
		document.body.appendChild(node);

		const titleEl = node.querySelector('.js-wpcode-rmodal-title');
		const subEl = node.querySelector('.js-wpcode-rmodal-sub span');
		const leadEl = node.querySelector('.js-wpcode-rmodal-lead');
		const listEl = node.querySelector('.wpcode-checklist');
		const trackEl = node.querySelector('.wpcode-progress-track');
		const labelEl = node.querySelector('.wpcode-progress-label');
		const progressFill = node.querySelector('.wpcode-progress-fill');
		const progressText = node.querySelector('.js-wpcode-progress-text');
		const progressPercent = node.querySelector('.js-wpcode-progress-percent');
		const successEl = node.querySelector('.wpcode-success-state');
		const successHeading = node.querySelector('.js-wpcode-success-heading');
		const successBody = node.querySelector('.js-wpcode-success-body');
		const cancelBtn = node.querySelector('.js-wpcode-rmodal-cancel');
		const primaryBtn = node.querySelector('.js-wpcode-rmodal-primary');
		const primaryLabel = primaryBtn.querySelector('span');

		titleEl.textContent = i18n.install_title.replace('%s', data.name);
		subEl.textContent = i18n.snippets_subtitle
			.replace('%1$d', data.snippets.length)
			.replace('%2$s', data.category);
		leadEl.textContent = i18n.confirm_lead;

		data.snippets.forEach(function (s) {
			const row = document.createElement('li');
			row.className = 'wpcode-check-row pending';
			const label = document.createElement('span');
			label.className = 'label-name';
			label.textContent = s;
			row.appendChild(label);
			listEl.appendChild(row);
		});

		cancelBtn.textContent = i18n.cancel;
		primaryLabel.textContent = i18n.install_button;

		function close() {
			node.remove();
		}
		node.addEventListener('click', function (e) {
			if (e.target === node) {
				close();
			}
		});
		cancelBtn.addEventListener('click', close);

		// Guard so the success-state primary button (rebound to a redirect) can't
		// re-trigger an install when clicked.
		let installing = false;
		primaryBtn.addEventListener('click', function () {
			if (installing) {
				return;
			}
			installing = true;
			titleEl.textContent = i18n.installing_title.replace('%s', data.name);
			primaryBtn.disabled = true;
			primaryLabel.textContent = i18n.installing_button;
			cancelBtn.style.display = 'none';

			const rows = listEl.querySelectorAll('.wpcode-check-row');
			let step = 0;
			trackEl.hidden = false;
			labelEl.hidden = false;
			progressText.textContent = i18n.creating_snippets;

			function tick() {
				if (step >= rows.length) {
					return;
				}
				rows[step].classList.remove('pending');
				rows[step].classList.add('active');
			}
			tick();
			const interval = setInterval(function () {
				const r = rows[step];
				if (!r) {
					return;
				}
				r.classList.remove('active');
				r.classList.add('done');
				step += 1;
				const pct = Math.round((step / rows.length) * 100);
				progressFill.style.width = pct + '%';
				progressPercent.textContent = pct + '%';
				if (step >= rows.length) {
					clearInterval(interval);
				} else {
					tick();
				}
			}, 520);

			const fd = new FormData();
			fd.append('action', 'wpcode_install_pack');
			fd.append('nonce', nonce);
			fd.append('slug', slug);

			fetch(ajaxurl, { method: 'POST', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (resp) {
					if (!resp.success) {
						clearInterval(interval);
						alert(resp.data && resp.data.message ? resp.data.message : 'Install failed');
						close();
						return;
					}
					function showSuccess() {
						if (step < rows.length) {
							setTimeout(showSuccess, 200);
							return;
						}
						titleEl.textContent = i18n.installed_title.replace('%s', data.name);
						leadEl.hidden = true;
						listEl.hidden = true;
						trackEl.hidden = true;
						labelEl.hidden = true;
						successEl.hidden = false;

						// Report the real outcome, not an optimistic "all activated".
						// A snippet can fail to create (failed_options) or fail its
						// activation safety check (activation_failed).
						const result = (resp.data && resp.data.result) || {};
						const total = data.snippets.length;
						const issues =
							((result.failed_options || []).length) +
							((result.activation_failed || []).length);
						// Prefer the server's real active count; fall back to total - issues.
						const active = typeof result.active_count === 'number'
							? result.active_count
							: Math.max(0, total - issues);

						if (active < total) {
							successHeading.textContent = i18n.success_heading_partial
								.replace('%1$d', active)
								.replace('%2$d', total);
							successBody.textContent = i18n.success_body_partial;
						} else {
							successHeading.textContent = i18n.success_heading.replace('%d', total);
							successBody.textContent = i18n.success_body;
						}
						primaryBtn.disabled = false;
						primaryLabel.textContent = i18n.open_dashboard;
						cancelBtn.style.display = '';
						cancelBtn.textContent = i18n.back_to_packs;

						primaryBtn.onclick = function () {
							window.location.href = resp.data.redirect_url;
						};
					}
					showSuccess();
				})
				.catch(function () {
					clearInterval(interval);
					alert('Install failed');
					close();
				});
		});
	}

	// ----- Card click → install -----
	document.querySelectorAll('.js-wpcode-open-install').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			e.stopPropagation();
			const card = btn.closest('.wpcode-list-item');
			if (!card) {
				return;
			}
			openInstallModal(card.dataset.packSlug);
		});
	});

	// ----- Toggle -----
	document.querySelectorAll('.js-wpcode-toggle-option').forEach(function (el) {
		el.addEventListener('click', function () {
			const row = el.closest('.wpcode-snippet-row');
			const detail = el.closest('.wpcode-pack-detail');
			if (!row || !detail) {
				return;
			}
			const libraryId = row.dataset.libraryId;
			const slug = detail.dataset.packSlug;
			const turnOn = !el.classList.contains('is-on');

			function setToggle(state) {
				el.classList.toggle('is-on', state);
				el.setAttribute('aria-checked', state ? 'true' : 'false');
				row.classList.toggle('is-inactive', !state);
			}

			// Optimistic update, reconciled to the server's real state below.
			setToggle(turnOn);

			const fd = new FormData();
			fd.append('action', 'wpcode_toggle_pack_option');
			fd.append('nonce', detail.dataset.nonce);
			fd.append('slug', slug);
			fd.append('library_id', libraryId);
			fd.append('active', turnOn ? '1' : '0');

			fetch(ajaxurl, { method: 'POST', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (resp) {
					if (resp && resp.success && resp.data) {
						// Sync to the real state — activation can be refused.
						setToggle(!!resp.data.active);
						if (resp.data.activation_refused) {
							window.alert(i18n.activation_refused);
						}
					} else {
						setToggle(!turnOn);
					}
				})
				.catch(function () {
					setToggle(!turnOn);
				});
		});
		el.addEventListener('keydown', function (e) {
			if (e.key === ' ' || e.key === 'Enter') {
				e.preventDefault();
				el.click();
			}
		});
	});

	// ----- Add / Reinstall option -----
	document.querySelectorAll('.js-wpcode-add-option').forEach(function (btn) {
		btn.addEventListener('click', function () {
			const row = btn.closest('.wpcode-snippet-row');
			const detail = btn.closest('.wpcode-pack-detail');
			if (!row || !detail) {
				return;
			}
			btn.disabled = true;

			const fd = new FormData();
			fd.append('action', 'wpcode_add_pack_option');
			fd.append('nonce', detail.dataset.nonce);
			fd.append('slug', detail.dataset.packSlug);
			fd.append('library_id', row.dataset.libraryId);

			fetch(ajaxurl, { method: 'POST', body: fd })
				.then(function (r) { return r.json(); })
				.then(function (resp) {
					// Already installed (e.g. a concurrent add) is not an error —
					// the snippet is present, so just refresh to show it.
					if (resp.success || (resp.data && resp.data.code === 'already_installed')) {
						window.location.reload();
					} else {
						btn.disabled = false;
						alert(resp.data && resp.data.message ? resp.data.message : 'Could not add option');
					}
				})
				.catch(function () {
					btn.disabled = false;
				});
		});
	});

	// ----- Remove pack -----
	document.querySelectorAll('.js-wpcode-remove-pack').forEach(function (btn) {
		btn.addEventListener('click', function () {
			const detail = btn.closest('.wpcode-pack-detail');
			if (!detail) {
				return;
			}
			const packName = document.querySelector('.wpcode-detail-head h2').textContent;
			const snippetCount = detail.querySelectorAll('.wpcode-snippet-row').length;
			const msg = i18n.confirm_remove
				.replace('%1$s', packName)
				.replace('%2$d', snippetCount);

			function doRemove() {
				const fd = new FormData();
				fd.append('action', 'wpcode_remove_pack');
				fd.append('nonce', detail.dataset.nonce);
				fd.append('slug', detail.dataset.packSlug);

				fetch(ajaxurl, { method: 'POST', body: fd })
					.then(function (r) { return r.json(); })
					.then(function (resp) {
						if (resp.success) {
							window.location.href = resp.data.redirect_url;
						}
					});
			}

			// Use WPCode's jquery-confirm dialog to match the rest of the admin,
			// falling back to the native confirm if it isn't available.
			const jq = window.jQuery;
			if (jq && typeof jq.confirm === 'function') {
				jq.confirm({
					title: i18n.remove_title,
					content: msg,
					type: 'blue',
					backgroundDismiss: true,
					buttons: {
						confirm: {
							text: i18n.remove_confirm_button,
							btnClass: 'wpcode-btn-confirm',
							action: doRemove
						},
						cancel: {
							text: i18n.cancel
						}
					}
				});
			} else if (window.confirm(msg)) {
				doRemove();
			}
		});
	});
})();
