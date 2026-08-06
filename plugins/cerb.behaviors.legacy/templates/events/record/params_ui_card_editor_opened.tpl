{$fieldset_id = uniqid()}
<fieldset id="{$fieldset_id}" style="margin-top:5px;">
	<b>Run when the editor popup opens for these record types:</b>{* (one per line, <tt>*</tt> for wildcards)*}
	<br>
	<textarea name="event_params[listen_contexts]" rows="6" cols="45" style="height:8em;width:100%;">{$trigger->event_params.listen_contexts}</textarea>
	<div>
		{function menu level=0}
			{foreach from=$keys item=data key=idx}
				{if is_array($data->children) && !empty($data->children)}
					{if !is_null($data->key)}
					<li class="cerb-point" data-point="{$data->key}">
						<div style="font-weight:bold;">
							{$idx}
						</div>
						<ul style="width:300px;">
							{menu keys=$data->children level=$level+1}
						</ul>
					</li>
					{else}
					<li>
						<div>
							{$idx}
						</div>
						<ul style="width:300px;">
							{menu keys=$data->children level=$level+1}
						</ul>
					</li>
					{/if}
				{elseif !is_null($data->key)}
					<li class="cerb-point" data-point="{$data->key}">
						<div style="font-weight:bold;">
							{$idx}
						</div>
					</li>
				{/if}
			{/foreach}
		{/function}
	
		<ul class="cerb-menu" style="width:200px;display:none;">
			{menu keys=$menu}
		</ul>
	</div>
</fieldset>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	const $fieldset = $('#{$fieldset_id}');
	const $textarea = $fieldset.find('textarea:first');
	const menuEl = $fieldset.find('ul.cerb-menu')[0];

	if(menuEl && window.CerbUI && CerbUI.Menu) {
		new CerbUI.Menu(menuEl, {
			inline: true,
			selectableParents: true,
			onSelect: function(li, src) {
				const point = src.getAttribute('data-point');

				if(point != null)
					$textarea.insertAtCursor(point + "\r\n");
			}
		});
	}
});
</script>