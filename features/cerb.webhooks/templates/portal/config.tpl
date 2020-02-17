{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">

<fieldset class="peek">
	<legend>Webhook</legend>
	
	<div class="cerb-form">
		<div>
			<label><b>Use this behavior to respond to webhook requests:</b></label>
			<button type="button" class="chooser-behavior" data-field-name="params[webhook_behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="disabled:n" data-query-required="event:&quot;event.webhook.received&quot;"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $params.webhook_behavior_id}
					{$behavior = DAO_TriggerEvent::get($params.webhook_behavior_id)}
					{if $behavior}
						<li><input type="hidden" name="params[webhook_behavior_id]" value="{$behavior->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></li>
					{/if}
				{/if}
			</ul>
		</div>
	</div>
</fieldset>

<div class="status"></div>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $status = $frm.find('div.status');
	
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.showError($status, json.error);
				} else if (json.message) {
					Devblocks.showSuccess($status, json.message);
				} else {
					Devblocks.showSuccess($status, "Saved!");
				}
			}
		});
	});

	$frm.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;
	
	$frm.find('.chooser-behavior')
		.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
			})
	;
});
</script>