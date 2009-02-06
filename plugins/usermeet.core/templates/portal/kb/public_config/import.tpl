{include file="$tpl_path/portal/kb/header.tpl"}

<div style="padding:5px;">

<h2>Import Articles</h2>

<form action="{devblocks_url}{/devblocks_url}" method="post" enctype="multipart/form-data">
<input type="hidden" name="a" value="doImport">

<b>Import File:</b> (XML format)<br>
<input type="file" name="import_file" size="45"><br>
<br>

<button type="submit">{$translate->_('common.save_changes')|capitalize}</button>

</form>

</div>

{include file="$tpl_path/portal/kb/footer.tpl"}