<form action="{devblocks_url}{/devblocks_url}" method="post">
<input type="hidden" name="c" value="config">
<input type="hidden" name="a" value="saveSettings">

<div class="block">
<h2>Storage</h2>
<br>

<h3>Database size:</h3>
Data: <b>{$total_db_data} MB</b><br>
Indexes: <b>{$total_db_indexes} MB</b><br>
Total Disk Space: <b>{$total_db_size} MB</b><br>
<br>
Running an OPTIMIZE on the database would free up about <b>{$total_db_slack} MB</b><br>
<br>

<h3>Attachments:</h3>
Total Disk Space: <b>{$total_file_size} MB</b><br>
<br>



<br>
{*<button type="submit"><img src="{devblocks_url}c=resource&p=cerberusweb.core&f=images/check.gif{/devblocks_url}" align="top"> {$translate->_('common.save_changes')|capitalize}</button>*}
</div>
</form>

<br>
