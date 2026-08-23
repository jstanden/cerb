---
id: "docs-api-data-types"
title: "API: Data Types"
url: "https://cerb.ai/docs/api/data-types/"
summary: "This page provides a detailed overview of the data types used in API requests for Cerb. It describes each data type, including 'bit' for binary flags, 'char' for single characters, 'integer' for whole numbers, 'mixed' for various types depending on custom fields, 'string' for text values, and 'timestamp' for Unix 32-bit timestamps. Each type is explained with examples to clarify their usage in request options and payload fields."
tags: ["docs"]
---
Data types are provided for each request's options and payload fields. Here are the possible data types:

| Type | Description |
| --- | --- |
| **bit** | A binary flag with a value of `0` for **false** or `1` for **true**. |
| **char** | A single character. Example: `C` |
| **integer** | A whole number with no commas or decimals. Example: `1234` |
| **mixed** | A mixed data type can be any of these, depending on the custom field in question. See [custom fields](/docs/api/custom-fields/) for more information. |
| **string** | A text value. Example: `This is a string of text.` |
| **timestamp** | A Unix 32-bit timestamp representing the number of seconds since the Unix Epoch (January 1, 1970 00:00:00 GMT). Example: `1399376670` |

