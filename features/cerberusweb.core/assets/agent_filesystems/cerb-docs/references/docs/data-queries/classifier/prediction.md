---
id: "docs-data-queries-classifier-prediction"
title: "Data Queries: Classifier Prediction"
url: "https://cerb.ai/docs/data-queries/classifier/prediction/"
summary: "This page provides information on how to use the `classifier.prediction` feature in Cerb to obtain predicted classifications for given text inputs using specified classifiers. It details the necessary inputs, such as the classifier to use and the text to classify, and describes the available response formats, with the default being a dictionary format suitable for spreadsheets and API results. An example is provided to illustrate how a 'Yes/No' classifier might predict an `answer.maybe` for the input 'I am not sure.'"
tags: ["docs"]
---
# classifier.prediction

`classifier.prediction` queries return a predicted [classification](/docs/records/types/classifier_class/) for the given text using the given [classifier](/docs/records/types/classifier/).

For instance, a "Yes/No" classifier would predict `answer.maybe` for the input _"I'm not sure"_.

- [Inputs](#inputs)
- [Response Formats](#response-formats)
- [Examples](#examples)

# Inputs

- `classifier:` (a [search query](/docs/search/) of [classifier](/docs/records/types/classifier/#search-query-fields) records to include)
- `text:` (the text to classify)

# Response Formats

The results can be returned in these formats:

- **dictionaries** (default) returns a table-based format suitable for [sheets](/docs/sheets/) and API results.

# Examples

```
type:classifier.prediction
classifier:(name:Yes/No)
text:"I am not sure"
format:dictionaries
```
