<fieldset id="tabConfig{$workspace_tab->id}" class="peek">
<legend>Display this calendar:</legend>

<button type="button" class="chooser-abstract" data-field-name="params[calendar_id]" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-single="true" data-query="" data-autocomplete="" data-autocomplete-if-empty="true"><span class="glyphicons glyphicons-search"></span></button>

<ul class="bubbles chooser-container">
	{$calendar = DAO_Calendar::get($workspace_tab->params.calendar_id)}
	{if $calendar}
		<li><input type="hidden" name="params[calendar_id]" value="{$calendar->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-context-id="{$calendar->id}">{$calendar->name}</a></li>
	{/if}
</ul>

</fieldset>

<script type="text/javascript">
$(function() {
	var $fieldset = $('#tabConfig{$workspace_tab->id}');
})
</script>