<?php
namespace Cerb\LLM\Providers\Surfaces;

use Cerb\LLM\Providers\Interfaces\ChatStreaming;
use DevblocksLlmChatResponse;
use DevblocksLlmChatResponse_Tool;
use Extension_DevblocksLlmMemoryStore;

/**
 * The dialect-bearing half of a chat provider: everything whose shape depends on which WIRE FORMAT is
 * being spoken, and nothing that depends on which vendor is behind it.
 *
 * It exists because one provider id can front more than one API. `openai` serves both
 * `/v1/chat/completions` and `/v1/responses`, which disagree on the request body, the response envelope,
 * the stored message shape, the streaming grammar, and where a reasoning level lives -- but agree
 * completely on the model catalog, the brand, and the context windows.
 *
 * NOT the same set as `Interfaces\Chat`, which is why this is a distinct name rather than an alias for a
 * conjunction of the existing two. `Chat` also demands `getChatModels()` and `getChatKataAutocomplete()`,
 * which are facts about OpenAI THE VENDOR: identical on both surfaces, answered by the provider, and
 * meaningless on a surface. Bundling them is why a surface cannot implement `Chat`, and is worth
 * remembering if the provider interfaces are ever split properly.
 *
 * `ChatStreaming` is extended rather than listed separately: a surface owns the streaming grammar for its
 * own format (`_streamAccumulator`, `sanitizePartialContent`), so there is no such thing as a surface that
 * implements one and not the other.
 */
interface ChatSurface extends ChatStreaming {
	function chatCompletion(array $messages, string $system_prompt, array $tools, Extension_DevblocksLlmMemoryStore $memory) : DevblocksLlmChatResponse;

	function returnTool(DevblocksLlmChatResponse_Tool $tool, string $content, Extension_DevblocksLlmMemoryStore $memory) : void;

	function convertToGenericMessage(array $message, ?string $message_uuid=null) : DevblocksLlmChatResponse;

	function toNativeMessage(DevblocksLlmChatResponse $message) : array;

	function sanitizeMessages(array $messages) : array;

	// The level that will actually ship for a turn of this shape, which the two surfaces answer differently:
	// /v1/chat/completions has to force `none` whenever tools are present, /v1/responses does not.
	function getEffectiveEffort(bool $has_tools) : ?string;
}
