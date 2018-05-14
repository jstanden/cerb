<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>Display this snippet</legend>
		
		<div>
			<b>ID:</b>
			
			<div style="margin-left:10px;">
				<input type="text" name="params[context_id]" value="{$widget->extension_params.context_id}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="off">
			</div>
		</div>
	</fieldset>
</div>

<script type="text/javascript">
$(function() {
	var $config = $('#widget{$widget->id}Config');
});
</script>