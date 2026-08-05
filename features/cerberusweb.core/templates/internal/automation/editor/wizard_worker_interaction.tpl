{$wizard_uid = uniqid('wiwiz')}
<style>{literal}
.cerb-wi-wizard--title { display:flex; align-items:center; gap:0.4em; margin-bottom:0.75em; font-weight:bold; font-size:1.5em; }
.cerb-wi-wizard .cerb-ui-form--field { margin-bottom:0.75em; }
.cerb-wi-wizard input[type=text], .cerb-wi-wizard textarea { width:100%; box-sizing:border-box; }
{/literal}</style>

<div id="{$wizard_uid}" class="cerb-wi-wizard" data-cerb-worker-interaction-wizard>
	<div class="cerb-wi-wizard--title"><span class="cerb-icons cerb-icon-form"></span> Worker Interaction</div>

	<div class="cerb-wi-wizard--errors cerb-ui-panel--alert" data-cerb-wi-errors hidden style="margin-bottom:0.75em;"></div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Title</label>
			<input type="text" data-cerb-wi-title value="Worker Interaction" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Prompt</label>
			<input type="text" data-cerb-wi-prompt value="What would you like to do?" spellcheck="false">
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Answer type</label>
			<select data-cerb-wi-type>
				<option value="text">Short text</option>
				<option value="textarea">Long text</option>
			</select>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Result message</label>
			<textarea data-cerb-wi-result rows="3" spellcheck="false">{literal}Thanks! You entered: {{prompt_input}}{/literal}</textarea>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{literal}
(function() {
	var root = document.getElementById('{/literal}{$wizard_uid}{literal}');
	if(!root) return;

	var sel = root.querySelector('[data-cerb-wi-type]');
	if(window.CerbUI && CerbUI.SelectMenu) { try { new CerbUI.SelectMenu(sel); } catch(e) {} }

	var val = function(s) { var el = root.querySelector(s); return el ? el.value : ''; };

	var errorsEl = root.querySelector('[data-cerb-wi-errors]');
	var showErrors = function(list) {
		if(!errorsEl) return;
		errorsEl.textContent = (list && list.length) ? list.join(' ') : '';
		errorsEl.hidden = !(list && list.length);
	};

	var body = root.closest('[data-cerb-template-wizard-body]') || root.parentElement;

	body._cerbGetAnswers = function() {
		return {
			title: val('[data-cerb-wi-title]'),
			prompt: val('[data-cerb-wi-prompt]'),
			answer_type: (sel && sel.value) || 'text',
			result: val('[data-cerb-wi-result]')
		};
	};

	body._cerbValidate = function() {
		var errs = [];
		if(!val('[data-cerb-wi-title]').trim()) errs.push('Enter a title.');
		if(!val('[data-cerb-wi-prompt]').trim()) errs.push('Enter a prompt.');
		showErrors(errs);
		return errs.length === 0;
	};
})();
{/literal}
</script>
