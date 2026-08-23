---
id: "workflows-cerb-email-dmarcreports"
title: "DMARC Reports"
url: "https://cerb.ai/workflows/cerb.email.dmarc_reports/"
summary: "This page provides detailed information on the DMARC Reports workflow in Cerb, which is designed to automatically parse DMARC report attachments in emails. It includes sections on installation, usage, and configuration. The workflow is integrated into Cerb version 11.0 and above, and can be enabled through the Cerb interface. The usage section guides users on testing DMARC report interactions and configuring the delivery of DMARC reports to Cerb by setting up appropriate DNS records. Additionally, the page offers a reference template for building custom DMARC report workflows, detailing the necessary configurations and scripts to parse and interact with DMARC report attachments effectively."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
  - [Testing the DMARC report interaction](#testing-the-dmarc-report-interaction)
  - [Configure delivery of DMARC reports to Cerb](#configure-delivery-of-dmarc-reports-to-cerb)

- [Reference](#reference)

# Introduction

A DMARC[1](#fn:dmarc) (Domain-based Message Authentication, Reporting, and Conformance) report is a feedback mechanism used in email authentication to provide organizations with information about the successful and blocked deliveries of their emails using their own domain name (hostname) from various sender IP addresses. These reports help organizations monitor and enhance their email security and authentication practices.

DMARC reports include details about email authentication results, such as whether an email passed or failed SPF (Sender Policy Framework) and DKIM (DomainKeys Identified Mail) checks. They also provide insights into the source IP addresses of email senders and whether their emails were accepted, rejected, or marked as suspicious. This information is valuable for organizations to identify and address potential email spoofing, phishing, or unauthorized use of their domain names.

This workflow automatically parses DMARC report attachments in email.

# Installation

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» DMARC Reports**.

# Usage

### Testing the DMARC report interaction

Navigate to **Search&nbsp;» Attachments**.

Open the card for the file: `customer.example!cerb.example!1695078003!1695164407.xml.gz`

 

Click the **Open DMARC Report** button at the bottom of the popup.

 

The **DMARC Reporting** card widget will only appear on these attachments.

### Configure delivery of DMARC reports to Cerb

In the DNS of all sender domains, you should have a DMARC record configured like:

```
TXT _dmarc.cerb.example
v=DMARC1; p=reject; sp=reject; rf=afrf; adkim=s; aspf=s; pct=100; ri=86400;fo=1;
```

Add the following options to enable DMARC delivery reports. Replace `dmarc-reports@cerb.example` with an email address that delivers into Cerb.

```
rua=mailto:dmarc-reports@cerb.example; ruf=mailto:dmarc-reports@cerb.example;
```

# Reference

You can build your own DMARC reports workflow using this template as a reference.

Change occurrences of **cerb.email.dmarc\_reports** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.email.dmarc_reports
  version: 2026-08-13T00:00:00Z
  description: Parse DMARC report attachments in email
  website: https://cerb.ai/workflows/cerb.email.dmarc_reports/
  requirements:
    cerb_version: >=12.0 <12.1
    cerb_plugins: cerberusweb.core

records:
  automation/parse:
    fields:
      name: cerb.email.dmarcReports.parse
      extension_id: cerb.trigger.automation.function
      description: Parse DMARC report attachments
      script@raw:
        inputs:
          record/file:
            record_type: attachment
            required@bool: yes

        start:
          decision/format:
            # .xml.gz
            outcome/gzipXml:
              if@bool: {{inputs.file.name ends with '.xml.gz'}}
              then:
                file.read:
                  inputs:
                    uri: cerb:attachment:{{inputs.file.id}}
                    filters:
                      gzip.decompress:
                  output: file_contents

            # .zip
            outcome/zip:
              then:
                # Look inside the ZIP for the first XML file
                data.query/zip:
                  inputs:
                    query@text:
                      type:attachment.manifest
                      id:{{inputs.file.id}}
                      filter:*.xml
                      limit:1
                      format:dictionaries
                  output: results
                  on_success:
                    outcome/notFound:
                      if@bool: {{1 != results.data.cursor.num_files}}
                      then:
                        return:

                # Extract the XML file from the ZIP
                file.read:
                  inputs:
                    uri: cerb:attachment:{{inputs.file.id}}
                    extract: {{results.data.files[0].name}}
                  output: file_contents
                  on_success:
                    outcome/notXml:
                      # Must be XML
                      if@bool:
                        {{
                          not file_contents.bytes
                          or 'text/xml' != file_contents.mime_type
                        }}
                      then:
                        return:

          set/json:
            file_contents@json: {{xml_decode(file_contents.bytes)|json_encode}}

          outcome/notJson:
            # Must be JSON
            if@bool: {{not file_contents}}
            then:
              return:

          # If there's only one row, make a list for consistency
          outcome/oneRow:
            if@bool: {{file_contents.record.row is not empty}}
            then:
              var.set:
                inputs:
                  key: file_contents:record
                  value:
                    0@key: file_contents:record

          return:
            report@key: file_contents

      policy_kata@raw:
        commands:
          data.query:
            deny/type@bool: {{query.type != 'attachment.manifest'}}
            allow@bool: yes
          file.read:
            deny/uri@bool: {{inputs.uri is not prefixed ('cerb:attachment:')}}
            allow@bool: yes

  automation/interaction:
    fields:
      name: cerb.email.dmarcReports.interaction
      extension_id: cerb.trigger.interaction.worker
      description: Interaction for quick review of DMARC reports
      script@raw:
        inputs:
          record/file:
            required@bool: yes
            record_type: attachment

        start:
          function/dmarc:
            uri: cerb:automation:cerb.email.dmarcReports.parse
            output: results
            inputs:
              file: {{inputs.file.id}}
            on_success:
              await:
                form:
                  title: DMARC results
                  elements:
                    sheet/prompt_sheet:
                      data@key: results:report:record
                      schema:
                        layout:
                        columns:
                          text/ip:
                            params:
                              value_template@raw: {{row.source_ip}}
                          #interaction/ip:
                          # params:
                          # uri: cerb:automation:cerb.interaction.locationByIP
                          # text_template@raw: {{row.source_ip}}
                          # inputs:
                          # ip@raw: {{row.source_ip}}
                          text/domain:
                            params:
                              value_template@raw: {{identifiers.header_from}}
                          text/count:
                            params:
                              value_template@raw: {{row.count}}
                          icon/spf:
                            params:
                              image_template@raw: {{'pass' == row.policy_evaluated.spf ? 'circle-ok' : 'remove-2'}}
                          icon/dkim:
                            params:
                              image_template@raw: {{'pass' == row.policy_evaluated.dkim ? 'circle-ok' : 'remove-2'}}
                          text/disposition:
                            params:
                              value_template@raw: {{row.policy_evaluated.disposition}}
      policy_kata@raw:
        commands:
          function:
            deny/uri@bool: {{uri != 'cerb:automation:cerb.email.dmarcReports.parse'}}
            allow@bool: yes

  card_widget/attachment_card:
    fields:
      name: DMARC Reporting
      extension_id: cerb.card.widget.form_interaction
      record_type: attachment
      pos: 100
      width_units: 2
      zone: content
      extension_params:
        is_popup: 0
        interactions_kata@raw:
          interaction/dmarcReport:
            label: Open DMARC Report
            uri: cerb:automation:cerb.email.dmarcReports.interaction
            icon: toolbox
            hidden@bool:
              {{
                not (
                  record_mime_type in ['application/zip','application/gzip','application/octet-stream']
                  and record_name is pattern ('*!*!*.zip','*!*!*.xml.gz')
                )
              }}
            inputs:
              file: {{record_id}}
      options_kata@raw:
        hidden@bool:
          {{
            not (
              record_mime_type in ['application/zip','application/gzip','application/octet-stream']
              and record_name is pattern ('*!*!*.zip','*!*!*.xml.gz')
            )
          }}
  attachment/dmarc_example:
    fields:
      name: customer.example!cerb.example!1695078003!1695164407.xml.gz
      mime_type: application/gzip
      content: data:application/zip;base64,H4sICI+NC2UAA2N1c3RvbWVyLmV4YW1wbGUhY2VyYi5leGFtcGxlITE2OTUwNzgwMDMhMTY5NTE2NDQwNy54bWwA7VbbjtsgFHzfr4jyXl9y21Ri2T71C9pni8BxQtcGBDhN/75gwPYm2TbqbqWqWuUhZs6YOWcYO0GPp7aZHUEbLsXDvMyK+QwElYyL/cP865fPH7bz2SO+QzUA2xH6hO9mMxT52NFRnha+oEFJbasWLGHEEo85VOp9JUgLmHbGyhZ0BifSqgZQPpQCE1rCG8xaoumnS3KoRubJalJRKSyhtuKili/fdkkNe8RuOcOk3C3okq1gXW+u7DASw41uNqg0EfvYt4N2sOfOkM3HdXG/LYolygOS6iBYXy03q1Vx75oSabP8+W6D2tREpGTD6Y9KdbuGmwMMjUhnicAU9G7sNoKBQdgTb7FBebiIoFF1j/nvACms4RtQi3IVETNCJmGKWlwW7tD9Rd/stcacsVTq1KOW3wcXjOw0hYorvMqW2SIr3d4DlEhUdsLLoDxcJTxKwZE0nbOMpYL3gRslDbc+h0IKb8IEmfC8B4oYN/toR5y2joXBk8l4Z5rujNJQiDMQltfcPQXDbQcgDHRVa9menc20MkbjCI1UcI3/vBbFLyQR6eyh0mC6xo5dnE3426wEH6BxRy51z3NmpOXICDLRrLgY/JpqoomTt+tTNy1u/bhOvV/cKD1mOT+3w5NTIm8J5zrrP78I59/KZu3ecO/ZfG02g4v/ZzbLYpEtt9liXfgf37cOaHrj/0lEQ+E9ov96RK9L3xRRlA//A38Cx2teijoKAAA=
      attach@list:
        workflow:{{workflow_id}}
```

1. Wikipedia - DMARC https://en.wikipedia.org/wiki/DMARC&nbsp;[↩](#fnref:dmarc)

