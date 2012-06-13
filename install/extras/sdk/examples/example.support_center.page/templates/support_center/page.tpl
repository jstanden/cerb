<p>
	This is the page content.
</p>

<p>
	<a class="ajax" href="javascript:;">Load content through Ajax.</a>
</p>

<div class="ajax-output"></div>

<script type="text/javascript">
$content = $('div#content');

$content.find('a.ajax').click(function(e) {
	ajaxHtmlGet('div#content div.ajax-output', '{devblocks_url}c=example&a=ajaxMethod{/devblocks_url}?param=value');
});
</script>