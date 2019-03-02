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
	
		<ul class="cerb-menu" style="width:200px;">
			{menu keys=$menu}
		</ul>
	</div>
</fieldset>

<script type="text/javascript">
$(function() {
	var $fieldset = $('#{$fieldset_id}');
	var $textarea = $fieldset.find('textarea:first');
	var $menu = $fieldset.find('ul.cerb-menu');
	
	$menu.on('click', 'li', function(e) {
		e.stopPropagation();
		var $target = $(e.target);
		
		if($target.is('div'))
			$target = $target.closest('li');
		
		var point = $target.attr('data-point');
		
		if(null == point)
			return;
		
		$textarea.insertAtCursor(point + "\r\n");
	});
	
	$menu.menu();
});
</script>