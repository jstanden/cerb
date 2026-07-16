<?php
namespace Cerb\LLM\Providers\Interfaces;

interface Embedding {
	function embed(array $texts) : array;

	// The known embedding model ids (KATA `model:` value autocompletion).
	function getEmbeddingModels() : array;

	// KATA autocomplete contribution for this provider's embedding params block, consumed by
	// _DevblocksLlmService::getKataProviderAutocomplete(): ['keys' => block keys, 'values' => sub-path values].
	function getEmbeddingKataAutocomplete() : array;
}
