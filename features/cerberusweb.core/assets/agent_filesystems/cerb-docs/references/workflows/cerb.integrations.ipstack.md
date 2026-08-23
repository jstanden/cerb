---
id: "workflows-cerb-integrations-ipstack"
title: "Geolocate IPs (IPstack)"
url: "https://cerb.ai/workflows/cerb.integrations.ipstack/"
summary: "This page provides a comprehensive guide on integrating Cerb with IPstack to geolocate IP addresses and display their locations on maps. It covers the installation process, including the requirement to create an IPstack connected account and enabling the workflow in Cerb 11.0+. The usage section explains how to interact with the system to locate IP addresses, including using the Cerb interface and adding the interaction to custom widgets and toolbars. Additionally, the page offers a reference template for building a custom 'Geolocate IPs' workflow, detailing the necessary configurations, scripts, and policies to retrieve and display geospatial data about IP addresses."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Installation](#installation)
  - [Requirements](#requirements)
  - [Cerb](#cerb)

- [Usage](#usage)
  - [Using the interaction](#using-the-interaction)

- [Reference](#reference)

# Introduction

This workflow integrates Cerb with IPstack for geolocating IP addresses and rendering locations on maps.

# Installation

## Requirements

[Create an IPstack connected account](/solutions/integrations/ipstack/) if you haven't already.

## Cerb

This workflow is built into Cerb [11.0+](/releases/11.0/). It will automatically update.

You can enable it from **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» Geolocate IPs (IPstack)**.

Link the workflow to the IPstack connected account you created above.

# Usage

## Using the interaction

Click on the floating Cerb icon in the lower right and select **Location by IP** from the menu.

 

Enter an IP address:

You can click the reset icon to repeat the interaction multiple times before closing the popup.

The **cerb.integrations.ipstack.lookupIP.interaction** interaction can be added to your own widgets and toolbars.

# Reference

You can build your own **Geolocate IPs** workflow using this template as a reference.

Change occurrences of **cerb.integrations.ipstack** to your own workflow identifier. Use a prefix based on a domain you own (e.g. `com.example.workflow`).

```
workflow:
  name: cerb.integrations.ipstack
  version: 2026-08-07T00:00:00Z
  description: Geolocate IPs and render locations on maps.
  website: https://cerb.ai/workflows/cerb.integrations.ipstack/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core,
  config:
    chooser/ipstack_account_id:
      label: IPstack Account
      record_type: connected_account
      record_query: service:(ipstack)
      multiple@bool: no

records:
  automation/automation_interaction:
    fields:
      name: cerb.integrations.ipstack.lookupIP.interaction
      extension_id: cerb.trigger.interaction.worker
      description@text:
      script@raw:
        inputs:
          text/ip:

        start:
          decision/hasInput:
            outcome/input:
              if@bool: {{inputs.ip is empty}}
              then:
                await:
                  form:
                    elements:
                      text/prompt_ip:
                        label: What IP would you like to locate?
                        required@bool: yes
                        type: ipv4
            outcome:
              then:
                set:
                  prompt_ip@key: inputs:ip

          function:
            uri: cerb:automation:cerb.integrations.ipstack.lookupIP.function
            output: results
            inputs:
              ip: {{prompt_ip}}
            on_success:
              set:
                prompts:
                  say/respond:
                    content: ## Location of {{prompt_ip}}
                  sheet/respond_sheet@ref: sheetFormat
              decision:
                outcome/usa:
                  if@bool:
                      {{results.data.country_name == 'United States' ? 'yes'}}
                  then:
                    var.push:
                      inputs:
                        key: prompts
                        value@ref: usaMapSchema
                outcome/world:
                  then:
                    var.push:
                      inputs:
                        key: prompts
                        value@ref: worldMapSchema
              await:
                form:
                  elements@key: prompts
            on_error:
              error:
                message@key: results:error

        &worldMapSchema:
          map/respond_map:
            resource:
              uri: cerb:resource:map.world.countries
            projection:
              type: mercator
              scale: 90
              center:
                latitude: {{results.data.latitude}}
                longitude: {{results.data.longitude}}
              zoom:
                latitude: {{results.data.latitude}}
                longitude: {{results.data.longitude}}
                scale: 3.5
            points@ref: mapPointSchema

        &usaMapSchema:
          map/respond_map:
            resource:
              uri: cerb:resource:map.country.usa.states
            projection:
              type: albersUsa
              scale: 600
              zoom:
                latitude: {{results.data.latitude}}
                longitude: {{results.data.longitude}}
                scale: 1.5
            points@ref: mapPointSchema

        &mapPointSchema:
          data:
            point:
              latitude: {{results.data.latitude}}
              longitude: {{results.data.longitude}}
              properties:
                name@text:
                  {{results.data.city}}, {{results.data.country_name}}
                city: {{results.data.city}}
                country: {{results.data.country_name}}
          size:
            default: 5
          fill:
            default: #FF0000

        &sheetFormat:
          data@json:
            [{{results.data|json_encode}}]
          schema:
            layout:
              style: fieldsets
              paging@bool: no
            columns:
              text/hostname:
                label: Hostname
                params:
                  value_template@raw: {{dns_host_by_ip(ip)}}
              text/city:
                label: City
              text/region_name:
                label: State/Region
              text/country_name:
                label: Country
              text/coordinates:
                label: Coordinates
                params:
                  value_template@raw:
                    {{latitude}}, {{longitude}}

      policy_kata@raw:
        commands:
          function:
            allow/name@bool: {{uri == 'cerb:automation:cerb.integrations.ipstack.lookupIP.function'}}
  automation/automation_func:
    fields:
      name: cerb.integrations.ipstack.lookupIP.function
      extension_id: cerb.trigger.automation.function
      description: Return geospatial data about an IP address
      script@raw:
        inputs:
          text/ip:
            type: ip
            required@bool: yes

        start:
          set:
            config@json: {{cerb_workflow_config('cerb.integrations.ipstack')|json_encode}}
          storage.get:
            inputs:
              key: api:ipstack:{{inputs.ip}}
            output: ip_data
            on_error:
              http.request@ref: httpRequest
          return:
            data@key: ip_data

        &httpRequest:
          output: http_response
          inputs:
            url: http://api.ipstack.com/{{inputs.ip|url_encode}}
            authentication: cerb:connected_account:{{config.ipstack_account_id}}
          on_success:
            set:
              response_json@json: {{http_response.body}}
            decision:
              outcome/error:
                if@bool: {{not response_json.ip ? 'yes'}}
                then:
                  error: Failed to retrieve the IP.

              outcome/success:
                then:
                  set:
                    ip_data@key: response_json
                  storage.set:
                    inputs:
                      key: api:ipstack:{{inputs.ip}}
                      value@key: ip_data
                      expires: 1 week
          on_error:
            error:
              message@key: http_response:error

      policy_kata@raw:
        commands:
          http.request:
            deny/url@bool: {{inputs.url is not prefixed ('http://api.ipstack.com/')}}
            allow@bool: yes
          storage.get:
            allow@bool: yes
          storage.set:
            allow@bool: yes

 toolbar_section/toolbar_global_menu:
    fields:
      name: Lookup IP
      toolbar_name: global.menu
      priority: 200
      is_disabled: 0
      toolbar_kata@raw:
        interaction/lookupIp:
          label: Location by IP
          uri: cerb:automation:cerb.integrations.ipstack.lookupIP.interaction
          icon: map
```
