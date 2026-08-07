{$element_id = uniqid()}
<div class="cerb-form-builder-prompt cerb-form-builder-prompt-query" id="{$element_id}">
	<h6>{$label}</h6>

	<div style="margin-left:10px;">
		<div class="cerb-ui-searchquery">
			<span class="cerb-ui-searchquery--icon cerb-icons cerb-icon-search"></span>
			<div class="cerb-ui-searchquery--field">
				<div class="cerb-ui-searchquery--highlight" aria-hidden="true"></div>
				<textarea name="prompts[{$var}]" class="cerb-ui-searchquery--input" rows="1" placeholder="{$placeholder}">{$value|default:$default}</textarea>
				<span class="cerb-ui-searchquery--caret-anchor"></span>
			</div>
			<div class="cerb-ui-searchquery--right">
				<a data-action="autocomplete" style="cursor:pointer;color:var(--cerb-color-background-contrast-150);" title="Suggestions (Ctrl/⌘+Space)"><span class="cerb-icons cerb-icon-autocomplete"></span></a>
			</div>
		</div>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
	$(function() {
		const promptEl = document.getElementById('{$element_id}');
		const $form = $(promptEl).closest('form');
		const sqEl = promptEl.querySelector('.cerb-ui-searchquery');

		if(sqEl && window.CerbUI && CerbUI.SearchQuery) {
			const opts = {
				onSearch: function() {
					$form.triggerHandler($.Event('cerb-form-builder-submit'));
				}
			};
			{if $record_type}
			opts.onAutocomplete = CerbUI.SearchQuery.queryFieldSource("{$record_type}");
			opts.context = "{$record_type}";
			{/if}
			const sq = new CerbUI.SearchQuery(sqEl, opts);
			const acBtn = sqEl.querySelector('[data-action=autocomplete]');
			if(acBtn) acBtn.addEventListener('click', () => sq.openAutocomplete());
		}
	});
</script>
