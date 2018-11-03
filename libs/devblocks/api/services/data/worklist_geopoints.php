<?php
class _DevblocksDataProviderWorklistGeoPoints extends _DevblocksDataProvider {
	function getData($query, $chart_fields, &$error=null, array $options=[]) {
		$db = DevblocksPlatform::services()->database();
		
		$chart_model = [
			'type' => 'worklist.geo.points',
			'of' => '',
			'point' => '',
			'query' => '',
			'format' => 'geopoints',
		];
		
		foreach($chart_fields as $field) {
			$oper = $value = null;
			
			if(!($field instanceof DevblocksSearchCriteria))
				continue;
			
			if($field->key == 'of') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				
				if(false == ($chart_context = Extension_DevblocksContext::getByAlias($value, true)))
					continue;
				
				$chart_model['context'] = $chart_context->id;
				
			} else if($field->key == 'point') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['point'] = $value;
				
			} else if($field->key == 'format') {
				CerbQuickSearchLexer::getOperStringFromTokens($field->tokens, $oper, $value);
				$chart_model['format'] = $value;
				
			} else if($field->key == 'query') {
				$data_query = CerbQuickSearchLexer::getTokensAsQuery($field->tokens);
				$data_query = substr($data_query, 1, -1);
				$chart_model['query'] = $data_query;
			}
		}

		// Convert field to SearchFields_* using context
		if(false != ($chart_context = Extension_DevblocksContext::get($chart_model['context']))) {
			$view = $chart_context->getTempView();
			$search_class = $chart_context->getSearchClass();
			$query_fields = $view->getQuickSearchFields();
			$search_fields = $view->getFields();
			
			// [TODO] This must be a geopoint type
			if(array_key_exists('point', $chart_model)) {
				if(false == ($field = $search_class::getFieldForSubtotalKey($chart_model['point'], $chart_context->id, $query_fields, $search_fields, $search_class::getPrimaryKey()))) {
					unset($chart_model['point']);
				} else {
					$chart_model['point'] = $field;
				}
			}
		}
		
		// Fetch data
		
		if(isset($chart_model['point'])) {
			@$query = $chart_model['query'];
			
			$context_ext = Extension_DevblocksContext::get($chart_model['context'], true);
			$dao_class = $context_ext->getDaoClass();
			$search_class = $context_ext->getSearchClass();
			$view = $context_ext->getTempView();
			$view->addParamsWithQuickSearch($query);
			
			$query_parts = $dao_class::getSearchQueryComponents(
				[],
				$view->getParams()
			);
			
			$sort_data = Cerb_ORMHelper::buildSort($view->renderSortBy, $view->renderSortAsc, $view->getFields(), $search_class);
			
			$field = $chart_model['point']['sql_select'];
			
			$sql = sprintf("SELECT %s AS id, %s AS point%s %s %s %s LIMIT %d",
				$search_class::getPrimaryKey(),
				$field,
				$sort_data['sql_select'] ? sprintf(", %s", $sort_data['sql_select']) : '',
				$query_parts['join'],
				$query_parts['where'],
				$sort_data['sql_sort'],
				$view->renderLimit
			);
			
			if(false == ($results = $db->GetArraySlave($sql)))
				$results = [];
			
			$chart_model['data'] = $results;
		}
		
		// Respond
		
		@$format = $chart_model['format'] ?: 'geopoints';
		
		switch($format) {
			case 'geopoints':
				return $this->_formatDataAsGeoPoints($chart_model);
				break;
				
			case 'table':
				return $this->_formatDataAsTable($chart_model);
				break;
				
			default:
				$error = sprintf("`format:%s` is not valid for `type:%s`. Must be one of: geopoints, table",
					$format,
					$chart_model['type']
				);
				return false;
		}
	}
	
	function _formatDataAsGeoPoints($chart_model) {
		$results = $chart_model['data'];
		
		$results = array_map(function($row) {
			if($row['point'])
				$row['point'] = @DevblocksPlatform::parseGeoPointString($row['point']);
			
			$row['data'] = [];
			
			return $row;
		}, $results);
		
		return [
			'data' => $results,
			'_' => [
				'type' => 'worklist.geo.points',
				'format' => 'geopoints',
			]
		];
	}
	
	function _formatDataAsTable($chart_model) {
		$rows = $columns = [];
		
		$table = [
			'columns' => &$columns,
			'rows' => &$rows,
		];
		
		$point_field_id = $chart_model['point']['key_query'];
		
		$columns['id'] = [
			'label' => 'ID',
			'type' => DevblocksSearchCriteria::TYPE_NUMBER,
			'type_options' => [],
		];
		
		$columns['point'] = [
			'label' => DevblocksPlatform::strTitleCase(@$chart_model['point']['label'] ?: $point_field_id),
			'type' => @$chart_model['point']['type'] ?: DevblocksSearchCriteria::TYPE_GEO_POINT,
			'type_options' => @$chart_model['point']['type_options'] ?: [],
		];
		
		foreach($chart_model['data'] as $data) {
			$id = $data['id'];
			$point = $data['point'];
			
			if(false === ($point = @DevblocksPlatform::parseGeoPointString($point)))
				continue;
			
			$point_label = sprintf('%f, %f', $point['latitude'], $point['longitude']);
			
			$row = [
				'name_label' => 'Name',
				'name' => $id,
				'id' => $id,
				'point_label' => $point_label,
				'point' => $point_label,
			];
			
			$rows[] = $row;
		}
		
		return [
			'data' => $table,
			'_' => [
				'type' => 'worklist.geo.points',
				'format' => 'table',
			]
		];
	}
};