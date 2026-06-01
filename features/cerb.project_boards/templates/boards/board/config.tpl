<fieldset id="tabConfig{$workspace_tab->id}" class="peek">
<legend>Display project board:</legend>

<button type="button" class="chooser-abstract" data-field-name="params[board_id]" data-context="{CerberusContexts::CONTEXT_PROJECT_BOARD}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="cerb-icons cerb-icon-search"></span></button>

<ul class="bubbles chooser-container">
	{$board = DAO_ProjectBoard::get($workspace_tab->params.board_id)}
	{if $board}
		<li><input type="hidden" name="params[board_id]" value="{$board->id}"><a class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_PROJECT_BOARD}" data-context-id="{$board->id}">{$board->name}</a></li>
	{/if}
</ul>

</fieldset>