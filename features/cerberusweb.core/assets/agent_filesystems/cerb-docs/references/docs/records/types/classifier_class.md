---
id: "docs-records-types-classifierclass"
title: "Classifier Classification Records"
url: "https://cerb.ai/docs/records/types/classifier_class/"
summary: "This page provides detailed information about the classification records in Cerb, including their structure and usage within the system. It outlines the fields available in the Records API, which are essential for managing classifications, such as `classifier_id`, `name`, and `updated_at`. The page also describes dictionary placeholders used in automations, snippets, and API responses, offering a range of fields like `_context`, `id`, and `record_url`. Additionally, it covers search query fields that facilitate filtering classification records based on criteria like `classifier`, `id`, and `name`. Lastly, it lists the worklist columns available for viewing classification data, including `c_classifier_id`, `c_name`, and `c_updated_at`, providing a comprehensive guide for users to effectively manage and utilize classification records in Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Classification |
| **Name (plural):** | Classifications |
| **Alias (uri):** | classifier\_class |
| **Identifier (ID):** | cerberusweb.contexts.classifier.class |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| **x** | **`classifier_id`** | [number](/docs/records/fields/types/number/) | The ID of the parent [classifier](/docs/records/types/classifier/) |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| **x** | **`name`** | [text](/docs/records/fields/types/text/) | The name of this classification |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `classifier_` | record | [Classifier](/docs/records/types/classifier/) |
| `classifier_owner_` | record | Classifier Owner |
| `id` | number | Id |
| `name` | text | Name |
| `record_url` | text | Record Url |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `custom_<id>` | mixed | [Custom Fields](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in classifier classification [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `classifier:` | [record](/docs/search/#deep-search) | [Classifier](/docs/records/types/classifier/) |
| `classifier.id:` | [chooser](/docs/search/#choosers) | [Classifier](/docs/records/types/classifier/) |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `links:` | [links](/docs/search/#links) | Record Links |
| `name:` | [text](/docs/search/#text) | Name |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on classifier classification [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_classifier_id` | Classifier |
| `c_dictionary_size` | Dictionary Size |
| `c_id` | Id |
| `c_name` | Name |
| `c_training_count` | Examples |
| `c_updated_at` | Updated |
| `cf_<id>` | [Custom Field](/docs/records/types/custom_field/) |

[\< Record Types](/docs/records/types/)

