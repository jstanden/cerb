<form action="javascript:;" method="post" id="frmAvatarEditor" onsubmit="return false;">

<div>
	<div style="float:left;">
		<div style="margin:0;padding:0;border:1px solid rgb(230,230,230);display:inline-block;">
			<canvas class="canvas-avatar" width="{$image_width}" height="{$image_height}" style="width:{$image_width}px;height:{$image_height}px;cursor:move;"></canvas>
		</div>
		<div style="margin-top:5px;">
			<input type="text" name="bgcolor" value="#ffffff" size="8" class="color-picker">
		</div>
		<input type="hidden" name="imagedata" class="canvas-avatar-imagedata">
	</div>
	
	<div style="float:left;">
		<fieldset class="peek">
			<legend>Get image from a URL:</legend>
			<div>
				<input type="text" class="cerb-avatar-img-url" size="64" placeholder="http://example.com/image.png" />
				<button type="button" class="cerb-avatar-img-fetch">Fetch</button>
			</div>
			{if is_array($suggested_photos) && !empty($suggested_photos)}
			<div class="cerb-avatar-suggested-photos"></div>
			{/if}
		</fieldset>
	
		<fieldset class="peek">
			<legend>Upload an image:</legend>
			<input type="file" class="cerb-avatar-img-upload" />
		</fieldset>
		
		<fieldset class="peek cerb-avatar-monogram">
			<legend>Create an image:</legend>
			{'common.label'|devblocks_translate|capitalize}: <input type="text" name="initials" size="5" placeholder="RS" autocomplete="off" spellcheck="false">
			(<a href="https://en.wikipedia.org/wiki/Emoji#Unicode_Blocks" target="_blank" rel="noopener noreferrer">emoji</a>)
			&nbsp; 
			<button type="button">Generate</button>
		</fieldset>
	</div>
	
	<div style="clear:both;"></div>
	
	<div>
		<div class="cerb-ajax-spinner" style="display:none;"></div>
		<button type="button" class="canvas-avatar-zoomin" title="{'common.zoom.in'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-zoom-in"></span></button>
		<button type="button" class="canvas-avatar-zoomout" title="{'common.zoom.out'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-zoom-out"></span></button>
		<button type="button" class="canvas-avatar-remove" title="{'common.clear'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-erase"></span></button>
		<button type="button" class="canvas-avatar-export" title="{'common.save_changes'|devblocks_translate|capitalize}"><span class="glyphicons glyphicons-circle-ok" style="color:rgb(0,180,0);"></span> {'common.save_changes'|devblocks_translate|capitalize}</button>
	</div>
	
	<div class="cerb-avatar-error"></div>
