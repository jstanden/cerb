<div id="widget{$widget->id}ConfigTabDatasource" style="margin-top:10px;">
	<fieldset id="widget{$widget->id}Datasource" class="peek">
		<legend>Display this calendar:</legend>
		
		{$calendar_id = $widget->params.calendar_id}
		{$calendar = null}
		<div style="margin-left:10px;margin-bottom:0.5em;">
			<button type="button" class="chooser-abstract" data-field-name="params[calendar_id]" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-single="true" data-query=""><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $calendar_id}
					{$calendar = DAO_Calendar::get($calendar_id)}
					{if $calendar}
						<li><input type="hidden" name="params[calendar_id]" value="{$calendar->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_CALENDAR}" data-context-id="{$calendar->id}">{$calendar->name}</a></li>
					{/if}
				{/if}
			</ul>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $fieldset = $('fieldset#widget{$widget->id}Datasource');
});
</script>
