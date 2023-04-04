{if !empty($account_error)}
<div class="error">{$account_error}</div>
{elseif !empty($account_success)}
<div class="success">{'portal.sc.public.my_account.settings_saved'|devblocks_translate}</div>
{/if}

<form action="{devblocks_url}c=account{/devblocks_url}" method="post" id="profileForm">
<input type="hidden" name="a" value="doProfileUpdate">
<input type="hidden" name="_csrf_token" value="{$session->csrf_token}">

<fieldset>
	<legend>{'common.profile'|devblocks_translate|capitalize}</legend>

	<table cellpadding="2" cellspacing="2" border="0">
	
	{if $show_fields.contact_first_name}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.name.first'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_first_name}
			<input type="text" name="first_name" size="35" value="{$active_contact->first_name}">
			{else}
			{$active_contact->first_name}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_last_name}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.name.last'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_last_name}
			<input type="text" name="last_name" size="35" value="{$active_contact->last_name}">
			{else}
			{$active_contact->last_name}
			{/if}
		</td>
	</tr>
	{/if}
	
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.email'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			<select name="primary_email_id">
				<option value=""></option>
				{$addys = $active_contact->getEmails()}
				{foreach from=$addys item=addy key=addy_id}
				<option value="{$addy_id}" {if $active_contact->primary_email_id==$addy_id}selected="selected"{/if}>{$addy->email}</option>
				{/foreach}
			</select>
		</td>
	</tr>
	
	{if $show_fields.contact_title}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.title'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_title}
			<input type="text" name="title" size="35" value="{$active_contact->title}">
			{else}
			{$active_contact->title}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $active_contact->org_id}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.organization'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{$org = $active_contact->getOrg()}
			{$org->name}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_username}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.username'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_username}
			<input type="text" name="username" size="35" value="{$active_contact->username}">
			{else}
			{$active_contact->username}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_gender}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.gender'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_gender}
				<label><input type="radio" name="gender" value="M" {if 'M' == $active_contact->gender}checked="checked"{/if}> {'common.gender.male'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="gender" value="F" {if 'F' == $active_contact->gender}checked="checked"{/if}> {'common.gender.female'|devblocks_translate|capitalize}</label>
				<label><input type="radio" name="gender" value="" {if empty($active_contact->gender)}checked="checked"{/if}> Not specified</label>
			{else}
				{if $active_contact->gender == 'M'}
				{'common.gender.male'|devblocks_translate|capitalize}
				{else if $active_contact->gender == 'F'}
				{'common.gender.female'|devblocks_translate|capitalize}
				{else}
				{/if}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_location}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.location'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_location}
			<input type="text" name="location" size="35" value="{$active_contact->location}">
			{else}
			{$active_contact->location}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_dob}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.dob.abbr'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_dob}
			<input type="text" name="dob" size="35" value="{$active_contact->dob}" placeholder="YYYY-MM-DD">
			{else}
			{$active_contact->dob}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_phone}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.phone'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_phone}
			<input type="text" name="phone" size="35" value="{$active_contact->phone}">
			{else}
			{$active_contact->phone}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_mobile}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.mobile'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_mobile}
			<input type="text" name="mobile" size="35" value="{$active_contact->mobile}">
			{else}
			{$active_contact->mobile}
			{/if}
		</td>
	</tr>
	{/if}
	
	{if $show_fields.contact_photo}
	<tr>
		<td width="1%" nowrap="nowrap" valign="top"><b>{'common.photo'|devblocks_translate|capitalize}:</b></td>
		<td width="99%">
			{if 2 == $show_fields.contact_photo}
			<div>
				<div style="float:left;">
					<div style="margin:0;padding:0;border:1px solid rgb(230,230,230);display:inline-block;">
						<canvas class="canvas-avatar" width="100" height="100" style="width:100px;height:100px;cursor:move;"></canvas>
					</div>
					<input type="hidden" name="imagedata" class="canvas-avatar-imagedata">
				</div>
				
				<div style="float:left;">
					<fieldset class="peek">
						<legend>Upload new image:</legend>
					<input type="file" class="cerb-avatar-img-upload" />
				</fieldset>
				</div>
				
				<div style="clear:both;"></div>
				
				<div>
					<button type="button" class="canvas-avatar-zoomin"><span class="glyphicons glyphicons-zoom-in"></span></button>
					<button type="button" class="canvas-avatar-zoomout"><span class="glyphicons glyphicons-zoom-out"></span></button>
					<button type="button" class="canvas-avatar-remove"><span class="glyphicons glyphicons-erase"></span></button>
				</div>
				
				<div class="cerb-avatar-error"></div>
			</div>
			
			{else}
			<img class="cerb-avatar" src="{devblocks_url}c=avatar&context=contact&context_id={$active_contact->id}{/devblocks_url}?v={$active_contact->updated_at}" style="height:64px;width:64px;border-radius:5px;border:1px solid rgb(235,235,235);">
			{/if}
		</td>
	</tr>
	{/if}
	
	</tbody>
