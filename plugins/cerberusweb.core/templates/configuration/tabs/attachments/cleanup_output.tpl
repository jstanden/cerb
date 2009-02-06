<div class="{if !empty($orphans) || !empty($db_orphans)}error{else}success{/if}">
	Checked {$checked} disk files against {$total_files_db} db rows.<br> 
	There were {$orphans} orphans on the disk and not in db.<br>
	There were {$db_orphans} orphans in the db and not on disk.<br>
</div>
