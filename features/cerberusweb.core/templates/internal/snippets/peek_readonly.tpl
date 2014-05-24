<fieldset class="peek">
	<legend>{'common.properties'|devblocks_translate|capitalize}</legend>
	
	<table cellpadding="2" cellspacing="0" border="0" width="100%">
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.title'|devblocks_translate|capitalize}:</b><br>
			</td>
			<td width="99%">
				{$snippet->title}
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.type'|devblocks_translate|capitalize}:</b><br>
			</td>
			<td width="99%">
				{if empty($snippet->context)}
					Plaintext
				{else}
					{foreach from=$contexts item=ctx key=k}
					{if is_array($ctx->params.options.0) && isset($ctx->params.options.0.snippets)}
						{if $snippet->context==$k}
						{$ctx->name}
						{/if}
					{/if}
					{/foreach}
				{/if}
			</td>
		</tr>
		<tr>
			<td width="1%" nowrap="nowrap" valign="top">
				<b>{'common.owner'|devblocks_translate|capitalize}:</b>
			</td>
			<td width="99%">
				{if !empty($snippet->id)}
				<ul class="bubbles">
					<li>
					{if $snippet->owner_context==CerberusContexts::CONTEXT_ROLE && isset($roles.{$snippet->owner_context_id})}
					<b>{$roles.{$snippet->owner_context_id}->name}</b> (Role)
					{/if}
					
					{if $snippet->owner_context==CerberusContexts::CONTEXT_GROUP && isset($groups.{$snippet->owner_context_id})}
					<b>{$groups.{$snippet->owner_context_id}->name}</b> (Group)
					{/if}
					
					{if $snippet->owner_context==CerberusContexts::CONTEXT_WORKER && isset($workers.{$snippet->owner_context_id})}
					<b>{$workers.{$snippet->owner_context_id}->getName()}</b> (Worker)
					{/if}
					</li>
				</ul>
				{/if}
			</td>
		</tr>
	</table>
	
	<b>{'common.content'|devblocks_translate|capitalize}:</b>
	<pre class="emailbody" style="padding:10px;">{$snippet->content}</pre>
</fieldset>

<fieldset class="delete">
	{'error.core.no_acl.edit'|devblocks_translate}
</fieldset>

<script type="text/javascript">
	var $popup = genericAjaxPopupFetch('{$layer}');
	$popup.one('popup_open',function(event,ui) {
		$(this).dialog('option','title', 'Snippet');
	});
</script>
