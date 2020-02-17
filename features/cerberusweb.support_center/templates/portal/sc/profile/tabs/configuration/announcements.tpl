{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="handleSectionAction">
<input type="hidden" name="section" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="announcements">

{'portal.sc.cfg.feeds_info'|devblocks_translate}<br>
<br>

<table id="setupScAnnouncements" cellpadding="0" cellspacing="0" border="0" class="container">
	<tr>
		<td></td>
		<td>
			<b>{'portal.sc.cfg.feed_display_title'|devblocks_translate}</b>
		</td>
		<td>
			<b>{'portal.sc.cfg.feed_url'|devblocks_translate}</b>
		</td>
	</tr>

	<tbody class="template" style="display:none;margin:5px;">
	<tr>
		<td><span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;" title="Click and drag to rearrange"></span></td>
		<td>
			<input type="text" name="news_rss_title[]" value="{$news_rss_title}" size="45">
		</td>
		<td>
			<input type="text" name="news_rss_url[]" value="{$news_rss_url}" size="45">
		</td>
		<td>
			<button type="button" class="remove" onclick="$(this).closest('tbody').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></button>			
		</td>
	</tr>
	</tbody>
	
	{foreach from=$news_rss item=news_rss_url key=news_rss_title}
	<tbody class="drag" style="cursor:move;margin:5px;">
	<tr>
		<td><span class="ui-icon ui-icon-arrowthick-2-n-s" style="display:inline-block;vertical-align:middle;cursor:move;" title="Click and drag to rearrange"></span></td>
		<td>
			<input type="text" name="news_rss_title[]" value="{$news_rss_title}" size="45">
		</td>
		<td>
			<input type="text" name="news_rss_url[]" value="{$news_rss_url}" size="45">
		</td>
		<td>
			<button type="button" class="remove" onclick="$(this).closest('tbody').remove();"><span class="glyphicons glyphicons-circle-minus" style="color:rgb(200,0,0);"></span></button>			
		</td>
	</tr>
	</tbody>
	{/foreach}
</table>

<button type="button" class="add"><span class="glyphicons glyphicons-circle-plus"></span></button>

<div class="status"></div>

<button type="button" class="submit" style="margin-top:10px;"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</form>

<script type="text/javascript">
$(function() {
	var $frm = $('#{$form_id}');
	var $status = $frm.find('div.status');
		
	$frm.find('button.submit').on('click', function(e) {
		genericAjaxPost($frm, '', null, function(json) {
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.showError($status, json.error);
				} else if (json.message) {
					Devblocks.showSuccess($status, json.message);
				} else {
					Devblocks.showSuccess($status, "Saved!");
				}
			}
		});
	});
	
	var $container = $frm.find('#setupScAnnouncements.container');
	
	$container
		.sortable({ items: 'TBODY.drag', placeholder:'ui-state-highlight' })
		;
	$container
		.next('BUTTON.add')
		.click(function() {
			$clone = $container
				.find('TBODY.template')
				.clone()
				.addClass('drag')
				.removeClass('template')
				.show();
			$container.append($clone);
		});	
	});
</script>