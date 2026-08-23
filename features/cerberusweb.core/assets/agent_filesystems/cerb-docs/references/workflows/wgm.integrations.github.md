---
id: "workflows-wgm-integrations-github"
title: "GitHub Issues"
url: "https://cerb.ai/workflows/wgm.integrations.github/"
summary: "This page explains how to integrate Cerb with GitHub's API, allowing users to automate tasks such as creating GitHub issues and interacting with repositories. The workflow contains multiple automations for demonstrating integration between Cerb and GitHub's API, including functions for getting a list of user repositories and creating new GitHub issues. To configure the workflow, users are prompted to select the connected GitHub account they created earlier and can update the configuration at any time by editing the workflow profile. Once configured, users can test the integration by running the automation functions and interacting with the workflow's global menu. The page provides step-by-step instructions and screenshots for a comprehensive guide on how to integrate Cerb with GitHub's API."
tags: ["workflows"]
---
- [Introduction](#introduction)
- [Configure the GitHub service](#configure-the-github-service)
- [Installation](#installation)
- [Configuration](#configuration)
- [Test the integration](#test-the-integration)
- [Next steps](#next-steps)

# Introduction

This workflow contains multiple automations for demonstrating integration between Cerb and GitHub's API.

 

# Configure the GitHub service

If you haven't already, follow [these instructions](/solutions/integrations/github/) to configure the GitHub service and add your first connected account.

# Installation

Click on **Search&nbsp;» Workflows&nbsp;» (+)&nbsp;» (empty)** and paste the following [KATA](/docs/kata/):

```
workflow:
  name: wgm.integrations.github
  version: 2026-05-25T20:00:00Z
  description: Integrate Cerb with Github
  website: https://cerb.ai/resources/workflows/
  requirements:
    cerb_version: >=11.0 <12.1
    cerb_plugins: cerberusweb.core, 
  config:
    chooser/account:
      label: Connected Account:
      record_type: connected_account
records:
  automation/getrepo:
    fields:
      name: wgm.github.getrepos
      extension_id: cerb.trigger.automation.function
      description: Get a list of user repositories
      script@raw:
        start:
          set:
            config@json: {{cerb_workflow_config('wgm.integrations.github')|json_encode}}
          http.request/getrepo:
            output: http_response
            inputs:
              method: GET
              url: https://api.github.com/user/repos
              headers:
                Content-Type: application/json
                User-Agent: Cerb
              authentication: cerb:connected_account:{{config.account}}
            on_success:
              set:
                response_json@json: {{http_response.body}}
                http_response@json: null
              return:
                repositories@key: response_json
      policy_kata@raw:
        commands:
          http.request:
            deny/method@bool: {{inputs.method not in ['GET']}}
            deny/url@bool: {{inputs.url is not prefixed ('https://api.github.com')}}
            allow@bool: yes
  automation/postissue:
    fields:
      name: wgm.github.issue.create
      extension_id: cerb.trigger.automation.function
      description: Create a GitHub issue
      script@raw:
        inputs:
          text/repo:
            type: freeform
            required@bool: yes
          text/body:
            type: freeform
            required@bool: no
          text/title:
            type: freeform
            required@bool: yes
          
        start:
          set:
            config@json: {{cerb_workflow_config('wgm.integrations.github')|json_encode}}
          http.request/createissue:
            output: http_response
            inputs:
              method: POST
              url: https://api.github.com/repos/{{inputs.repo}}/issues
              headers:
                Content-Type: application/json
                User-Agent: Cerb
              body:
                title: {{inputs.title}}
                body: {{inputs.body}}
              authentication: cerb:connected_account:{{config.account}}
            on_success:
              set:
                response_body@json: {{http_response.body}}
              return:
                status_code: {{http_response.status_code}}
                url: {{response_body.html_url}}
            on_error:
          
      policy_kata@raw:
        commands:
          http.request:
            deny/method@bool: {{inputs.method not in ['POST']}}
            deny/url@bool: {{inputs.url is not prefixed ('https://api.github.com/repos')}}
            allow@bool: yes
          
  automation/issueinteraction:
    fields:
      name: wgm.github.issue.interaction
      extension_id: cerb.trigger.interaction.worker
      description: worker interaction for creating a new GitHub issue
      script@raw:
        start:
         await:
           form:
             title: Create GitHub Issue
             elements:
               text/prompt_repo:
                 label: Repository
                 required@bool: yes
                 type: freeform
                 placeholder: (example-repo/example-project)
               text/prompt_title:
                label: Issue Title
                required@bool: yes
                type: freeform
              textarea/prompt_body:
                label: Issue content
        
         function/create:
           uri: cerb:automation:wgm.github.issue.create
           inputs:
             repo: {{prompt_repo}}
             body: {{prompt_body}}
             title: {{prompt_title}}
           output: results
           
         outcome/success:
           if@bool: {{201 == results.status_code}}
           then:
             await:
               form:
                 title: Success!
                 elements:
                   say/success:
                     content@text:
                       Issue created! Issue URL is {{results.url}}.
                  submit:
                    buttons:
                      continue/yes:
                        label: Exit
                        icon: circle-ok
                        icon_at: start
                        value: yes
                      reset:
                        label: Create Another Issue
                        icon: refresh
                        icon_at: start
                      
                   
             
      policy_kata@raw:
        commands:
         function:
           deny/uri@bool: {{uri != 'cerb:automation:wgm.github.issue.create'}}
           allow@bool: yes
          
  toolbar_section/createissue:
    fields:
      name: Github Issue
      toolbar_name: global.menu
      priority@int: 50
      is_disabled: 0
      toolbar_kata@raw:
        interaction/createissue:
          label: Create a Github Issue
          uri: cerb:automation:wgm.github.issue.interaction
```

# Configuration

You'll be prompted to select the GitHub connected account you created earlier.

 

You can update the configuration at any time by opening the workflow profile and clicking "Edit Configuration".

 

# Test the integration

The workflow adds two automation functions and a worker interaction. Click on `wgm.github.getrepos` to bring up its card and click edit. From there you can click the play button on the simulator and you should get a response with all the repositories linked to your GitHub account.

 

The workflow also added a "Create a GitHub Issue" interaction to the global menu. You can access it from the Cerb icon in the bottom right corner of any page.

 

Enter the repository address, issue title, and body and click continue. You will be presented with a success message and a link to your new issue.

 

# Next steps

At this point you can modify the automations to meet your needs or create new ones. You can use the responses from GitHub's API in your automations.

