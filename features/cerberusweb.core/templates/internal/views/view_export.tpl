<form action="{devblocks_url}{/devblocks_url}" method="post" target="_blank" id="frm{$view_id}_export">
<input type="hidden" name="c" value="internal">
<input type="hidden" name="a" value="viewDoExport">
<input type="hidden" name="view_id" value="{$view_id}">
<input type="hidden" name="cursor_key" value="">
<input type="hidden" name="_csrf_token" value="{$session.csrf_token}">

<div class="export-settings">

<h1>{'common.export'|devblocks_translate|capitalize}</h1>
<br>

<div style="margin-bottom:10px;">
	<b>Fields:</b>
 </div>

<ul class="bubbles sortable" style="display:block;padding:0;"></ul>

<div style="margin:10px 0px 0px 0px;">
	<input type="text" size="32" class="input_search filter">
</div>

<ul class="cerb-popupmenu" style="border:0;margin:0px 0px 15px 0px;display:block;max-height:300px;overflow:auto;">
	{foreach from=$context_labels item=label key=token}
	<li><a href="javascript:;" token="{$token}">{$label}</a></li>
	{/foreach}
</ul>

<div style="margin-bottom:10px;">
	<b>Format dates as:</b>
	<div style="margin-left:10px;">
		<label><input type="radio" name="format_timestamps" value="1" checked="checked"> Text</label>
		<label><input type="radio" name="format_timestamps" value="0"> Unix Timestamps</label>
	</div>
</div>

<div style="margin-bottom:10px;">
	<b>Export list as:</b>
	<div style="margin-left:10px;">
		<select name="export_as">
			<option value="csv" selected="selected">Comma-separated values (.csv)</option>
			<option value="json">JSON (.json)</option>
			<option value="xml">XML (.xml)</option>
		</select>
	</div>
</div>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.export'|devblocks_translate|capitalize}</button>
<button type="button" onclick="$('#{$view_id}_tips').html('').hide();" style="cancel"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> Cancel</button>

</div>

<div class="export-status"></div>

</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#frm{$view_id}_export');
	var $menu = $frm.find('ul.cerb-popupmenu');
	var $fields_menu = $frm.find('ul.cerb-popupmenu');
	var $input = $frm.find('input.filter');

	var $settings = $frm.find('div.export-settings');
	var $status = $frm.find('div.export-status');
	
	$frm.find('ul.bubbles.sortable').sortable({
		placeholder: 'ui-state-highlight',
		items: 'li',
		distance: 10
	});
	
	$frm.on('export_increment', function() {
		genericAjaxPost('frm{$view_id}_export', null, 'c=internal&a=doViewExport', function(json) {
			
			// If complete, display the download link
			if(json.completed) {
				$frm.find('input:hidden[name=cursor_key]').val('');
				
				$status.html('<a href="javascript:;" class="close"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);font-size:16px;position:relative;float:right;"></span></a><div style="font-size:18px;font-weight:bold;text-align:center;">Download: <a href="' + json.attachment_url + '" target="_blank">' + json.attachment_name + '</a></div>').fadeIn();
				$status.find('a.close').click(function() {
					$('#{$view_id}_tips').html('').hide();
				});
				return;
			}
			
			$frm.find('input:hidden[name=cursor_key]').val(json.key);
			
			// If in progress, continue looping pages
			$status.html('<div style="font-size:18px;font-weight:bold;text-align:center;padding:10px;margin:10px;">Exported ' + json.rows_exported + ' records<br><span class="cerb-ajax-spinner"></span></div>').fadeIn();
			$frm.trigger('export_increment');
			
		});
		
	})
	
	$frm.find('button.submit').click(function() {
		$settings.hide();
		$frm.trigger('export_increment');
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