---
id: "solutions-automations-compare-values"
title: "Compare values using boolean logic"
url: "https://cerb.ai/solutions/automations/compare-values/"
summary: "This page from the Cerb documentation explains how to compare values using boolean logic groups in automations scripting, specifically through the use of boolean expressions and filters. The example demonstrates a script that sends a receipt email to a customer if the subject contains specific keywords, such as 'receipt' or 'kassenzettel', while excluding emails sent from addresses starting with 'noreply@' or 'spammer@'. The script uses Cerb's filtering capabilities, including the `|lower` filter to make the comparison case-insensitive."
tags: ["solutions", "solutions-automations"]
---
Compare against a set of values using boolean logic in automations [scripting](/docs/scripting/).

- [automation](#)
- [output](#)

- 
```
start:
  set:
    message:
      subject: Receipt for your recent order
      to: customer@example.com
      from: shop@example.com
  return:
    pass@bool:
      {{
        not (
          message.to starts with 'noreply@'
          or message.from starts with 'spammer@'
        ) and (
          'receipt' in message.subject|lower
          or 'kassenzettel' in message.subject|lower
        )
      }}
```
- 
```
__return:
  pass: true
```

In this case we use the [`|lower`](/docs/scripting/filters/#lower) filter since `in` is case-sensitive.

