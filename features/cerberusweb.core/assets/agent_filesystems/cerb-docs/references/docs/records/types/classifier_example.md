---
id: "docs-records-types-classifierexample"
title: "Classifier Example Records"
url: "https://cerb.ai/docs/records/types/classifier_example/"
summary: "This page provides detailed information about the structure and functionality of 'Classifier Example' records in Cerb. It outlines the fields available in the Records API, including required fields like `classifier_id` and `expression`, and optional fields such as `links` and `updated_at`. The page also describes dictionary placeholders used in automations, snippets, and API responses, offering a comprehensive list of fields like `_context`, `class_`, and `record_url`. Additionally, it details search query fields that can be used to filter classifier example records, such as `class:`, `classifier:`, and `expression:`. Lastly, it lists the worklist columns available for organizing classifier example data, including `c_class_id`, `c_classifier_id`, and `c_updated_at`. This information is crucial for users looking to manage and utilize classifier examples effectively within Cerb."
tags: ["docs", "docs-records-types"]
---
| **Name (singular):** | Classifier Example |
| **Name (plural):** | Classifier Examples |
| **Alias (uri):** | classifier\_example |
| **Identifier (ID):** | cerberusweb.contexts.classifier.example |

- [Records API](#records-api)
- [Dictionary Placeholders](#dictionary-placeholders)
- [Search Query Fields](#search-query-fields)
- [Worklist Columns](#worklist-columns)

### Records API

These fields are available in the [Records API](/docs/api/endpoints/records/) and [packages](/docs/packages/):

| Req'd | Field | Type | Notes |
| --- | --- | --- | --- |
| &nbsp; | `class_id` | [number](/docs/records/fields/types/number/) | The ID of the [classification](/docs/records/types/classifier_class/) this example trains |
| **x** | **`classifier_id`** | [number](/docs/records/fields/types/number/) | The ID of the [classifier](/docs/records/types/classifier/) this example belongs to |
| **x** | **`expression`** | [text](/docs/records/fields/types/text/) | The expression used for training the classifier |
| &nbsp; | `links` | [links](/docs/records/fields/types/links/) | An array of record `type:id` tuples to link to. Prefix with `-` to unlink. |
| &nbsp; | `updated_at` | [timestamp](/docs/records/fields/types/timestamp/) | The date/time when this record was last modified |

### Dictionary Placeholders

These [placeholders](/docs/scripting/variables/#placeholders) are available in [dictionaries](/docs/guide/developers/dictionaries/) for [automations](/docs/automations/), [snippets](/docs/snippets/), and [API](/docs/api/) responses:

| Field | Type | Description |
| --- | --- | --- |
| `_context` | text | [Record type](/docs/records/types/) extension ID |
| `_label` | text | Label |
| `_type` | text | [Record type](/docs/records/types/) alias |
| `class_` | record | [Classification](/docs/records/types/classifier_class/) |
| `classifier_` | record | [Classifier](/docs/records/types/classifier/) |
| `classifier_owner_` | record | Classifier Owner |
| `expression` | text | Expression |
| `id` | number | Id |
| `record_url` | text | Record Url |
| `updated_at` | date | Updated |

These optional placeholders are also available with **key expansion** in [dictionaries](/docs/guide/developers/dictionaries/#key-expansion) and the [API](/docs/api/responses/#expanding-keys-in-api-requests):

| Field | Type | Description |
| --- | --- | --- |
| `comment_count` | number | [Comment](/docs/records/types/comment/) count on the record |
| `comments` | comments | [Comments](/docs/guide/developers/dictionaries/#key-expansion) |
| `links` | links | [Links](/docs/guide/developers/dictionaries/#key-expansion) |

### Search Query Fields

These [filters](/docs/search/#filters) are available in classifier example [search queries](/docs/search/):

| Field | Type | Description |
| --- | --- | --- |
| `class:` | [record](/docs/search/#deep-search) | [Class](/docs/records/types/classifier_class/) |
| `class.id:` | [chooser](/docs/search/#choosers) | [Classification](/docs/records/types/classifier_class/) |
| `classifier:` | [record](/docs/search/#deep-search) | [Classifier](/docs/records/types/classifier/) |
| `classifier.id:` | [chooser](/docs/search/#choosers) | [Classifier](/docs/records/types/classifier/) |
| `expression:` | [text](/docs/search/#text) | Expression |
| `fieldset:` | [record](/docs/search/#deep-search) | [Fieldset](/docs/records/types/custom_fieldset/) |
| `id:` | [number](/docs/search/#numbers) | Id |
| `updated:` | [date](/docs/search/#dates) | Updated |

### Worklist Columns

These columns are available on classifier example [worklists](/docs/worklists/):

| Column | Description |
| --- | --- |
| `c_class_id` | Classification |
| `c_classifier_id` | Classifier |
| `c_expression` | Expression |
| `c_id` | Id |
| `c_updated_at` | Updated |

[\< Record Types](/docs/records/types/)

