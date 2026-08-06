{$form_id = uniqid()}
<form id="{$form_id}" action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="profiles">
<input type="hidden" name="a" value="invoke">
<input type="hidden" name="module" value="community_portal">
<input type="hidden" name="action" value="saveConfigTabJson">
<input type="hidden" name="portal_id" value="{$portal->id}">
<input type="hidden" name="config_tab" value="announcements">

<div class="cerb-ui-panel cerb-ui-panel--spaced">
	<div class="cerb-ui-header cerb-ui-header--tight cerb-ui-header--center">
		<div class="cerb-ui-header--title-sm">News Feeds</div>
		<div class="cerb-ui-header--right">
			<button type="button" class="cerb-ui-button cerb-ui-button--subtle add"><span class="cerb-icons cerb-icon-circle-plus"></span> Add feed</button>
		</div>
	</div>

	<p class="cerb-u-text-muted">{'portal.sc.cfg.feeds_info'|devblocks_translate}</p>

	<table id="setupScAnnouncements" class="container">
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
			<td>
				<span class="cerb-icons cerb-icon-move cerb-u-cursor-move cerb-u-mr-2" title="Drag to rearrange"></span>
			</td>
			<td>
				<input type="text" name="news_rss_title[]" value="{$news_rss_title}" size="45">
			</td>
			<td>
				<input type="text" name="news_rss_url[]" value="{$news_rss_url}" size="45">
			</td>
			<td>
				<button type="button" class="remove" data-cerb-button="tbody_remove"><span class="cerb-icons cerb-icon-circle-minus"></span></button>
			</td>
		</tr>
		</tbody>

		{foreach from=$news_rss item=news_rss_url key=news_rss_title}
		<tbody class="drag" style="cursor:move;margin:5px;">
		<tr>
			<td><span class="cerb-icons cerb-icon-move cerb-u-cursor-move cerb-u-mr-2" title="Drag to rearrange"></span></td>
			<td>
				<input type="text" name="news_rss_title[]" value="{$news_rss_title}" size="45">
			</td>
			<td>
				<input type="text" name="news_rss_url[]" value="{$news_rss_url}" size="45">
			</td>
			<td>
				<button type="button" class="remove" data-cerb-button="tbody_remove"><span class="cerb-icons cerb-icon-circle-minus"></span></button>
			</td>
		</tr>
		</tbody>
		{/foreach}
	</table>
</div>

<div class="buttons cerb-u-mt-2">
	<button type="button" class="cerb-ui-button save"><span class="cerb-icons cerb-icon-circle-ok"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
</div>
</form>

<script nonce="{DevblocksPlatform::getRequestNonce()}" type="text/javascript">
$(function() {
	let $frm = $('#{$form_id}');
	let $table = $('#setupScAnnouncements');

	$table.on('click', function(e) {
		e.stopPropagation();

		let $target = $(e.target);

		if($target.is('.cerb-icon-circle-minus'))
			$target = $target.closest('button');

		if($target.is('[data-cerb-button=tbody_remove]'))
			$target.closest('tbody').remove();
	});

	$frm.find('button.save').on('click', function(e) {
		e.stopPropagation();
		genericAjaxPost($frm, '', null, function(json) {
			Devblocks.clearAlerts();
			if(json && typeof json == 'object') {
				if(json.error) {
					Devblocks.createAlertError(json.error);
				} else if (json.message) {
					Devblocks.createAlert(json.message, 'success', 5000);
				} else {
					Devblocks.createAlert('Saved!', 'success', 5000);
				}
			}
		});
	});

	let $container = $frm.find('#setupScAnnouncements.container');

	if(window.CerbUI && CerbUI.Sortable)
		new CerbUI.Sortable($container.get(0), { items: 'tbody.drag' });

	$frm.find('button.add')
		.click(function() {
			let $clone = $container
				.find('TBODY.template')
				.clone()
				.addClass('drag')
				.removeClass('template')
				.show();
			$container.append($clone);
		});
	});
</script>
