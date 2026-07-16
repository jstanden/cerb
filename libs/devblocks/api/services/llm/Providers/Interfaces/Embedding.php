<?php
namespace Cerb\LLM\Providers\Interfaces;

interface Embedding {
	function embed(array $texts) : array;

	// The known embedding model ids (KATA `model:` value autocompletion).
	function getEmbeddingModels() : array;
}
