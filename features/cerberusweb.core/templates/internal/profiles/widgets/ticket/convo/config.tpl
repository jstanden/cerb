<div id="widget{$widget->id}Config" style="margin-top:10px;">
	<fieldset class="peek">
		<legend>{'common.comments'|devblocks_translate|capitalize}</legend>
		
		<label>
			<input type="radio" name="params[comments_mode]" value="0" {if !$widget->extension_params.comments_mode}checked="checked"{/if}>
			Show
		</label>
		<label>
			<input type="radio" name="params[comments_mode]" value="2" {if 2 == $widget->extension_params.comments_mode}checked="checked"{/if}>
			Show with the latest comment pinned at the top
		</label>
		<label>
			<input type="radio" name="params[comments_mode]" value="1" {if 1 == $widget->extension_params.comments_mode}checked="checked"{/if}>
			Hide
		</label>
	</fieldset>
</div>