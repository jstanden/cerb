---
id: "docs-automations-triggers-llm-tool"
title: "llm.tool"
url: "https://cerb.ai/docs/automations/triggers/llm.tool/"
summary: "This page provides an overview of the **llm.tool** feature in Cerb, which allows for the creation of reusable shared functions that can be triggered by AI agents using large language models. It details the structure of inputs and outputs for these functions, explaining that the automation dictionary begins with custom input values from the caller. The page also describes how the function returns key/value pairs to the caller, with the possibility of nesting keys to return dictionaries."
tags: ["docs", "docs-automations"]
---
**llm.tool** [automations](/docs/automations/) are reusable shared tools triggered by the [llm.agent:](/docs/automations/commands/llm.agent/) command using large language models.

The description and inputs are used by the AI agent to understand how to use the tool.

# Inputs

The automation [dictionary](/docs/automations/#dictionaries) starts with the following values:

| Key | Type | Notes |
| --- | --- | --- |
| `inputs` | dictionary | [Custom input](/docs/automations/#inputs) values from the AI agent |

# Outputs

## return:

When the tool concludes in the `return` state, it returns a `content` key to the AI agent.

```
return:
  content@text:
    This is the result of the tool.
```

# Examples

## Search Cerb documentation

- [automation](#)
- [policy](#)
- [inputs](#)
- [output](#)

- 
```
inputs:
  text/query:
    type: freeform
    description: The semantic search query (e.g. "How much does Cerb cost?")
    required@bool: yes

start:
  http.request/search:
    output: http_response
    inputs:
      method: POST
      url: https://api.cerb.cloud/docs/search
      headers:
        Content-Type: application/json
      body:
        query: {{inputs.query}}
        limit: 10
    on_error:
    on_success:
      set:
        response_json@json: {{http_response.body}}
        http_response@json: null
      return:
        content@text:
          <search_results>
          {% for result in response_json.results %}
          ----------------------------------------------------------------
          ID: {{result.id}}
          URL: {{result.url}}
          TITLE: {{result.title}}
          
          {{result.summary}}
          
          {% endfor %}
          </search_results>
```
- 
```
commands:
  http.request:
    deny/url@bool: {{inputs.url is not prefixed ('https://api.cerb.cloud/docs/')}}
    allow@bool: yes
```
- 
```
inputs:
  query: What is Cerb?
```
- 
```
__exit: return
__return:
  content@text:
    <search_results>
    ----------------------------------------------------------------
    ID: docs-setup-team-groups
    URL: https://cerb.ai/docs/setup/team/groups/
    TITLE: Groups

    This page provides information on configuring groups within Cerb. It likely includes instructions or options for setting up and managing groups, which are essential for organizing users and permissions in the Cerb platform.

    ----------------------------------------------------------------
    ID: docs-home
    URL: https://cerb.ai/docs/home/
    TITLE: Documentation

    This webpage serves as a comprehensive documentation hub for Cerb, covering various guides and references essential for different user roles. It includes an introduction and getting started section, an admin guide with topics like installation, security, and backups, and a worker guide focusing on the user interface. The developer guide provides insights into dictionaries, scripting, and plugins. The reference section is extensive, detailing features such as activity logs, automations, calendars, dashboards, notifications, and workflows, among others. Additionally, the API section offers information on authentication, requests, responses, and endpoints. The meta section provides release history, philosophy, and credits, making this a complete resource for understanding and utilizing Cerb effectively.

    ----------------------------------------------------------------
    ID: docs-setup-team-roles
    URL: https://cerb.ai/docs/setup/team/roles/
    TITLE: Roles

    This page provides information on configuring roles within Cerb. It likely includes details on how to set up and manage roles, assign permissions, and customize access levels for different users or groups within the platform. This functionality is essential for maintaining security and ensuring that users have the appropriate access to features and data based on their responsibilities.

    ----------------------------------------------------------------
    ID: help
    URL: https://cerb.ai/help/
    TITLE: Help

    This page provides contact information and resources for Cerb, including an email address for general inquiries, links to documentation, discussion forums, an issue tracker, and a newsletter archive. It also includes details about automated demos, license updates, and renewals. For sales inquiries, a phone number is provided. Additionally, the mailing address for Webgroup Media, LLC, the company behind Cerb, is listed.

    ----------------------------------------------------------------
    ID: docs-workers
    URL: https://cerb.ai/docs/workers/
    TITLE: Workers

    This page describes the concept of "workers" in Cerb, who are the individuals representing a team to the outside world. Workers include full-time and part-time staff, interns, volunteers, managers, executives, investors, and partners. They access Cerb through standard web browsers on various devices such as desktop computers, laptops, tablets, or smartphones, and can be logged in from multiple devices simultaneously. This setup provides flexibility for the team to work from virtually any location.

    ----------------------------------------------------------------
    ID: docs-contacts
    URL: https://cerb.ai/docs/contacts/
    TITLE: Contacts

    This page provides an overview of the concept of "Contacts" within Cerb, describing them as the clients, customers, partners, and other entities that workers engage with. It likely outlines how contacts are managed and utilized within the Cerb platform to facilitate interactions and relationships.

    ----------------------------------------------------------------
    ID: signup
    URL: https://cerb.ai/signup/
    TITLE: Start using Cerb for free

    This page offers a free trial of Cerb, allowing potential users to sign up with their work email and organization details. It provides options for different types of organizations, including businesses, government agencies, academic institutions, non-profits, and open source projects. Users can choose between two deployment options: Cerb Cloud, a fully managed subscription service, or Self-Hosted, which is deployed on the user's own hardware and network. The page also offers an option to subscribe to an email newsletter for tips on using Cerb. It emphasizes privacy, stating that user information will not be shared with third parties without consent, except as required by law or necessary for service provision.

    ----------------------------------------------------------------
    ID: docs-credits
    URL: https://cerb.ai/docs/credits/
    TITLE: Credits

    This page provides credits for the development and contributions to Cerb, highlighting key individuals such as Jeff Standen and Dan Hildebrandt, who have played significant roles in its development. It outlines the platform and infrastructure used, including Devblocks, HTML5, PHP, MySQL, Amazon Web Services, Docker, and GitHub. The page also lists various libraries and tools integrated into Cerb, such as Ace, C3.js, jQuery, PHPUnit, and Swift Mailer, among others, which support functionalities like code editing, charting, and secure communications. Additionally, it mentions the licenses and references related to the project.

    ----------------------------------------------------------------
    ID: docs-intro
    URL: https://cerb.ai/docs/intro/
    TITLE: Introduction

    This page provides an overview of Cerb, a customizable web-based platform designed for enterprise communication and process automation. Cerb has evolved over 24 years, integrating with API-based services to automate digital workflows using its KATA language and browser-based tools. It allows teams to create personalized workspaces with customizable widgets and manage various tasks through custom records and fields. Common use cases include transforming standard email systems into high-volume team-based webmail with automated features and integrating with large language models for customer support. Cerb can be deployed on personal hardware, Docker, or as a managed service in Cerb Cloud, with its source code available on GitHub. It is free for single-seat use, ensuring users can maintain their data without ongoing costs. The page also directs users to guides for administrators, workers, and developers.

    ----------------------------------------------------------------
    ID: docs-plugins-cerberusweb-core
    URL: https://cerb.ai/docs/plugins/cerberusweb.core/
    TITLE: Plugin: Cerb Core

    This page provides a comprehensive overview of the Cerb Core plugin, detailing its core functionalities and various extensions. It includes a wide range of extensions such as Bot Actions, Bot Events, Calendar Datasources, Connected Service Providers, Controllers, Custom Field Types, Event Listeners, HTTP Request Listeners, Mail Transport Types, Page Sections, Page Types, Profile Tab Types, Profile Widget Types, Record Types, Scheduled Jobs, Search Schemas, Storage Schemas, Workspace Page Types, Workspace Tab Types, Workspace Widget Datasources, and Workspace Widget Types. Each extension is listed with its specific functionalities and identifiers, showcasing the extensive capabilities and integrations available within the Cerb Core framework. This detailed enumeration highlights the plugin's versatility in managing and automating various tasks and processes within the Cerb environment.

    </search_results>
```