</div>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind($('#frmAvatarEditor'));
	
	$popup.one('popup_open', function(event,ui) {
		$popup.dialog('option','title',"Profile Picture Editor");
		$popup.css('overflow', 'inherit');
		
		var $canvas = $popup.find('canvas.canvas-avatar');
		var canvas = $canvas.get(0);
		var context = canvas.getContext('2d');
		var $export = $popup.find('button.canvas-avatar-export');
		var $imagedata = $popup.find('input.canvas-avatar-imagedata');
		var $error = $popup.find('div.cerb-avatar-error');
		var $spinner = $popup.find('div.cerb-ajax-spinner');
		var $suggested = $popup.find('div.cerb-avatar-suggested-photos');
		var $bgcolor_well = $popup.find('input.color-picker');
		var $monogram = $popup.find('fieldset.cerb-avatar-monogram');
		
		$bgcolor_well.minicolors({
			swatches: ['#CF2C1D','#FEAF03','#57970A','#007CBD','#7047BA','#CF25F5','#ADADAD','#34434E', '#FFFFFF'],
			opacity: true,
			change: function() {
				$canvas.trigger('avatar-redraw');
			}
		});
		
		$monogram.find('button').click(function() {
			var bgcolor = $bgcolor_well.val();
			
			if(bgcolor == '#ffffff') {
				bgcolor = '#1e5271';
				$bgcolor_well.minicolors('value', { color: bgcolor, opacity:0 });
			}
			
			var txt = $monogram.find('input:text').val(); //.substring(0,3);
			
			var scale = 1.0;
			x = 0;
			y = 0;
			
			var $new_canvas = $('<canvas height="{$image_height}" width="{$image_width}"/>');
			var new_canvas = $new_canvas.get(0);
			var new_context = new_canvas.getContext('2d');
			new_context.clearRect(0, 0, new_canvas.width, new_canvas.height);
			
			var height = 70;
			var bounds = { width: {$image_width} };
			while(bounds.width > 95) {
				var height = height - 5;
				new_context.font = "Bold " + height + "pt Arial";
				bounds = new_context.measureText(txt);
			}
			
			new_context.fillStyle = '#FFFFFF';
			new_context.fillText(txt,(new_canvas.width-bounds.width)/2,height+(new_canvas.height-height)/2);
			
			$(img).one('load', function() {
				$new_canvas.remove();
				$canvas.trigger('avatar-redraw');
			});
			
			$(img).attr('src', new_canvas.toDataURL());
		});
		
		var isMouseDown = false;
		var x = 0, lastX = 0;
		var y = 0, lastY = 0;
	
		var scale = 1.0;
		context.scale(scale,scale);
		
		var img = new Image();
		{if $imagedata}
			img.src = "{$imagedata}";
			$canvas.trigger('avatar-redraw');
		{/if}
		
		$canvas.mousedown(function (event) {
			isMouseDown = true;
			lastX = event.offsetX;
			lastY = event.offsetY;
		});
		
		$canvas.mouseup(function(event) {
			isMouseDown = false;
		});
		
		$canvas.mouseout(function(event) {
			isMouseDown = false;
		});
		
		$canvas.mousemove(function(event) {
			if(isMouseDown) {
				x = x - (lastX - event.offsetX);
				y = y - (lastY - event.offsetY);
				
				$canvas.trigger('avatar-redraw');
				
				lastX = event.offsetX;
				lastY = event.offsetY;
			}
		});
		
		/*
		$canvas.on('dragover', function(e) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		});
		
		$canvas.on('dragend', function(e) {
			e.preventDefault();
			e.stopPropagation();
			return false;
		});
		
		$canvas.on('drop', function(e) {
			e.preventDefault();
			e.stopPropagation();
			
			var files = e.originalEvent.dataTransfer.files;
			var reader = new FileReader();
			
			if(files.length == 0)
				return;
			
			$spinner.show();
			
			reader.onload = function(event) {
				$(img).attr('src', event.target.result);
				scale = 1.0;
				x = 0;
				y = 0;
				$canvas.trigger('avatar-redraw');
				$spinner.hide();
			}
			
			reader.readAsDataURL(files[0]);
		});
		*/
		
		$canvas.on('avatar-redraw', function() {
			var bgcolor = $bgcolor_well.minicolors('rgbaString');
			
			context.save();
			
			context.scale(scale, scale);
			context.clearRect(0, 0, canvas.width, canvas.height);
			
			context.fillStyle = bgcolor;
			context.fillRect(0, 0, canvas.width, canvas.height);
			
			var aspect = img.height/img.width;
			context.drawImage(img, x, y, canvas.width, canvas.width*aspect);
			
			context.restore();
		});
		
		{foreach from=$suggested_photos item=photo}
		var $img = $('<img style="cursor:pointer;margin-right:5px;" width="48" height="48">')
			.attr('title',"{$photo.title}")
			.load(function(e) {
				// When successful, add to suggestions
				$(this)
					.click(function() {
						$popup.find('input.cerb-avatar-img-url').val($(this).attr('src'));
						$popup.find('button.cerb-avatar-img-fetch').click();
					})
					.appendTo($suggested)
					;
			})
			.error(function(e) {
				// On a 404, ignore this suggestion
			})
			.attr('src',"{$photo.url nofilter}")
			;
		{/foreach}
		
		$export.click(function() {
			var evt = new jQuery.Event('avatar-editor-save');
			
			evt.avatar = {
				'imagedata': canvas.toDataURL()
			};
			
			if(0 == $(img).attr('src').length) {
				evt.avatar.empty = true;
			}
			
			$popup.trigger(evt);
		});
		
		$popup.find('button.canvas-avatar-zoomout').click(function() {
			scale = Math.max(scale-0.25, 1.0);
			$canvas.trigger('avatar-redraw');
		});
		
		$popup.find('button.canvas-avatar-zoomin').click(function() {
			scale = Math.min(scale+0.25, 10.0);
			$canvas.trigger('avatar-redraw');
		});
		
		$popup.find('button.canvas-avatar-remove').click(function() {
			scale = 1.0;
			x = 0;
			y = 0;
			$bgcolor_well.minicolors('value', { color: '#ffffff', opacity:0 });
			$(img).attr('src', '');
			$canvas.trigger('avatar-redraw');
		});
		
		$popup.find('button.cerb-avatar-img-fetch').click(function() {
			var $url = $popup.find('input.cerb-avatar-img-url');
			var url = encodeURIComponent($url.val());
			
			$error.html('').hide();
			
			$spinner.show();
			
			genericAjaxGet('', 'c=avatars&a=_fetch&url=' + url, function(json) {
				if(undefined == json.status || !json.status) {
					Devblocks.showError($error, json.error);
					$url.select().focus();
					$spinner.hide();
					return;
				}
				
				if(undefined == json.imageData) {
					Devblocks.showError($error, "No image data was available at the given URL.");
					$url.select().focus();
					$spinner.hide();
					return;
				}
				
				scale = 1.0;
				x = 0;
				y = 0;
				$(img).one('load', function() {
					$bgcolor_well.minicolors('value', { color: '#ffffff', opacity:1 });
					$canvas.trigger('avatar-redraw');
				});
				$(img).attr('src', json.imageData);
				$spinner.hide();
			});
		});
	
		$popup.find('input.cerb-avatar-img-upload').change(function(event) {
			$error.html('').hide();
			
			if(undefined == event.target || undefined == event.target.files)
				return;
			
			var f = event.target.files[0];
			
			if(undefined == f)
				return;
			
			if(!f.type.match('image.*')) {
				Devblocks.showError($error, "You may only upload images.");
				return;
			}
			
			var reader = new FileReader();
			
			reader.onload = (function(file) {
				return function(e) {
					scale = 1.0;
					x = 0;
					y = 0;
					$(img).one('load', function() {
						$bgcolor_well.minicolors('value', { color: '#ffffff', opacity:1 });
						$canvas.trigger('avatar-redraw');
					});
					$(img).attr('src', e.target.result);
				};
			})(f);
			
			reader.readAsDataURL(f);
		});
		
		$popup.on('cerb-avatar-set-defaults', function(e) {
			if(undefined == e.avatar)
				return;
			
			if(e.avatar.imagedata) {
				scale = 1.0;
				x = 0;
				y = 0;
				$(img).one('load', function() {
					$canvas.trigger('avatar-redraw');
				});
				$(img).attr('src', e.avatar.imagedata);
			}
			
			if(e.avatar.imageurl) {
				$popup.find('input.cerb-avatar-img-url').val(e.avatar.imageurl);
			}
		});
	});
});
</script>