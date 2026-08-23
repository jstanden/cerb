---
id: "solutions-automations-simulate-errors"
title: "Simulate errors"
url: "https://cerb.ai/solutions/automations/simulate-errors/"
summary: "This page explains how to simulate random errors or successes for HTTP requests in Cerb's automation editor, using the `on_simulate` command. This allows you to test error handling and functionality without executing the actual request, making it a useful tool for testing automation scripts in Cerb's simulator. The `simulate.success:` and `simulate.error:` commands can be used to mock successful or failed responses, triggering corresponding events in your script, such as `on_success` or `on_error`."
tags: ["solutions", "solutions-automations"]
---
## Simulate random errors

The `on_simulate:` command will only be used by the automation editor's simulator. It can be useful when you want to test a command without actually executing it. You can further use `simulate_success:` and `simulate_error:` to mock their respective outcomes.

In this example, we are randomly simulating both successful and failed HTTP requests.

- [automation](#)
- [policy](#)

- 
```
start:
  http.request/get:
    output: http_response
    inputs:
      method: GET
      url: https://cerb.ai/
    on_simulate:
      decision:
        outcome/error500:
          if@bool: {{0 == random(1)}}
          then:
            simulate.error:
              status_code@int: 500
              body: Error!
        outcome/ok200:
          then:
            simulate.success:
              status_code@int: 200
              body: Success!
    on_error:
      log.error: This is an error message
    on_success:
```

The `random(1)` function returns a value of 0 or 1 (50% chance of either outcome).

`simulate.success:` mocks the http.request: response and triggers the `on_success:` event.

`simulate.error:` triggers the `on_error:` event. This way you can test failure modes on command and make sure your error handling works as intended.

- 
```
commands:
  http.request:
    deny/method@bool: {{inputs.method not in ['GET']}}
    allow@bool: yes
```

