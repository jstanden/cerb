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
						{if is_array($data->children) && !empty($data->children)}
							<li {if $data->key}data-token="{$data->key}" data-label="{$data->label}"{/if}>
								{if $data->key}
									<div style="font-weight:bold;">{$data->l|capitalize}</div>
								{else}
									<div>{$idx|capitalize}</div>
								{/if}
								<ul>
									{tree keys=$data->children level=$level+1}
								</ul>
							</li>
						{elseif $data->key}
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

<div>
	<button type="button" class="save"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	<span class="cerb-ajax-spinner" style="display:none;"></span>
</div>

<script type="text/javascript">
$(function() {
	var $frm = $('#frmConfigRecordType');
	var $bubbles = $frm.find('ul.bubbles');
	var $save_button = $frm.find('button.save');
	var $spinner = $frm.find('span.cerb-ajax-spinner');
	
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
		}
	});
	
	$bubbles.on('click', function(e) {
		var $target = $(e.target);
		if($target.is('.glyphicons-circle-remove')) {
			e.stopPropagation();
			$target.closest('li').remove();
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
		distance: 10
	});
	
	$save_button.click(function() {
		$save_button.attr('disabled','disabled');
		$spinner.show();
		
		genericAjaxPost('frmConfigRecordType', '', null, function(json) {
			$spinner.fadeOut();
			$save_button.removeAttr('disabled');
		});
	});
});
</script>