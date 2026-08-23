---
id: "guides-automations-interaction-worker-process-csv-file"
title: "Process an uploaded CSV file"
url: "https://cerb.ai/guides/automations/interaction.worker/process-csv-file/"
summary: "This page provides a guide on how to process an uploaded CSV file using the interaction.worker automation in Cerb. The automation reads and processes the file in chunks to avoid memory and timeout issues. It uses a while loop to ingest chunks, processing each row individually and combining it with column headers to create associative arrays. The process can be customized based on specific needs, and it includes examples of different use cases, such as creating tasks and sending email using scheduled drafts."
tags: ["guides"]
---
- [Introduction](#introduction)
- [Code](#code)
- [Examples](#examples)
  - [Create tasks](#create-tasks)
  - [Send email using a template and scheduled drafts](#send-email-using-a-template-and-scheduled-drafts)

# Introduction

This [automation](/docs/automations/) reads and processes an uploaded CSV file in chunks to avoid memory and timeout issues. The example is intended to be customized based on your needs.

You can add the [interaction](/docs/interactions/) to any [toolbar](/docs/toolbars/) in Cerb's UI.

Keep in mind that each step of an interaction has a time limit of 30 seconds. It's important to occasionally use an [await:](/docs/automations/commands/await/) to report progress to the user. Depending on your use case this may be done for every row or in batches.

If you have a time-intensive process that the user doesn't need to wait for, use [queue.push:](/docs/automations/commands/queue.push/) here to quickly add the rows to a queue and process them asynchronously in the background. You can notify the user when background processing is complete.

# Code

| **Trigger:** | [interaction.worker](/docs/automations/triggers/interaction.worker/) |

- [automation](#)
- [policy](#)

- 
```
1
2
3
4
5
6
7
8
9
10
11
12
13
14
15
16
17
18
19
20
21
22
23
24
25
26
27
28
29
30
31
32
33
34
35
36
37
38
39
40
41
42
43
44
45
46
47
48
49
50
51
52
53
54
55
56
57
58
59
60
61
62
63
64
65
66
67
68
69
70
71
72
73
74
75
76
77
78
start:
  set/config:
    # The bytes of the CSV to read in a single iteration
    chunk_size@int: 1024000
    # Start at the beginning of the file
    offset_from@int: 0
    # We'll store the CSV headings from the first row here
    csv_headings@list:
    # This will be true when we hit the end of the file
    is_eof@bool: no
  
  # Get a file from the user
  await/upload:
    form:
      title: Upload CSV
      elements:
        fileUpload/prompt_file:
          label: Upload CSV file:
          as: automation_resource
          required@bool: yes

  # Loop to ingest chunks
  while:
    if@bool: {{not is_eof}}
    do:
      file.read:
        output: results
        inputs:
          uri: cerb:automation_resource:{{prompt_file}}
          # Process batches of lines to avoid timeouts
          length@int: {{chunk_size}}
          length_split@json: "\n"
          offset: {{offset_from}}
        on_success:
          set:
            csv_rows@json: {{results.bytes|parse_csv|json_encode}}
            offset_from: {{results.offset_to}}
          # [TODO] Verify array results
      
      # The first time, grab the column names
      outcome/firstChunk:
        if@bool: {{0 == results.offset_from}}
        then:
          set:
            csv_headings@json: {{csv_rows|first|json_encode}}
            csv_rows@json: {{csv_rows[1:]|json_encode}}
          # [TODO] Enforce the required columns
      
      # Merge the headings as keys
      set/headings:
        csv_rows@json: {{csv_rows|map((row) => array_combine(csv_headings, row))|json_encode}}
      
      # Loop through the each row
      repeat/rows:
        each@csv: {{csv_rows|keys|join(',')}}
        as: row_id
        do:
          set/row:
            csv_row@json: {{csv_rows[row_id]|json_encode}}
          
          # [TODO] You can do anything with the row data now (tasks, drafts, etc)
          # This example prints each row, which you probably don't want.
          # If the process is time-consuming it's best to use a queue here.
          await/output:
            form:
              title: Row
              elements:
                say:
                  content@text:
                    | | |
                    |-|-|
                    {% for key in csv_row|keys %}
                    | **{{key|trim}}:** | {{csv_row[key]}} |
                    {% endfor %}
      
      # We're done when the last read byte is the size of the file
      set/eof:
        is_eof@bool: {{offset_from + 1 >= results.size}}
```

Here's a breakdown by line numbers:

**Lines 1-10: Initialization**

  - Sets up initial variables including chunk size (1MB), starting offset (0), empty CSV headings list, and EOF flag

**Lines 13-20: File Upload**

  - Creates a form prompting the user to upload a CSV file
  - Stores the uploaded file as an automation resource with the placeholder ``

**Lines 22-24: Main Processing Loop**

  - Begins a while loop that continues until the end of file is reached

**Lines 25-34: File Reading**

  - Reads a chunk of the file starting from the current offset
  - Uses newline characters as delimiters to split into complete rows
  - Parses the chunk as CSV and stores results as ``
  - Updates the offset position for the next iteration

**Lines 41-47: Header Processing (First Chunk Only)**

  - On the first iteration (when offset is 0), extracts column headers from the first row
  - Removes the header row from the data rows to avoid processing it as data

**Lines 50-51: Data Structure Mapping**

  - Combines the CSV headers with each data row to create associative arrays
  - This transforms simple arrays into key-value pairs using column names as keys

**Lines 54-74: Row Processing**

  - Iterates through each row in the current chunk
  - Converts each row to JSON format for processing
  - Displays each row in a formatted table (this is placeholder logic meant to be replaced)
  - Comments indicate this is where actual business logic would be implemented

**Lines 77-78: EOF Detection**

  - Checks if the current offset has reached the file size to determine when processing is complete
  - Sets the EOF flag to exit the main loop

- 
```
commands:
  file.read:
    allow@bool: yes
```

# Examples

These examples implement processing logic for different use cases (replacing lines 53-74).

In the reference, each row is processed individually (line 54) and displayed to the worker (line 64).

## Create tasks

We can create a [task](/docs/records/types/task/) record for each row in the uploaded CSV file.

- On line 12 we add `count_tasks_created` to keep a running total of new tasks.

- On lines 48-52 we enforced the required "Title" column.

- On lines 66-76 we create a task record for every row in the CSV file. We can use the [@optional](/docs/automations/#annotations) annotation for columns that are optional to skip those fields when empty. We use the `on_success:` event to increment the task counter.

- On lines 82-88 we output the number of created tasks at the end.

- [automation](#)
- [policy](#)
- [CSV](#)

- 
```
1
2
3
4
5
6
7
8
9
10
11
12
13
14
15
16
17
18
19
20
21
22
23
24
25
26
27
28
29
30
31
32
33
34
35
36
37
38
39
40
41
42
43
44
45
46
47
48
49
50
51
52
53
54
55
56
57
58
59
60
61
62
63
64
65
66
67
68
69
70
71
72
73
74
75
76
77
78
79
80
81
82
83
84
85
86
87
88
start:
  set/config:
    # The bytes of the CSV to read in a single iteration
    chunk_size@int: 1024000
    # Start at the beginning of the file
    offset_from@int: 0
    # We'll store the CSV headings from the first row here
    csv_headings@list:
    # This will be true when we hit the end of the file
    is_eof@bool: no
    # Keep track of the number of imported tasks
    count_tasks_created@int: 0
  
  # Get a file from the user
  await/upload:
    form:
      title: Upload CSV
      elements:
        fileUpload/prompt_file:
          label: Upload CSV file:
          as: automation_resource
          required@bool: yes

  # Loop to ingest chunks
  while:
    if@bool: {{not is_eof}}
    do:
      file.read:
        output: results
        inputs:
          uri: cerb:automation_resource:{{prompt_file}}
          # Process batches of lines to avoid timeouts
          length@int: {{chunk_size}}
          length_split@json: "\n"
          offset: {{offset_from}}
        on_success:
          set:
            csv_rows@json: {{results.bytes|parse_csv|json_encode}}
            offset_from: {{results.offset_to}}
      
      # The first time, grab the column names
      outcome/firstChunk:
        if@bool: {{0 == results.offset_from}}
        then:
          set:
            csv_headings@json: {{csv_rows|first|json_encode}}
            csv_rows@json: {{csv_rows[1:]|json_encode}}
          # Enforce the required columns
          outcome/verify:
            if@bool: {{'Title' not in csv_headings}}
            then:
              error: The CSV file must have a column named "Title".
      
      # Merge the headings as keys
      set/headings:
        csv_rows@json: {{csv_rows|map((row) => array_combine(csv_headings, row))|json_encode}}
      
      # Loop through the each row
      repeat/rows:
        each@csv: {{csv_rows|keys|join(',')}}
        as: row_id
        do:
          set/row:
            csv_row@json: {{csv_rows[row_id]|json_encode}}
          
          record.create/task:
            output: new_task
            inputs:
              record_type: task
              fields:
                title: {{csv_row['Title']}}
                importance@optional: {{csv_row['Importance']}}
                status: open
            on_success:
              set:
                count_tasks_created@int: {{count_tasks_created + 1}}

      # We're done when the last read byte is the size of the file
      set/eof:
        is_eof@bool: {{offset_from + 1 >= results.size}}
  
  await/results:
    form:
      title: Imported
      elements:
        say:
          content@text:
            Imported {{count_tasks_created}} task{{1 != count_tasks_created ? 's'}}.
```
- 
```
commands:
  file.read:
    allow@bool: yes
  record.create:
    deny/type@bool: {{inputs.record_type is not record type ('task')}}
    allow@bool: yes
```
- 
```
Title,Importance
"This is a sample task",50
"This is sample low-priority task",25
"This is sample high-priority task",75
```

## Send email using a template and scheduled drafts

We can create a ticket and schedule an outgoing email draft for each row in the uploaded CSV file.

- On lines 7-8 we add a list of required CSV column names.

- On lines 13-14 we add `count_tickets_created` to keep a running total of new tickets.

- On lines 15-27 we create an email template with group name, status, delivery time, subject, and body. All of these can use `` placeholders. Note that the `@raw` annotation prevents the placeholders from being substituted until we send each message.

- On lines 63-68 we validate the required CSV columns from earlier.

- On lines 82-88 we replace the placeholders with the CSV row values.

- On lines 90-116 we create a [ticket](/docs/records/types/ticket/) and [draft](/docs/records/types/draft/) record for every row in the CSV file. We can use the [@optional](/docs/automations/#annotations) annotation for columns that are optional to skip those fields when empty. We create the draft in the `on_success:` event of the new ticket, then increment the ticket counter.

- On lines 118-130 we show the user a progress update every 25 created tickets.

- On lines 136-142 we output the number of created tickets at the end. This could be more sophisticated and show the new records in a `sheet:` from an `await:form:`.

- [automation](#)
- [policy](#)
- [CSV](#)

- 
```
1
2
3
4
5
6
7
8
9
10
11
12
13
14
15
16
17
18
19
20
21
22
23
24
25
26
27
28
29
30
31
32
33
34
35
36
37
38
39
40
41
42
43
44
45
46
47
48
49
50
51
52
53
54
55
56
57
58
59
60
61
62
63
64
65
66
67
68
69
70
71
72
73
74
75
76
77
78
79
80
81
82
83
84
85
86
87
88
89
90
91
92
93
94
95
96
97
98
99
100
101
102
103
104
105
106
107
108
109
110
111
112
113
114
115
116
117
118
119
120
121
122
123
124
125
126
127
128
129
130
131
132
133
134
135
136
137
138
139
140
141
142
start:
  set/config:
    # The bytes of the CSV to read in a single iteration
    chunk_size@int: 1024000
    # Start at the beginning of the file
    offset_from@int: 0
    # The required CSV headings
    csv_headings_required@csv: Email, First Name
    # We'll store the CSV headings from the first row here
    csv_headings@list:
    # This will be true when we hit the end of the file
    is_eof@bool: no
    # Keep track of the number of created drafts
    count_tickets_created@int: 0
    # The email template to send with `{{csv_row['Column Name']}}` placeholders from the file
    email_template:
      group: Support
      status: waiting
      deliver_at: 20 minutes
      subject@raw: New email for {{csv_row['First Name']|default('you')}}
      body@raw:
        Hello {{csv_row['First Name']|default('there')}},
        
        This is a sample email body.
        
        -- 
        Support
  
  # Get a file from the user
  await/upload:
    form:
      title: Upload CSV
      elements:
        fileUpload/prompt_file:
          label: Upload CSV file:
          as: automation_resource
          required@bool: yes

  # Loop to ingest chunks
  while:
    if@bool: {{not is_eof}}
    do:
      file.read:
        output: results
        inputs:
          uri: cerb:automation_resource:{{prompt_file}}
          # Process batches of lines to avoid timeouts
          length@int: {{chunk_size}}
          length_split@json: "\n"
          offset: {{offset_from}}
        on_success:
          set:
            csv_rows@json: {{results.bytes|parse_csv|json_encode}}
            offset_from: {{results.offset_to}}
      
      # The first time, grab the column names
      outcome/firstChunk:
        if@bool: {{0 == results.offset_from}}
        then:
          set:
            csv_headings@json: {{csv_rows|first|json_encode}}
            csv_rows@json: {{csv_rows[1:]|json_encode}}
          # Enforce the required columns
          outcome/verify:
            if@bool:
              {{array_intersect(csv_headings_required,csv_headings)|length != csv_headings_required|length}}
            then:
              error: The CSV file must have columns named: {{csv_headings_required|join(', ')}}
      
      # Merge the headings as keys
      set/headings:
        csv_rows@json: {{csv_rows|map((row) => array_combine(csv_headings, row))|json_encode}}
      
      # Loop through the each row
      repeat/rows:
        each@csv: {{csv_rows|keys|join(',')}}
        as: row_id
        do:
          set/row:
            csv_row@json: {{csv_rows[row_id]|json_encode}}
            
          # Replace the email template placeholders
          kata.parse:
            output: parsed_template
            inputs:
              dict:
                csv_row@key: csv_row
              kata@key: email_template

          record.create/ticket:
            output: new_ticket
            inputs:
              record_type: ticket
              fields:
                subject: {{parsed_template.subject}}
                participants: {{csv_row['Email']}}
                group: {{parsed_template.group}}
                status@optional: {{parsed_template.status}}
            on_success:
              record.create/draft:
                output: new_draft
                inputs:
                  record_type: draft
                  fields:
                    type: ticket.reply
                    ticket_id: {{new_ticket.id}}
                    name: {{parsed_template.subject}}
                    is_queued: 1
                    queue_delivery_date@date: {{parsed_template.deliver_at}}
                    # See: https://cerb.ai/docs/records/types/draft/#params-ticketreply--ticketforward
                    params:
                      to: {{csv_row['Email']}}
                      subject: {{parsed_template.subject}}
                      content: {{parsed_template.body}}
              set:
                count_tickets_created@int: {{count_tickets_created + 1}}

              # Keep the user informed of progress
              outcome/progress:
                if@bool: {{0 == count_tickets_created % 25}}
                then:
                  await:
                    form:
                      title: Importing CSV
                      elements:
                        say:
                          content@text:
                            Imported {{count_tickets_created}} ticket{{1 != count_tickets_created ? 's'}}...
                        submit:
                          is_automatic@bool: yes

      # We're done when the last read byte is the size of the file
      set/eof:
        is_eof@bool: {{offset_from + 1 >= results.size}}
  
  await/results:
    form:
      title: Imported
      elements:
        say:
          content@text:
            Imported {{count_tickets_created}} ticket{{1 != count_tickets_created ? 's'}}.
```
- 
```
commands:
  file.read:
    allow@bool: yes
  record.create:
    deny/type@bool: {{inputs.record_type is not record type ('draft','ticket')}}
    allow@bool: yes
```
- 
```
Email,First Name
s.gallo@fiaflux.example,Sofia
c.bertin@baston.example,Claire
```

