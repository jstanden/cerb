<div id="portalConfig{$instance->code}">

<fieldset class="peek">
	<legend>Interactions</legend>
	
	<div class="cerb-form">
		<div>
			<label><b>Default bot name:</b></label>
			<input type="text" name="params[bot_name]" value="{$params.bot_name}" size="45" placeholder="e.g. &quot;Cerb&quot;">
		</div>
	
		<div>
			<label><b>Use this behavior to respond to new interactions:</b></label>
			<button type="button" class="chooser-behavior" data-field-name="params[interaction_behavior_id]" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-single="true" data-query="event:&quot;event.interaction.chat.portal&quot; disabled:n"><span class="glyphicons glyphicons-search"></span></button>
			
			<ul class="bubbles chooser-container">
				{if $params.interaction_behavior_id}
					{$behavior = DAO_TriggerEvent::get($params.interaction_behavior_id)}
					{if $behavior}
						<li><input type="hidden" name="params[interaction_behavior_id]" value="{$behavior->id}"><a href="javascript:;" class="cerb-peek-trigger no-underline" data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}">{$behavior->title}</a></li>
					{/if}
				{/if}
			</ul>
		</div>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>Portal Home Page</legend>
	
	<div class="cerb-form">
		<div>
			<label><b>Page title:</b></label>
			<input type="text" name="params[page_title]" value="{$params.page_title|default:''}" size="45" placeholder="Get help from our friendly chat bot">
		</div>
		<div>
			<label><b>Display the floating bot icon:</b></label>
			<div>
				<input type="radio" name="params[page_hide_icon]" value="0" {if !$params.page_hide_icon}checked="checked"{/if}> {'common.yes'|devblocks_translate|capitalize}
				<input type="radio" name="params[page_hide_icon]" value="1" {if $params.page_hide_icon}checked="checked"{/if}> {'common.no'|devblocks_translate|capitalize}
			</div>
		</div>
		<div>
			<label><b>Custom CSS:</b></label>
			<div>
				<textarea name="params[page_css]" data-editor-mode="ace/mode/css">{$params.page_css}</textarea>
			</div>
		</div>
	</div>
</fieldset>

<fieldset class="peek">
	<legend>Security</legend>
	
	<div class="cerb-form">
		<div>
			<label><b>Only allow the bot widget to be embedded at this URL host:</b></label>
			<input type="text" name="params[cors_allow_origin]" value="{$params.cors_allow_origin|default:'*'}" size="45" placeholder="e.g. &quot;https://example.com&quot;, or * (asterisk) for any origin">
		</div>
	</div>
</fieldset>

</div>

<script type="text/javascript">
$(function() {
	var $portal = $('#portalConfig{$instance->code}');
	
	$portal.find('.cerb-peek-trigger')
		.cerbPeekTrigger()
	;
	
	$portal.find('.chooser-behavior')
		.cerbChooserTrigger()
			.on('cerb-chooser-saved', function(e) {
			})
	;
	
	$portal.find('textarea[name="params[page_css]"]')
		.cerbCodeEditor()
	;
});
</script>