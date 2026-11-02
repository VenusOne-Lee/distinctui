/* distinctUI settings: the picture picker, the colour picker and a live mark preview. */
(function () {
	var hidden = document.getElementById('dui-mark-image');
	var preview = document.querySelector('.dui-mark-preview');
	var clear = document.querySelector('[data-dui-clear-image]');
	var initials = document.getElementById('dui-initials');
	var picker = document.getElementById('dui-signature-picker');
	var hex = document.getElementById('dui-signature');
	if (!preview) return;

	function showInitials() {
		preview.innerHTML = '';
		var span = document.createElement('span');
		span.textContent = (initials && initials.value) || (initials && initials.placeholder) || '';
		preview.appendChild(span);
	}

	var pick = document.querySelector('[data-dui-pick-image]');
	if (pick && window.wp && wp.media) {
		var frame;
		pick.addEventListener('click', function () {
			if (!frame) {
				frame = wp.media({ title: pick.textContent, library: { type: 'image' }, multiple: false });
				frame.on('select', function () {
					var a = frame.state().get('selection').first().toJSON();
					hidden.value = a.id;
					var url = (a.sizes && a.sizes.thumbnail && a.sizes.thumbnail.url) || a.url;
					preview.innerHTML = '';
					var img = document.createElement('img');
					img.src = url; img.alt = '';
					preview.appendChild(img);
					clear.hidden = false;
				});
			}
			frame.open();
		});
	}
	if (clear) clear.addEventListener('click', function () { hidden.value = '0'; clear.hidden = true; showInitials(); });
	if (initials) initials.addEventListener('input', function () { if (hidden.value === '0' || !hidden.value) showInitials(); });

	function setColour(c) { preview.style.setProperty('--mark-colour', c); }
	if (picker && hex) {
		picker.addEventListener('input', function () { hex.value = picker.value; setColour(picker.value); });
		hex.addEventListener('input', function () { if (/^#[0-9a-f]{6}$/i.test(hex.value)) { picker.value = hex.value; setColour(hex.value); } });
	}
	document.querySelectorAll('.dui-palette input').forEach(function (r) {
		r.addEventListener('change', function () {
			if (hex.value) return;
			var first = r.closest('.dui-palette').querySelector('.dui-rings i');
			if (first) setColour(first.style.getPropertyValue('--ring'));
		});
	});
})();
