---
id: "docs-automations-commands-repeat"
title: "Automations: repeat"
url: "https://cerb.ai/docs/automations/commands/repeat/"
summary: "This page provides a detailed explanation of the 'repeat' command in Cerb automations, which is used to iterate over an array and execute a sequence of commands for each element. It includes a practical example where numbers from 1 to 10 are summed, resulting in a total of 55. The page also outlines the syntax for using the 'repeat' command, including the keys 'each:', 'as:', and 'do:'. The 'each:' key specifies the array to iterate over, which can be formatted as CSV, JSON, or a newline-delimited list, or read directly from a placeholder with the '@key' annotation. The 'each:' key is optional, and when it's missing or doesn't resolve to an array it's treated as empty, so the loop runs zero times without reporting an error. The 'as:' key names the placeholder for the current iteration's value and is required at run time, and the 'do:' key contains the commands to be executed repeatedly."
tags: ["docs", "docs-automations"]
---
The **repeat:** command iterates an array and repeats a sequence of commands for each value.

```
start:
  set:
    sum: 0
  repeat:
    each@json: [1,2,3,4,5,6,7,8,9,10]
    as: i
    do:
      set:
        sum@int: {{sum + i}}
  return:
    counter@key: sum
```

```
counter: 55
```

- [Syntax](#syntax)
  - [each:](#each)
  - [as:](#as)
  - [do:](#do)

# Syntax

### each:

The `each:` key must resolve to an array.

This is most often accomplished with one of the following annotations:

| Annotation | Description |
| --- | --- |
| `@csv` | Comma-separated values |
| `@json` | JSON-encoded values |
| `@key` | An array read from a [dictionary](/docs/kata/#key) path |
| `@list` | Newline-delimited values |

Use [`@key`](/docs/kata/#key) when the array is already held by a placeholder. This reads the value from the dictionary and preserves its type, so the array doesn't make a round trip through text:

```
repeat:
  each@key: prompt_tasks
  as: task_id
  do:
    log: Linking task {{task_id}}
```

Here `prompt_tasks` holds an array of record IDs from a multi-select `sheet:` element in a preceding [await:](/docs/automations/commands/await/) form. An `await:` form element's key is the name of the placeholder it sets, so pairing a sheet with `@key` is the general shape rather than a quirk of this example.

If `each:` doesn't resolve to an array then it's treated as an empty array. The `do:` block runs zero times, the automation continues with the next command, and nothing is logged. There's no error.

A missing `each:` behaves the same way. The `each:` key isn't required, and neither its presence nor its type is checked when the automation is saved.

This is the usual outcome of writing a bare `each: {{my_list}}`. A placeholder renders as text, and text isn't an array, so the loop quietly does nothing. Read the array with `@key`, or convert text into one with `@csv`, `@json`, or `@list`.

The [while:](/docs/automations/commands/while/) command has the opposite failure mode for the same reason. See [if:](/docs/automations/commands/while/#if).

### as:

The `as:` key names the placeholder that holds the value of the current iteration of `each:`.

This may optionally take the format of `key, value` to set a placeholder for both the key and value of `each:` item.

The `as:` key is **required**, but only when the automation runs. The parser doesn't check for it, so an automation without `as:` saves without complaint and then returns an `as: is required.` error on execution. Unlike `each:`, it doesn't fail quietly.

### do:

The `do:` key contains any number of [commands](/docs/automations/#commands) to repeat.

