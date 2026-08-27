{* "Simulate initial state" popup: admin-only simulator priming form. Each prime-input descriptor (trigger scope
   + automation #inputs) renders directly as a cerb-ui-form control — grouped into "Event inputs" and "Automation
   inputs" panels. On Run/Save the filled <form> is handed up to the opener (peek_edit), which posts it to
   submitPrimeState → initial-state YAML for the Run Input editor (and re-fires the run on "Run"). *}
{$uniqid = uniqid('primeState')}
<div id="{$uniqid}" data-cerb-dialog-title="Simulate initial state">
	<form class="cerb-ui-form">
		{if $scope_inputs}
		<div class="cerb-ui-form--section">
			<div class="cerb-ui-form--section-head">Event inputs</div>
			<div class="cerb-ui-form--section-body">
				{foreach from=$scope_inputs item=field}{include file="devblocks:cerberusweb.core::internal/automation/editor/prime_state_field.tpl" field=$field}{/foreach}
			</div>
		</div>
		{/if}

		{if $automation_inputs}
		<div class="cerb-ui-form--section">
			<div class="cerb-ui-form--section-head">Automation inputs</div>
			<div class="cerb-ui-form--section-body">
				{foreach from=$automation_inputs item=field}{include file="devblocks:cerberusweb.core::internal/automation/editor/prime_state_field.tpl" field=$field}{/foreach}
			</div>
		</div>
		{/if}

		{* Which button was pressed ('run' | 'save') — the opener reads it client-side; submitPrimeState ignores it. *}
		<input type="hidden" name="prompts[prime]" data-prime-mode value="run">

		<div class="cerb-u-flex cerb-u-gap-2">
			<button type="button" class="cerb-ui-button" data-prime-run style="flex:1 1;">Run <span class="cerb-icons cerb-icon-play"></span></button>
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle" data-prime-save style="flex:1 1;"><span class="cerb-icons cerb-icon-download"></span> Save to input</button>
		</div>
	</form>

	<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		var $div = $('#{$uniqid}');
		var $popup = genericAjaxPopupFind($div);
		var $form = $div.find('form.cerb-ui-form');

		// Enhance record choosers (single + multiple) from their seed markup.
		if(window.CerbUI && CerbUI.RecordChooser) {
			$div.find('[data-prime-chooser]').each(function() {
				new CerbUI.RecordChooser(this, {
					context: this.getAttribute('data-context'),
					name: this.getAttribute('data-name'),
					// A scoped input offers only its subset -- `agent_*` is a worker, but only an AI one.
					query: this.getAttribute('data-query') || '',
					multiple: '1' === this.getAttribute('data-multiple')
				});
			});
		}

		// Enhance polymorphic (context) choosers — a type switcher + record search, posting "<context>:<id>".
		if(window.CerbUI && CerbUI.ContextChooser) {
			$div.find('[data-prime-context-chooser]').each(function() {
				var contexts = [];
				try { contexts = JSON.parse(this.getAttribute('data-contexts') || '[]'); } catch(e) {}
				new CerbUI.ContextChooser(this, {
					contexts: contexts,
					name: this.getAttribute('data-name'),
					multiple: '1' === this.getAttribute('data-multiple')
				});
			});
		}

		// Enhance date inputs.
		if(window.CerbUI && CerbUI.DatePicker) {
			$div.find('[data-prime-date]').each(function() { new CerbUI.DatePicker.FormInput(this); });
		}

		// Enhance picklists (e.g. an HTTP-method chooser).
		if(window.CerbUI && CerbUI.SelectMenu) {
			$div.find('[data-prime-select]').each(function() { new CerbUI.SelectMenu(this); });
		}

		// Enhance code fields (e.g. a request body) — a ScriptingEditor with no placeholder wiring.
		if(window.CerbUI && CerbUI.ScriptingEditor) {
			$div.find('[data-prime-scripting-editor]').each(function() { new CerbUI.ScriptingEditor(this, { minLines: 4, maxLines: 18 }); });
		}

		// Enhance suggest-but-freeform fields (e.g. a Content-Type header) — a TextChooser over a fixed list.
		if(window.CerbUI && CerbUI.TextChooser) {
			$div.find('[data-prime-text-chooser]').each(function() {
				var source = [];
				try { source = JSON.parse(this.getAttribute('data-suggestions') || '[]'); } catch(e) {}
				new CerbUI.TextChooser(this, { source: source, minLength: 0 });
			});
		}

		// Required inputs (automation #inputs: `required@bool: yes`) must be answered before priming. Each
		// value-carrier posts as prompts[key] (single) or prompts[key][] (multiple, e.g. a records chooser).
		var validateRequired = function() {
			var fd = new FormData($form[0]);
			var $firstInvalid = null;

			$form.find('[data-prime-required]').each(function() {
				var $field = $(this);
				var base = $field.attr('data-prime-name');
				var values = fd.getAll(base).concat(fd.getAll(base + '[]'));
				var filled = values.some(function(v) { return String(v).trim() !== ''; });

				$field.toggleClass('cerb-ui-form--field--invalid', !filled);
				if(!filled && !$firstInvalid)
					$firstInvalid = $field;
			});

			if($firstInvalid) {
				$firstInvalid.find('input,textarea,select').first().trigger('focus');
				$firstInvalid[0].scrollIntoView({ block: 'center' });
			}

			return !$firstInvalid;
		};

		// Run / Save: validate required inputs, stamp the pressed mode, then hand the filled form up to the opener.
		var submit = function(mode) {
			if(!validateRequired())
				return;
			$form.find('[data-prime-mode]').val(mode);
			$popup.trigger($.Event('cerb-automation-prime-submit', { form_el: $form[0] }));
		};

		// Clear a field's invalid state once the worker starts filling it.
		$form.on('input change', '[data-prime-required]', function() {
			$(this).removeClass('cerb-ui-form--field--invalid');
		});

		$div.find('[data-prime-run]').on('click', function(e) { e.preventDefault(); submit('run'); });
		$div.find('[data-prime-save]').on('click', function(e) { e.preventDefault(); submit('save'); });
	});
	</script>
</div>
