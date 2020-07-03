<b>Resume draft ID:</b> (optional)
<div style="margin-left:10px;margin-bottom:0.5em;">
	<textarea name="{$namePrefix}[draft_id]" class="placeholders">{$params.draft_id}</textarea>
</div>

<b>Save the new message dictionary to a placeholder named:</b> {include file="devblocks:cerberusweb.core::help/docs_button.tpl" url="https://cerb.ai/guides/bots/prompts#saving-placeholders"}
<div style="margin-left:10px;margin-bottom:0.5em;">
	&#123;&#123;<input type="text" name="{$namePrefix}[var]" size="32" value="{if !empty($params.var)}{$params.var}{else}placeholder{/if}" required="required" spellcheck="false">&#125;&#125;
	<div style="margin-top:5px;">
		<div>
			<i><small>The placeholder name must be lowercase, without spaces, and may only contain a-z, 0-9, and underscores (_)</small></i>
		</div>
	</div>
</div>