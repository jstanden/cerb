---
id: "solutions-automations-calculate-week-range"
title: "Calculate week ranges"
url: "https://cerb.ai/solutions/automations/calculate-week-range/"
summary: "This page provides a solution for calculating the weekly date ranges for a full year using the `date_lerp()` function in Cerb. The script interpolates timestamps between two dates."
tags: ["solutions", "solutions-automations"]
---
You can use [`date_lerp()`](/docs/scripting/functions/#date_lerp) to calculate the date ranges for a full year. This interpolates the timestamps between two dates with the given unit and step.

- [automation](#)
- [output](#)

- 
```
start:
  return:
    output@list:
      {% for i,ts in date_lerp('Jan 1 2025 last Monday to Dec 31','week',step,53) %}
      Week {{i+1}}: {{ts|date('D Y-m-d')}} - {{ts|date_modify('this Sunday 23:59:59')|date('D Y-m-d')}}
      {% endfor %}
```
- 
```
__return:
  output:
  - 'Week 1: Mon 2024-12-30 - Sun 2025-01-05'
  - 'Week 2: Mon 2025-01-06 - Sun 2025-01-12'
  - 'Week 3: Mon 2025-01-13 - Sun 2025-01-19'
  - 'Week 4: Mon 2025-01-20 - Sun 2025-01-26'
  - 'Week 5: Mon 2025-01-27 - Sun 2025-02-02'
  - 'Week 6: Mon 2025-02-03 - Sun 2025-02-09'
  - 'Week 7: Mon 2025-02-10 - Sun 2025-02-16'
  - 'Week 8: Mon 2025-02-17 - Sun 2025-02-23'
  - 'Week 9: Mon 2025-02-24 - Sun 2025-03-02'
  - 'Week 10: Mon 2025-03-03 - Sun 2025-03-09'
  - 'Week 11: Mon 2025-03-10 - Sun 2025-03-16'
  - 'Week 12: Mon 2025-03-17 - Sun 2025-03-23'
  - 'Week 13: Mon 2025-03-24 - Sun 2025-03-30'
  - 'Week 14: Mon 2025-03-31 - Sun 2025-04-06'
  - 'Week 15: Mon 2025-04-07 - Sun 2025-04-13'
  - 'Week 16: Mon 2025-04-14 - Sun 2025-04-20'
  - 'Week 17: Mon 2025-04-21 - Sun 2025-04-27'
  - 'Week 18: Mon 2025-04-28 - Sun 2025-05-04'
  - 'Week 19: Mon 2025-05-05 - Sun 2025-05-11'
  - 'Week 20: Mon 2025-05-12 - Sun 2025-05-18'
  - 'Week 21: Mon 2025-05-19 - Sun 2025-05-25'
  - 'Week 22: Mon 2025-05-26 - Sun 2025-06-01'
  - 'Week 23: Mon 2025-06-02 - Sun 2025-06-08'
  - 'Week 24: Mon 2025-06-09 - Sun 2025-06-15'
  - 'Week 25: Mon 2025-06-16 - Sun 2025-06-22'
  - 'Week 26: Mon 2025-06-23 - Sun 2025-06-29'
  - 'Week 27: Mon 2025-06-30 - Sun 2025-07-06'
  - 'Week 28: Mon 2025-07-07 - Sun 2025-07-13'
  - 'Week 29: Mon 2025-07-14 - Sun 2025-07-20'
  - 'Week 30: Mon 2025-07-21 - Sun 2025-07-27'
  - 'Week 31: Mon 2025-07-28 - Sun 2025-08-03'
  - 'Week 32: Mon 2025-08-04 - Sun 2025-08-10'
  - 'Week 33: Mon 2025-08-11 - Sun 2025-08-17'
  - 'Week 34: Mon 2025-08-18 - Sun 2025-08-24'
  - 'Week 35: Mon 2025-08-25 - Sun 2025-08-31'
  - 'Week 36: Mon 2025-09-01 - Sun 2025-09-07'
  - 'Week 37: Mon 2025-09-08 - Sun 2025-09-14'
  - 'Week 38: Mon 2025-09-15 - Sun 2025-09-21'
  - 'Week 39: Mon 2025-09-22 - Sun 2025-09-28'
  - 'Week 40: Mon 2025-09-29 - Sun 2025-10-05'
  - 'Week 41: Mon 2025-10-06 - Sun 2025-10-12'
  - 'Week 42: Mon 2025-10-13 - Sun 2025-10-19'
  - 'Week 43: Mon 2025-10-20 - Sun 2025-10-26'
  - 'Week 44: Mon 2025-10-27 - Sun 2025-11-02'
  - 'Week 45: Mon 2025-11-03 - Sun 2025-11-09'
  - 'Week 46: Mon 2025-11-10 - Sun 2025-11-16'
  - 'Week 47: Mon 2025-11-17 - Sun 2025-11-23'
  - 'Week 48: Mon 2025-11-24 - Sun 2025-11-30'
  - 'Week 49: Mon 2025-12-01 - Sun 2025-12-07'
  - 'Week 50: Mon 2025-12-08 - Sun 2025-12-14'
  - 'Week 51: Mon 2025-12-15 - Sun 2025-12-21'
  - 'Week 52: Mon 2025-12-22 - Sun 2025-12-28'
  - 'Week 53: Mon 2025-12-29 - Sun 2026-01-04'
```

