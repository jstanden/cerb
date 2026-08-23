---
id: "guides-mail-test-deliverability"
title: "Test mail deliverability"
url: "https://cerb.ai/guides/mail/test-deliverability/"
summary: "This page provides instructions on how to test mail deliverability in Cerb by sending a test message to the mail-tester.com service, which verifies proper delivery and generates a deliverability score based on factors such as SPF, DKIM, DMARC, reverse DNS (PTR), etc. To do this, follow these steps: open a web browser to mail-tester.com, copy the destination email address, then in Cerb navigate to Search >> Tickets, select a group and bucket, paste the destination email address into the To field, set the subject and message content, select Open as the status of the conversation, and click Send Message. After sending the test message, switch back to mail-tester.com and check your score, which will indicate whether everything went well or if corrections are needed."
tags: ["guides"]
---
We're going to send a test message from Cerb to the **mail-tester.com** service. Not only will this verify that your message was delivered properly, but it will also generate a deliverability score by testing your mail server configuration: SPF, DKIM, DMARC, reverse DNS (PTR), etc.

1. First, open a web browser to http://mail-tester.com.

2. Copy the destination email address that shows up on that page.

3. Open Cerb in another browser window or tab.

4. Navigate to **Search**&nbsp;» **Tickets**.

5. Click the **(+)** icon in the gray bar above the worklist.

6. Select a group and bucket to send **From:**.

7. Paste the destination email address from **@mail-tester.com** in the **To:** field.

8. In **Subject:**, type: `This is a test of outgoing mail from Cerb`

9. On the first line of the message, type: `This is an outgoing message.`

10. In **Properties**, below the message text, select **Open** for the status of the conversation.

11. Scroll down to the bottom of the popup window and click the **Send Message** button.

12. Switch back to the browser at **mail-tester.com** and click the blue **Then Check Your Score** button.

13. If everything goes well, you should see something like this:

If you received a less than perfect score, scroll down to see the details. You can make corrections, send another test message to the same email address, and then reload the results page.

