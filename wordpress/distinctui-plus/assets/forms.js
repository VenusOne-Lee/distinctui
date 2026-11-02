/*
 * distinctUI+ forms: send without a reload, and show errors in place.
 * Errors are black and bold, name the field and say the fix (rule 10).
 * Without this script the form still works, with a reload.
 */
(function () {
	'use strict';
	var t = window.duiForms || {};

	function clear(form) {
		form.querySelectorAll('.dui-field-error').forEach(function (e) { e.remove(); });
		form.querySelectorAll('[aria-invalid]').forEach(function (e) { e.removeAttribute('aria-invalid'); });
	}

	function show(form, ok, message, errors) {
		var msg = form.querySelector('.dui-form-msg');
		clear(form);
		var first = null;
		Object.keys(errors || {}).forEach(function (name) {
			var field = form.querySelector('[name="' + CSS.escape(name) + '"]');
			if (!field) return;
			var p = document.createElement('p');
			p.className = 'dui-field-error is-style-error';
			p.id = field.id + '-error';
			p.textContent = errors[name];
			field.setAttribute('aria-invalid', 'true');
			field.setAttribute('aria-describedby', ((field.getAttribute('aria-describedby') || '') + ' ' + p.id).trim());
			field.closest('.dui-field').appendChild(p);
			if (!first) first = field;
		});
		if (msg) {
			msg.hidden = false;
			msg.textContent = message;
			msg.classList.toggle('is-style-error', !ok);
			msg.classList.toggle('is-sent', ok);
		}
		if (ok) {
			form.querySelectorAll('.dui-field, .dui-form-actions').forEach(function (e) { e.hidden = true; });
			if (msg) { msg.setAttribute('tabindex', '-1'); msg.focus(); }
		} else if (first) {
			first.focus();
		}
	}

	document.querySelectorAll('form[data-dui-form]').forEach(function (form) {
		form.noValidate = true;
		var key = form.getAttribute('data-dui-form');

		// Answers after a reload (no script on the way out, or it failed).
		var q = new URLSearchParams(location.search);
		var msgEl = form.querySelector('.dui-form-msg');
		if (q.get('dui_sent') === key) show(form, true, (msgEl && msgEl.getAttribute('data-sent')) || '', {});
		if (q.get('dui_error') === key) {
			var bad = {};
			try { bad = JSON.parse(q.get('dui_bad') || '{}'); } catch (e) {}
			show(form, false, q.get('dui_msg') || '', bad);
		}

		form.addEventListener('submit', function (ev) {
			ev.preventDefault();
			var button = form.querySelector('button[type="submit"]');
			var label = button.textContent;
			button.disabled = true;
			button.textContent = t.sending || 'Sending…';
			fetch(form.getAttribute('action') || location.href, {
				method: 'POST',
				body: new FormData(form),
				headers: { 'X-DUI-Form': '1' },
				credentials: 'same-origin'
			}).then(function (r) { return r.json(); }).then(function (data) {
				show(form, !!data.ok, data.message || '', data.errors || {});
			}).catch(function () {
				show(form, false, t.offline || 'That didn\'t send. Check your connection and try again.', {});
			}).then(function () {
				button.disabled = false;
				button.textContent = label;
			});
		});
	});
})();
