<div style="margin:5px;display:inline-block;">
	<label>
		<input type="radio" name="params[layout]" value="" {if empty($model->params.layout)}checked="checked"{/if}>
		<svg width="100" height="80" style="vertical-align:middle;">
			<g style="fill:lightgray;stroke:gray;stroke-width:1">
				<rect x="1" y="1" width="98" height="78" />
			</g>
			<g style="fill:rgb(180,180,180);stroke:gray;stroke-width:1">
				<rect x="5" y="5" width="90" height="70" />
			</g>
		</svg>
	</label>
</div>

<div style="margin:5px;display:inline-block;">
	<label>
		<input type="radio" name="params[layout]" value="sidebar_left" {if 'sidebar_left' == $model->params.layout}checked="checked"{/if}>
		<svg width="100" height="80" style="vertical-align:middle;">
			<g style="fill:lightgray;stroke:gray;stroke-width:1">
				<rect x="1" y="1" width="98" height="78" />
			</g>
			<g style="fill:rgb(180,180,180);stroke:gray;stroke-width:1">
				<rect x="5" y="5" width="30" height="70" />
				<rect x="40" y="5" width="55" height="70" />
			</g>
		</svg>
	</label>
</div>

<div style="margin:5px;display:inline-block;">
	<label>
		<input type="radio" name="params[layout]" value="sidebar_right" {if 'sidebar_right' == $model->params.layout}checked="checked"{/if}>
		<svg width="100" height="80" style="vertical-align:middle;">
			<g style="fill:lightgray;stroke:gray;stroke-width:1">
				<rect x="1" y="1" width="98" height="78" />
			</g>
			<g style="fill:rgb(180,180,180);stroke:gray;stroke-width:1">
				<rect x="5" y="5" width="55" height="70" />
				<rect x="65" y="5" width="30" height="70" />
			</g>
		</svg>
	</label>
</div>

<div style="margin:5px;display:inline-block;">
	<label>
		<input type="radio" name="params[layout]" value="thirds" {if 'thirds' == $model->params.layout}checked="checked"{/if}>
		<svg width="100" height="80" style="vertical-align:middle;">
			<g style="fill:lightgray;stroke:gray;stroke-width:1">
				<rect x="1" y="1" width="98" height="78" />
			</g>
			<g style="fill:rgb(180,180,180);stroke:gray;stroke-width:1">
				<rect x="4" y="5" width="28" height="70" />
				<rect x="36" y="5" width="28" height="70" />
				<rect x="68" y="5" width="28" height="70" />
			</g>
		</svg>
	</label>
</div>