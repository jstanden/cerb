---
id: "solutions-integrations-aws"
title: "Amazon Web Services (AWS)"
url: "https://cerb.ai/solutions/integrations/aws/"
summary: "This page provides a comprehensive guide on integrating Cerb with Amazon Web Services (AWS). It covers the steps to log into AWS, create a policy, set up a new user, and generate programmatic credentials necessary for the integration. The guide then explains how to create the AWS service within Cerb and utilize the connected account for automations, allowing users to access AWS APIs directly from Cerb. Additionally, it references related resources for further enhancing Cerb's capabilities with AWS services like Amazon Bedrock, AWS Lambda, and Amazon Polly."
tags: ["solutions"]
---
- [Introduction](#introduction)
- [Log in to Amazon Web Services](#log-in-to-amazon-web-services)
  - [Create a policy](#create-a-policy)
  - [Create a new user](#create-a-new-user)
  - [Generate programmatic credentials](#generate-programmatic-credentials)

- [Create the AWS service in Cerb](#create-the-aws-service-in-cerb)
- [Use the connected account in automations](#use-the-connected-account-in-automations)
- [Related resources](#related-resources)

# Introduction

In this guide we'll walk through the process of linking Cerb to Amazon Web Services (AWS). You'll be able to use any AWS API from automations in Cerb.

# Log in to Amazon Web Services

We'll start by logging in to the AWS Management Console.

If you don't have an AWS account, you can sign up for free at: https://aws.amazon.com

We're going to create the new user account for our Cerb bot to use.

If you haven't already selected the **IAM** service, do so now.

### Create a policy

Select **Policies** in the left navigation.

Click the **Create policy** button at the top.

Select the **JSON** tab.

Paste the following policy:

```
{
  "Version": "2012-10-17",
  "Statement": [
    {
      "Sid": "CerbIam",
      "Effect": "Allow",
      "Action": "iam:GetUser",
      "Resource": "arn:aws:iam::*:user/${aws:username}"
    }
  ]
}
```

You can add new permissions here depending on the services your automation needs to access. This is covered in those specific guides – see the [Amazon Bedrock](/solutions/integrations/bedrock/) guide for a worked example.

Click the **Next** button in the lower right.

Use the policy name: **CerbAutomationsPolicy**

Click the **Create Policy** button in the lower right.

### Create a new user

Select **Users** in the left navigation.

Click the orange **Create user** button in the top right of the page.

Type `CerbAutomations` in **User name**.

 

Click the orange **Next** button in the bottom right.

At the top, select **Attach existing policies directly**.

Select **CerbAutomationsPolicy**.

Click the **Next** button in the lower right.

Click the **Create user** button in the lower right.

### Generate programmatic credentials

Click on **CerbAutomations** in the users list.

Select the **Security credentials** tab.

In the **Access Keys** section, click the **Create access key** button near the middle of the page.

Select **Other** and click the **Next** button in the lower right.

Click the **Create access key** button in the lower right.

Click the **Download .csv** button to save a copy of your new credentials. You'll need these in a moment when adding a new connected account in Cerb.

Click the **Done** button.

That's everything we need to do in AWS.

# Create the AWS service in Cerb

1. Navigate to **Search&nbsp;» Connected Services**.

2. Click the **(+)** icon in the top right of the list.

3. Select **Amazon Web Services**.

4. Enter your Access Key and Secret Key from AWS.

5. Click the **Create** button.

# Use the connected account in automations

You can use the connected account you just created to access AWS APIs from [automations](/docs/automations/) in Cerb. This is typically accomplished using the [http.request](/docs/automations/commands/http.request/) command from an automation and referencing this connected account in the **authentication:** option.

# Related resources

- Guide: [Run large language models on Amazon Bedrock](/solutions/integrations/bedrock/)
- Workflow: [Generate Profile Images (Amazon Bedrock)](/workflows/cerb.integrations.aws_bedrock.profile_images/)
- Guide: [Run AWS Lambda functions from a Cerb bot](/guides/integrations/aws/lambda/)
- Guide: [Give Cerb bots the power of speech with Amazon Polly](/guides/integrations/aws/polly-speech/)

