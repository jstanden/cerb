{$behavior = DAO_TriggerEvent::get($params.behavior_id)}

<b>The current behavior should:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<label><input type="radio" name="{$namePrefix}[return]" value="1" {if $params.return}checked="checked"{/if}> {'common.wait'|devblocks_translate|capitalize}</label>
	<label><input type="radio" name="{$namePrefix}[return]" value="0" {if !$params.return}checked="checked"{/if}> {'common.exit'|devblocks_translate|capitalize}</label>
</div>

<b>{'common.behavior'|devblocks_translate|capitalize}:</b>
<div style="margin-left:10px;margin-bottom:0.5em;">
	<div class="cerb-ui-record-chooser">
		{if $behavior}
			<li data-context="{CerberusContexts::CONTEXT_BEHAVIOR}" data-context-id="{$behavior->id}" data-label="{$behavior->title}"></li>
		{/if}
	</div>
</div>

<div class="parameters">
{if $behavior}
{include file="devblocks:cerb.behaviors.legacy::events/_action_behavior_params.tpl" params=$params macro_params=$behavior->variables}
{/if}
</div>

<b>Save behavior data to a placeholder named:</b>
<div style="margin-left:10px;margin-bottom:10px;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="24" value="{if !empty($params.var)}{$params.var}{else}_behavior{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
	</div>
</div>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $action = $('#{$namePrefix}_{$nonce}');
	const $behavior_params = $action.find('div.parameters');

	if(!(window.CerbUI && CerbUI.RecordChooser))
		return;

	$action.find('.cerb-ui-record-chooser').each(function() {
		new CerbUI.RecordChooser(this, {
			context: '{CerberusContexts::CONTEXT_BEHAVIOR}',
			name: '{$namePrefix}[behavior_id]',
			emptyIcon: 'branch',
			query: 'event:event.message.chat.worker disabled:n usableBy.bot:{$trigger->bot_id}',
			onSelect: function(item) {
				if(item.id) {
					genericAjaxGet(null, 'c=profiles&a=invoke&module=behavior&action=getParams&name_prefix={$namePrefix}&trigger_id=' + item.id, function(html) {
						const $html = $(html);
						$behavior_params.html($html);
						if(window.CerbUI && CerbUI.ScriptingEditor) {
							$html.find('textarea.placeholders, :text.placeholders').each(function() {
								const isInput = this.tagName === 'INPUT';
								this.classList.remove('placeholders');
								CerbUI.ScriptingEditor.enhance(this, { singleLine: isInput, minLines: isInput ? 1 : 3, maxLines: isInput ? 6 : 12 });
							});
						}
					});
				} else {
					$behavior_params.html('');
				}
			}
		});
	});
});
</script>
