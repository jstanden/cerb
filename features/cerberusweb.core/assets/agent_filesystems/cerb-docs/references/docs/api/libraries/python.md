---
id: "docs-api-libraries-python"
title: "Cerb Web-API Library for Python"
url: "https://cerb.ai/docs/api/libraries/python/"
summary: "This page provides information about the Cerb Web-API Library for Python, contributed by CyberTechCafe-LLC. It includes links to the library's PyPI and GitHub pages, instructions for installing the library using pip, and examples of how to use the library to interact with the Cerb API. The usage examples demonstrate how to initialize the Cerb API client, retrieve a specific record, list available contexts, and perform a search query on records."
tags: ["docs"]
---
The Python library for the Cerb API was contributed by CyberTechCafe-LLC:

- https://pypi.python.org/pypi/cerbapi
- https://github.com/CyberTechCafe-LLC/cerbapi

# Installation

Install the module from `pip`:

```
pip install cerbapi
```

# Usage

```
from cerbapi import Cerb

cerb = Cerb(
        access_key='myaccesskey',
        secret='IdeallyDontStoreThisInYourCodeLikeThis',
        base='https://cerb.example/rest/'
        )

print(cerb.get_record('ticket', 1))
print(cerb.get_contexts())
print(cerb.search_records('comment', query='author.worker:Rob'))
```
