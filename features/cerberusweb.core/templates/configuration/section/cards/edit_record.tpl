<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="cards">
<input type="hidden" name="action" value="saveRecordType">
<input type="hidden" name="ext_id" value="{$context_ext->id}">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<fieldset>
	<legend>{$context_ext->manifest->name}</legend>
	
	<table cellpadding="10" cellspacing="0">
		<tr>
			<td valign="top">
				<b>Fields:</b>
				
				<ul class="bubbles sortable" style="display:block;padding:0;">
					{foreach from=$tokens item=token}
					<li style="display: block; cursor: move; margin: 5px;"><input type="hidden" name="tokens[]" value="{$token}">{$labels.$token}{if '_label' == substr($token, -6)} (Record){/if}<a href="javascript:;" style="position: absolute; visibility: hidden; top: -7px; right: -6px; display: block;"><span class="glyphicons glyphicons-circle-remove"></span></a></li>		
					{/foreach}
				</ul>
			</td>
			
			<td valign="top">
				<b>{'common.add'|devblocks_translate|capitalize}:</b>
				
				{function tree level=0}
					{foreach from=$keys item=data key=idx}
						{if is_array($data)}
							<li>
								<div>{$idx|capitalize}</div>
								<ul>
									{tree keys=$data level=$level+1}
								</ul>
							</li>
						{else}
							<li data-token="{$data->key}" data-label="{$data->label}"><div style="font-weight:bold;">{$data->l|capitalize}</div></li>
						{/if}
					{/foreach}
				{/function}
				
				<ul class="menu" style="width:250px;">
				{tree keys=$placeholders}
				</ul>
			</td>
		</tr>
	</table>
	
</fieldset>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmConfigRecordType');
	var $bubbles = $frm.find('ul.bubbles');
	
	var $placeholder_menu = $frm.find('ul.menu').menu({
		select: function(event, ui) {
			var token = ui.item.attr('data-token');
			var label = ui.item.attr('data-label');
			
			if(undefined == token || undefined == label)
				return;
			
			var $bubble = $('<li style="display:block;"></li>')
				.css('cursor', 'move')
				.css('margin', '5px')
			;
			
			var $hidden = $('<input>');
			$hidden.attr('type', 'hidden');
			$hidden.attr('name', 'tokens[]');
			$hidden.attr('value', token);
			
			var $a = $('<a href="javascript:;" style="position: absolute; visibility: hidden; top: -7px; right: -6px; display: block;"><span class="glyphicons glyphicons-circle-remove"></span></a>');
			
			$bubble.append($hidden);
			$bubble.append(label);
			$bubble.append($a);
			$bubbles.append($bubble);
			$frm.trigger('cerb-persist');
		}
	});
	
	$bubbles.on('click', function(e) {
		var $target = $(e.target);
		if($target.is('.glyphicons-circle-remove')) {
			e.stopPropagation();
			$target.closest('li').remove();
			$frm.trigger('cerb-persist');
		}
	});
	
	$bubbles.on('mouseover', function(e) {
		$bubbles.find('a').css('visibility', 'visible');
	});
	
	$bubbles.on('mouseout', function(e) {
		$bubbles.find('a').css('visibility', 'hidden');
	});
	
	$frm.find('ul.bubbles.sortable').sortable({
		placeholder: 'ui-state-highlight',
		items: 'li',
		distance: 10,
		stop: function(e, ui) {
			$frm.trigger('cerb-persist');
		}
	});
	
	$frm.on('cerb-persist', function(e) {
		e.stopPropagation();
		genericAjaxPost('frmConfigRecordType', '', null, function(json) {
		});
	});
});
</script>