<fieldset class="delete" style="margin-top:5px;">
	<legend>Customize: Access Denied</legend>
	
	<div style="margin-bottom:5px;">
		You do not have permission to modify this worklist.
	</div>
	
	<button type="button" onclick="$(this).closest('fieldset').remove();"><span class="glyphicons glyphicons-circle-remove" style="color:rgb(200,0,0);"></span> {'common.cancel'|devblocks_translate|capitalize}</button>
</fieldset>