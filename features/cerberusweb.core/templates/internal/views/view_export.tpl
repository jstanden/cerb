<form action="{devblocks_url}{/devblocks_url}" method="post" target="_blank" id="frm{$view_id}_export">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="viewDoExport">
<input type="hidden" name="view_id" value="{$view_id}">

<h1>{'common.export'|devblocks_translate|capitalize}</h1>
<br>

<div style="margin-bottom:10px;">
	<b>Fields:</b>
 </div>

<ul class="bubbles sortable" style="display:block;padding:0;"></ul>

<div style="margin:10px 0px 0px 0px;">
	<input type="text" size="16" class="input_search filter">
</div>

<ul class="cerb-popupmenu" style="border:0;margin:0px 0px 15px 0px;display:block;max-height:300px;overflow:auto;">
	{foreach from=$context_labels item=label key=token}
	<li><a href="javascript:;" token="{$token}">{$label}</a></li>
	{/foreach}
</ul>
	</div>
</div>

<div style="margin-bottom:10px;">
	<b>Export List As:</b>
	<div style="margin-left:10px;">
		<select name="export_as">
			<option value="csv" selected="selected">Comma-separated values (.csv)</option>
			<option value="json">JSON (.json)</option>
			<option value="xml">XML (.xml)</option>
		</select>
	</div>
</div>

<button type="button" onclick="this.form.submit();" style=""><span class="cerb-sprite2 sprite-tick-circle"></span> {'common.export'|devblocks_translate|capitalize}</button>
<button type="button" onclick="$('#{$view_id}_tips').html('').hide();" style=""><span class="cerb-sprite2 sprite-cross-circle"></span> Cancel</button>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frm{$view_id}_export');
	var $menu = $frm.find('ul.cerb-popupmenu');
	var $fields_menu = $frm.find('ul.cerb-popupmenu');
	var $input = $frm.find('input.filter');
	
	$frm.find('ul.bubbles.sortable').sortable({
		placeholder: 'ui-state-highlight',
		items: 'li',
		distance: 10
	});
	
	// Menu
	
	$input.keyup(
		function(e) {
			var term = $(this).val().toLowerCase();
			$fields_menu.find('> li a').each(function(e) {
				if(-1 != $(this).html().toLowerCase().indexOf(term)) {
					$(this).parent().show();
				} else {
					$(this).parent().hide();
				}
			});
		}
	);

	$fields_menu.find('> li').click(function(e) {
		e.stopPropagation();
		if(!$(e.target).is('li'))
			return;

		$(this).find('a').trigger('click');
	});

	$fields_menu.find('> li > a').click(function() {
		var $item = $(this);
		var token = $item.attr('token');
		var $bubbles = $fields_menu.siblings('ul.bubbles');
		
		var $bubble = $('<li></li>');
		$bubble.css('cursor', 'move');
		
		var $hidden = $('<input>');
		$hidden.attr('type', 'hidden');
		$hidden.attr('name', 'tokens[]');
		$hidden.attr('value', token);
		
		var $a = $('<a href="javascript:;"><span class="ui-icon ui-icon-trash" style="display:inline-block;width:14px;height:14px;"></span></a>');
		$a.click(function(e) {
			$(this).closest('li').remove();
		})
		
		$bubble.append($hidden);
		$bubble.append($item.text());
		$bubble.append($a);
		$bubbles.append($bubble);
		
		$input.focus().select();
	});
});
</script>