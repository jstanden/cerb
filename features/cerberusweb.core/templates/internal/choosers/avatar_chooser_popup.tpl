<form action="javascript:;" method="post" id="frmAvatarEditor" onsubmit="return false;">

<div>
	<div style="float:left;">
		<div style="margin:0;padding:0;border:1px solid rgb(230,230,230);display:inline-block;">
			<canvas class="canvas-avatar" width="100" height="100" style="width:100px;height:100px;cursor:move;"></canvas>
		</div>
		<input type="hidden" name="imagedata" class="canvas-avatar-imagedata">
	</div>
	
	<div style="float:left;">
		<fieldset class="peek">
			<legend>Get image from URL:</legend>
		  <input type="text" class="cerb-avatar-img-url" size="64" />
		  <button type="button" class="cerb-avatar-img-fetch">Fetch</button>
		</fieldset>
	
		<fieldset class="peek">
			<legend>-or- Upload image:</legend>
	  	<input type="file" class="cerb-avatar-img-upload" />
  	</fieldset>
	</div>
	
	<div style="clear:both;"></div>
	
	<div>
		<button type="button" class="canvas-avatar-zoomin"><span class="glyphicons glyphicons-zoom-in"></span></button>
		<button type="button" class="canvas-avatar-zoomout"><span class="glyphicons glyphicons-zoom-out"></span></button>
		<button type="button" class="canvas-avatar-remove"><span class="glyphicons glyphicons-erase"></span></button>
		<button type="button" class="canvas-avatar-export"><span class="glyphicons glyphicons-circle-ok"></span></button>
	</div>
	
	<div class="cerb-avatar-error"></div>
</div>
</form>

<script type="text/javascript">
$(function() {
	var $popup = genericAjaxPopupFind($('#frmAvatarEditor'));
	
	$popup.one('popup_open', function(event,ui) {
		$(this).dialog('option','title',"Avatar Editor");
		
		var $canvas = $popup.find('canvas.canvas-avatar');
		var canvas = $canvas.get(0);
		var context = canvas.getContext('2d');
		var $export = $popup.find('button.canvas-avatar-export');
		var $imagedata = $popup.find('input.canvas-avatar-imagedata');
		var $error = $popup.find('div.cerb-avatar-error');
		
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
		
		$canvas.on('avatar-redraw', function() {
			context.save();
			context.clearRect(0, 0, canvas.width, canvas.height);
			context.scale(scale, scale);
			var aspect = img.height/img.width;
			context.drawImage(img, x, y, canvas.width, canvas.width*aspect);
			context.restore();
		});
	
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
			$(img).attr('src', '');
			$canvas.trigger('avatar-redraw');
		});
		
		$popup.find('button.cerb-avatar-img-fetch').click(function() {
			var $url = $popup.find('input.cerb-avatar-img-url');
			var url = encodeURIComponent($url.val());
			
			$error.html('').hide();
			
			genericAjaxGet('', 'c=avatars&a=_fetch&url=' + url, function(json) {
				if(undefined == json.status || !json.status) {
					Devblocks.showError($error, json.error);
					$url.select().focus();
					return;
				}
				
				if(undefined == json.imageData) {
					Devblocks.showError($error, "No image data was available at the given URL.");
					$url.select().focus();
					return;
				}
				
				scale = 1.0;
				x = 0;
				y = 0;
				$(img).one('load', function() {
					$canvas.trigger('avatar-redraw');
				});
				$(img).attr('src', json.imageData);
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