---
id: "docs-scripting-filters--strippemblocks"
title: "Scripting Filter: strip_pem_blocks"
url: "https://cerb.ai/docs/scripting/filters/#strippemblocks"
summary: "Remove the contents of PEM-formatted blocks like PGP keys and SSL certificates"
tags: ["docs", "docs-scripting"]
---
## strip\_pem\_blocks

Remove the contents of PEM-formatted blocks like PGP signatures, public keys, and SSL certificates from a block of text. This is particularly useful when sanitizing text for indexing by a custom [search index](/docs/records/types/search_index/), where the long base64 payloads contribute noise rather than searchable terms.

The `-----BEGIN-----` and `-----END-----` markers are kept, on one line, so the text still records that a block was there.

`|strip_pem_blocks`

```
{% set message %}
Hello,

Here is my reply.

-----BEGIN PGP SIGNATURE-----

iQIzBAEBCAAdFiEE...
-----END PGP SIGNATURE-----
{% endset %}
{{message|strip_pem_blocks}}
```

```
Hello,

Here is my reply.

-----BEGIN PGP SIGNATURE----- -----END PGP SIGNATURE-----
```
