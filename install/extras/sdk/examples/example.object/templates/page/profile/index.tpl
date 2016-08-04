{$page_context = ''}
{$page_context_id = 0}

<h1>{$model->name}</h1>
<div>
	<b>{'common.created'|devblocks_translate|capitalize}</b>: <abbr title="{$model->created|devblocks_date}">{$model->created|devblocks_prettytime}</abbr>
</div>

<form>
	<!-- Toolbar -->
	<button type="button" id="btnExObProfileEdit" title="{'common.edit'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-cogwheel"></span></button>
</form>

<div id="objectTabs">
	<ul>
		{$point = Context_ExampleObject::ID} 
		{$tabs = [activity,notes,links]}
		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabActivityLog&scope=target&point={$point}&context={$point}&context_id={$model->id}{/devblocks_url}">{'common.activity_log'|devblocks_translate|capitalize}</a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextComments&context={$point}&id={$model->id}{/devblocks_url}">{'common.comments'|devblocks_translate|capitalize} <div class="tab-badge">{DAO_Comment::count($page_context, $page_context_id)|default:0}</div></a></li>		
		<li><a href="{devblocks_url}ajax.php?c=internal&a=showTabContextLinks&context={$point}&id={$model->id}{/devblocks_url}">{'common.links'|devblocks_translate} <div class="tab-badge">{DAO_ContextLink::count($page_context, $page_context_id)|default:0}</div></a></li>		
	</ul>
</div> 
<br>

<script type="text/javascript">
$(function() {
	var tabOptions = Devblocks.getDefaultjQueryUiTabOptions();
	tabOptions.active = Devblocks.getjQueryUiTabSelected('objectTabs');
	
	var tabs = $('#objectTabs').tabs(tabOptions);
	
	$('#btnExObProfileEdit').bind('click', function() {
		$popup = genericAjaxPopup('peek','c=example.objects&a=showPeekPopup&id={$model->id}',null,false,'50%');
		$popup.one('example_object_save', function(event) {
			event.stopPropagation();
			document.location.reload();
		});
	})
});
</script>