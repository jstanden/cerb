{$wizard_uid = uniqid('slwiz')}
<style>{literal}
.cerb-sl-wizard--title { display:flex; align-items:center; gap:0.4em; margin-bottom:0.75em; font-weight:bold; font-size:1.5em; }
.cerb-sl-wizard .cerb-ui-form--field { margin-bottom:0.75em; }
.cerb-sl-wizard input[type=text] { width:100%; box-sizing:border-box; }
.cerb-sl-wizard--account { min-width:0; }
{/literal}</style>

<div id="{$wizard_uid}" class="cerb-sl-wizard" data-cerb-slack-wizard>
	<div class="cerb-sl-wizard--title"><span class="cerb-icons cerb-icon-comments"></span> Send a Slack message</div>

	<div class="cerb-sl-wizard--errors cerb-ui-panel--alert" data-cerb-sl-errors hidden style="margin-bottom:0.75em;"></div>

	<div class="cerb-ui-form">
		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Slack connected account</label>
			<div class="cerb-sl-wizard--account" data-cerb-sl-account></div>
		</div>

		<div class="cerb-ui-form--field">
			<label class="cerb-ui-form--label">Default channel</label>
			<input type="text" data-cerb-sl-channel value="#general" placeholder="#general or a channel ID" spellcheck="false">
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
{literal}
(function() {
	var root = document.getElementById('{/literal}{$wizard_uid}{literal}');
	if(!root) return;

	var rc = null;
	var accEl = root.querySelector('[data-cerb-sl-account]');
	if(window.CerbUI && CerbUI.RecordChooser) {
		try {
			rc = new CerbUI.RecordChooser(accEl, {
				context: 'cerberusweb.contexts.connected_account',
				emptyIcon: 'key',
				searchPlaceholder: 'Connected account…'
			});
		} catch(e) {}
	}

	var val = function(s) { var el = root.querySelector(s); return el ? el.value : ''; };
	var accountId = function() {
		var v = (rc && rc.getValue) ? rc.getValue() : null;
		var item = Array.isArray(v) ? v[0] : v;
		return (item && item.id) ? item.id : '';
	};

	var errorsEl = root.querySelector('[data-cerb-sl-errors]');
	var showErrors = function(list) {
		if(!errorsEl) return;
		errorsEl.textContent = (list && list.length) ? list.join(' ') : '';
		errorsEl.hidden = !(list && list.length);
	};

	var body = root.closest('[data-cerb-template-wizard-body]') || root.parentElement;

	body._cerbGetAnswers = function() {
		return { account_id: accountId(), channel: val('[data-cerb-sl-channel]') };
	};

	body._cerbValidate = function() {
		var errs = [];
		if(!accountId()) errs.push('Pick the Slack connected account.');
		showErrors(errs);
		return errs.length === 0;
	};
})();
{/literal}
</script>
