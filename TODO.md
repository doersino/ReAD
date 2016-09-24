# Feature ideas

Roughly in order of desirability times "doability".

- [ ] Stats: don't show "per month" graphs when `period=month`.
- [ ] Stats: switching from `period=year` to `period=month` currently always yields the last month of the year, even when it's in the future. Switch to current month instead.
- [ ] Stats: Not just one-dataset graphs: e.g. could overlay articles per day and time spent per day.
- [ ] Stats: increase contrast of graphs.
- [ ] Stats: remove output of time taken (or at least add an option).
- [ ] Stats: weekdays vs. weekend.
- [ ] Stats: added per day minus read per day, red/green below/above zero line.
- [ ] Stats: way of visualizing (average) time between adding and reading/archiving articles, possibly pie graph with brackets?
- [ ] Stats: text language distribution per week/month/year.
- [ ] Setting for light/dark theme: "desktop", "mobile", or "both".
- [ ] Stats: "Custom" option in dropdown menu that, when selected, replaces dropdown with a text input allowing the user to select a time range (backend code for human-readable time to timestamp parsing already exists).
- [ ] When getting the source and text for multi-page articles (from select websites, e.g. Ars Technica), fetch all pages, extract text individually and concat for an accuarate ERT.
- [ ] Since we're storing the article text in the `read_text` table, a way of displaying it would make sense.
    - Link to that page from ERT display.
    - *Might* go hand in hand with improved title detection.
    - Would allow for all kinds of different font choices (more general: themes), might be beyond scope.
    - Need to make sure internal files can't be accessed: http://stackoverflow.com/questions/25090563/php-determine-if-a-url-is-an-internal-or-external-url
    - Before taking this on (essentially adding another separate subsection to this thing, in addition to main view and Stats), some refactoring/cleanup needed.
- [ ] More powerful search: with `AND`, `OR`, `NOT` operators, switch default meaning of space character from `AND` to `OR` and introduce grouping with `"`, as well as "host:" prefix. Maybe move search-related code to `Search.class.php`.
