test the Visitors stats work as expected: 
start by erasing all event in the db make sure last 24 hours is selected
visit the fake website 8 time with random (uniq) user agents
check the the counter above the chart for New Visitors should be 8
change the user agent (a new random one)
visit the website 2 time with this user agent (the same two time)
check the counter above the chart for New Visitors shuld be 9 and for Returning Visitors should be 1
visit the website with curl as user agent and visit the website with googglebot as user agent
check the counters above the chart you should show 10 Total Visitors, 9 New Visitors, 1  Returning Visitors and 2 Bots
bot don't count as visitor
if you found any console errors or bug fix it until everthing work and give the good number of visitors: Total,New,Returning & Bots  
A new visitor is a visitor seen the first time ever and a returning visitor is a vistor who has already been seen before (any time)
only page view type of event are counting for visitors



# Playwright Test Script Instructions for Visitor Statistics

## Objective
Create a Playwright test script to verify that visitor statistics are tracked and displayed correctly.Run the test with inline flag for reports.

## Setup Requirements
1. **Clear the database**: Start by deleting ALL events from the database
2. **Set time filter**: Ensure "Last 24 hours" is selected as the active time range

## Test Sequence

### Phase 1: Test New Visitors
1. Visit the fake website **8 times**, each with a **different, randomly generated user agent**
   - Important: Each user agent must be unique
2. **Verify**: The "New Visitors" counter above the chart should display **8**

### Phase 2: Test Returning Visitors
1. Generate a **new random user agent** (different from the 8 used previously)
2. Visit the website **2 times** using this same user agent
3. **Verify**: 
   - "New Visitors" counter should display **9** (8 + 1 new)
   - "Returning Visitors" counter should display **1** (the visitor who came twice)

### Phase 3: Test Bot Detection
1. Visit the website using `curl` as the user agent
2. Visit the website using `googlebot` as the user agent
3. **Verify all counters**:
   - Total Visitors: **10**
   - New Visitors: **9** 
   - Returning Visitors: **1**
   - Bots: **2**

## Important Rules
- **Bot behavior**: Bots do NOT count as visitors (they only appear in the "Bots" counter)
- **New visitor definition**: A visitor using a user agent that has NEVER been seen before in the database
- **Returning visitor definition**: A visitor using a user agent that has been seen at least once before (at any point in time)
- **Event filtering**: Only "page view" type events should be counted for visitor statistics

## Error Handling
- Check for any console errors during the test
- If bugs are found or numbers don't match expectations, debug and fix the issues
- Continue testing until all counters show the correct values

## Expected Final State
After all test phases:
- Total Visitors: 10
- New Visitors: 9
- Returning Visitors: 1
- Bots: 2

Ultrathink !