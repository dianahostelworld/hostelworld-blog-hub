/* "Add hostels" in the Classic Editor: pick a city, pick hostels, insert the tag. */
(function ($) {
	'use strict';

	var cfg = window.hwHostels || {};
	var $dlg;

	function esc(s) {
		return String(s == null ? '' : s).replace(/[&<>"]/g, function (c) {
			return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c];
		});
	}

	function build() {
		var guess = cfg.guess || {};
		$dlg = $(
			'<div class="hw-hostels-dialog" hidden role="dialog" aria-modal="true" aria-labelledby="hw-hostels-h">' +
			'<div class="hw-hostels-dialog__box">' +
			'<div class="hw-hostels-dialog__head"><h2 id="hw-hostels-h">Add Hostelworld hostels</h2>' +
			'<button type="button" class="button-link hw-x" aria-label="Close"><span class="dashicons dashicons-no-alt"></span></button></div>' +
			'<div class="hw-hostels-dialog__main">' +
			'<div class="hw-hostels-dialog__row">' +
			'<label>City<input type="text" class="hw-city" placeholder="lisbon"></label>' +
			'<label>Country<input type="text" class="hw-country" placeholder="portugal"></label>' +
			'<button type="button" class="button hw-find">Find hostels</button></div>' +
			'<p class="description">As they appear in a Hostelworld web address: hostelworld.com/hostels/europe/<b>portugal</b>/<b>lisbon</b>/</p>' +
			'<p class="hw-hostels-dialog__msg"></p>' +
			'<div class="hw-pick" hidden>' +
			'<div class="hw-hostels-dialog__row">' +
			'<label><span><input type="radio" name="hw-mode" value="top" checked> Top-rated, chosen automatically</span></label>' +
			'<label><span><input type="radio" name="hw-mode" value="pick"> Let me choose</span></label></div>' +
			'<div class="hw-hostels-dialog__row hw-top">' +
			'<label>How many<select class="hw-count"><option>2</option><option>3</option><option selected>4</option><option>6</option><option>8</option></select></label></div>' +
			'<div class="hw-hostels-dialog__list hw-list" hidden></div>' +
			'<div class="hw-hostels-dialog__row">' +
			'<label>Layout<select class="hw-layout"><option value="grid">Grid</option><option value="row">One sliding row</option></select></label>' +
			'<label>Heading (optional)<input type="text" class="hw-title" style="width:260px" placeholder="Top-rated hostels in …"></label></div>' +
			'</div></div>' +
			'<div class="hw-hostels-dialog__foot"><code class="hw-preview"></code>' +
			'<button type="button" class="button button-primary hw-insert" disabled>Insert</button></div>' +
			'</div></div>'
		).appendTo('body');

		$dlg.find('.hw-city').val(guess.city || '');
		$dlg.find('.hw-country').val(guess.country || '');

		$dlg.on('click', '.hw-x', close);
		$dlg.on('click', function (e) { if (e.target === $dlg[0]) { close(); } });
		$(document).on('keydown', function (e) { if (e.key === 'Escape' && !$dlg.prop('hidden')) { close(); } });
		$dlg.on('click', '.hw-find', find);
		$dlg.on('keydown', '.hw-city, .hw-country', function (e) { if (e.key === 'Enter') { e.preventDefault(); find(); } });
		$dlg.on('change input', 'input, select', preview);
		$dlg.on('change', 'input[name=hw-mode]', function () {
			var pick = this.value === 'pick';
			$dlg.find('.hw-list').prop('hidden', !pick);
			$dlg.find('.hw-top').prop('hidden', pick);
			preview();
		});
		$dlg.on('click', '.hw-insert', insert);
	}

	function msg(text, error) {
		$dlg.find('.hw-hostels-dialog__msg').text(text || '').toggleClass('is-error', !!error);
	}

	function find() {
		var city = $.trim($dlg.find('.hw-city').val()).toLowerCase();
		var country = $.trim($dlg.find('.hw-country').val()).toLowerCase();
		$dlg.find('.hw-pick').prop('hidden', true);
		$dlg.find('.hw-insert').prop('disabled', true);
		$dlg.find('.hw-preview').text('');
		msg('Looking up hostels…');

		$.post(cfg.ajax, { action: 'hw_hostels_find', nonce: cfg.nonce, city: city, country: country })
			.done(function (r) {
				if (!r || !r.success) {
					msg(r && r.data && r.data.message ? r.data.message : 'Something went wrong.', true);
					return;
				}
				if (!r.data.hostels.length) {
					msg('Hostelworld returned no hostels for ' + r.data.city + ' right now.', true);
					return;
				}
				var html = '';
				$.each(r.data.hostels, function (i, h) {
					html += '<label><input type="checkbox" value="' + h.id + '"> <span>' + esc(h.name) + (h.type ? ' <em style="color:#636C7D">(' + esc(h.type) + ')</em>' : '') + '</span>' +
						'<span class="meta">' + (h.rating ? esc(h.rating) + ' · ' + esc(h.reviews) + ' reviews' : 'No rating yet') +
						(h.price ? ' · from ' + esc(h.price) : '') + '</span></label>';
				});
				$dlg.find('.hw-list').html(html);
				$dlg.data('city', city).data('country', country);
				var nh = $.grep(r.data.hostels, function (h) { return !h.type; }).length;
				msg(r.data.hostels.length + ' places to stay in ' + r.data.city + ' (' + nh + ' hostels), best rated first. Top-rated picks hostels first.');
				$dlg.find('.hw-pick').prop('hidden', false);
				preview();
			})
			.fail(function () { msg('Could not reach the server. Try again.', true); });
	}

	function tag() {
		var city = $dlg.data('city');
		if (!city) { return ''; }
		var out = '[hw_hostels city="' + city + '" country="' + $dlg.data('country') + '"';
		if ($dlg.find('input[name=hw-mode]:checked').val() === 'pick') {
			var ids = $dlg.find('.hw-list input:checked').map(function () { return this.value; }).get();
			if (!ids.length) { return ''; }
			out += ' ids="' + ids.join(',') + '"';
		} else {
			out += ' count="' + $dlg.find('.hw-count').val() + '"';
		}
		if ($dlg.find('.hw-layout').val() === 'row') { out += ' layout="row"'; }
		var title = $.trim($dlg.find('.hw-title').val()).replace(/["\[\]]/g, '');
		if (title) { out += ' title="' + title + '"'; }
		return out + ']';
	}

	function preview() {
		var t = tag();
		$dlg.find('.hw-preview').text(t);
		$dlg.find('.hw-insert').prop('disabled', !t);
	}

	function insert() {
		var t = tag();
		if (!t) { return; }
		window.send_to_editor('\n' + t + '\n');
		close();
	}

	function open() {
		if (!$dlg) { build(); }
		$dlg.prop('hidden', false);
		$dlg.find($dlg.find('.hw-city').val() ? '.hw-find' : '.hw-city').trigger('focus');
	}

	function close() {
		$dlg.prop('hidden', true);
	}

	$(document).on('click', '.hw-hostels-open', function (e) {
		e.preventDefault();
		open();
	});
})(jQuery);