</table>
</fieldset>

<button type="button" class="submit"><span class="glyphicons glyphicons-circle-ok"></span> {'common.save_changes'|devblocks_translate}</button>

</form>

<script type="text/javascript">
	let $form = document.querySelector('#profileForm');
	
	{if 2 == $show_fields.contact_photo}
	let canvas = $form.querySelector('canvas.canvas-avatar');
	let context = canvas.getContext('2d');
	let image_data = $form.querySelector('input.canvas-avatar-imagedata');
	let $error = $form.querySelector('div.cerb-avatar-error');
	
	var isMouseDown = false;
	var x = 0, lastX = 0;
	var y = 0, lastY = 0;

	var scale = 1.0;
	context.scale(scale,scale);
	
	canvas.addEventListener('mousedown', function (event) {
		isMouseDown = true;
		lastX = event.offsetX;
		lastY = event.offsetY;
	});
	
	canvas.addEventListener('mouseup', function() {
		isMouseDown = false;
	});
	
	canvas.addEventListener('mouseout', function() {
		isMouseDown = false;
	});
	
	canvas.addEventListener('mousemove', function(event) {
		if(isMouseDown) {
			x = x - (lastX - event.offsetX);
			y = y - (lastY - event.offsetY);
			
			canvas.dispatchEvent(createEvent('avatar-redraw'));
			
			lastX = event.offsetX;
			lastY = event.offsetY;
		}
	});
	
	canvas.addEventListener('avatar-redraw', function() {
		context.save();
		context.clearRect(0, 0, canvas.width, canvas.height);
		context.scale(scale, scale);
		var aspect = img.height/img.width;
		context.drawImage(img, x, y, canvas.width, canvas.width*aspect);
		context.restore();
	});

	$form.querySelector('button.canvas-avatar-zoomout').addEventListener('click', function() {
		scale = Math.max(scale-0.25, 1.0);
		canvas.dispatchEvent(createEvent('avatar-redraw'));
	});
	
	$form.querySelector('button.canvas-avatar-zoomin').addEventListener('click', function() {
		scale = Math.min(scale+0.25, 10.0);
		canvas.dispatchEvent(createEvent('avatar-redraw'));
	});
	
	$form.querySelector('button.canvas-avatar-remove').addEventListener('click', function() {
		scale = 1.0;
		x = 0;
		y = 0;
		img.setAttribute('src', '');
		canvas.dispatchEvent(createEvent('avatar-redraw'));
	});
	
	$form.querySelector('input.cerb-avatar-img-upload').addEventListener('change', function(event) {
		$error.html = '';
		$error.style.display = 'none';
		
		if(!event.target || !event.target.files)
			return;
		
		var f = event.target.files[0];
		
		if(!f) return;
		
		if(!f.type.match('image.*')) {
			//Devblocks.showError($error, "You may only upload images.");
			return;
		}
		
		var reader = new FileReader();
		
		reader.onload = (function() {
			return function(e) {
				scale = 1.0;
				x = 0;
				y = 0;
				img.setAttribute('src', e.target.result);
			};
		})(f);
		
		reader.readAsDataURL(f);
	});
	
	$form.addEventListener('cerb-avatar-set-defaults', function(e) {
		if(!e.avatar) return;
		
		if(e.avatar.imagedata) {
			scale = 1.0;
			x = 0;
			y = 0;
			img.addEventListener('load', function() {
				canvas.dispatchEvent(createEvent('avatar-redraw'));
			});
			img.setAttribute('src', e.avatar.imagedata);
		}
	});
	
	var img = new Image();

	img.addEventListener('load', function() {
		canvas.dispatchEvent(createEvent('avatar-redraw'));
	});
	
	{if $imagedata}
		img.src = "{$imagedata}";
		canvas.dispatchEvent(createEvent('avatar-redraw'));
	{else}
		img.src = "";
	{/if}
	{/if}
	
	$form.querySelector('button.submit').addEventListener('click', function() {
		{if 2 == $show_fields.contact_photo}
		if(!img || !img.getAttribute('src') || 0 === img.getAttribute('src').length) {
			image_data.value = 'data:null';
		} else {
			image_data.value = canvas.toDataURL();
		}
		{/if}
		
		$form.submit();
	});
</script>