<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset class="peek black">
		<legend>Ticket</legend>

		<b><a class="cerb-chooser-ticket" data-context="{CerberusContexts::CONTEXT_TICKET}" data-single="true">{{'common.id'|devblocks_translate}}</a>:</b> (leave blank to auto-detect)

		<div style="margin-left:10px;">
			<input type="text" name="params[ticket_id]" value="{$widget->extension_params.ticket_id}" class="placeholders" style="width:95%;padding:5px;border-radius:5px;" autocomplete="off" spellcheck="false">
		</div>
	</fieldset>

	<fieldset class="peek black">
		<legend>{'common.comments'|devblocks_translate|capitalize}</legend>
		
		<label>
			<input type="radio" name="params[comments_mode]" value="0" {if !$widget->extension_params.comments_mode}checked="checked"{/if}>
			Show
		</label>
		<label>
			<input type="radio" name="params[comments_mode]" value="2" {if 2 == $widget->extension_params.comments_mode}checked="checked"{/if}>
			Show with the latest comment pinned at the top
		</label>
		<label>
			<input type="radio" name="params[comments_mode]" value="1" {if 1 == $widget->extension_params.comments_mode}checked="checked"{/if}>
			{'common.hide'|devblocks_translate|capitalize}
		</label>
	</fieldset>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $config = $('#widget{$widget->id}Config');
	let $input = $config.find('input[name="params[ticket_id]"]');

	$config.find('.cerb-chooser-ticket').cerbChooserTrigger()
		.on('cerb-chooser-selected', function(e) {
			{literal}$input.val(e.values[0] + '{# ' + e.labels[0] + ' #}');{/literal}
		})
		;
});
</script>