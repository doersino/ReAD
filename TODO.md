# Feature ideas

For each category, roughly in order of desirability times "doability". (Don't hold your breath – I haven't made any significant changes to ReAD in years, so I'm unlikely to one day wake up and start knocking these down from top to bottom.)


## Search

- [ ] More powerful search (possibly full-text): with `AND`, `OR`, `NOT` operators, switch default meaning of space character from `AND` to `OR` and introduce grouping with `"`, as well as "host:" prefix. Maybe move search-related code to `Search.class.php`.
- [ ] Investigate why searching "&" doesn't highlight the "&" in some titles and quotes (might also need to highlight `htmlspecialchars`'d variant of search *term*, but that's too hacky?).


## View

- [ ] Some indication (likely in the expand button) whether there are any non-text quotes.
- [ ] Add way of changing extracted text (and title?). Add "modified" column to table.
- [ ] Also show in-article quotes in quote editor. When deleting those, also delete them in the text.


## Stats

- [ ] Number of days on which inbox zero: articles read until day - total number of articles added until that day, also: How many books worth of text per year?
- [ ] Number of archived artices per starred article ("starred ratio").
- [ ] Set bounds for punchcard graph.
- [ ] Merge "articles per day" and "ERT per day" graphs?
- [ ] Weekdays vs. weekend.
- [ ] Switching from `period=year` to `period=month` currently always yields the last month of the year, even when it's in the future. Switch to current month instead. Same for year to decade.
- [ ] When going from a search results page to the stats page, only show stats for that search.
- [ ] See `TODO` comments under `// top 10 longest articles` in `src/stats.php`.
- [ ] Make some graphs adjust to the time span – weekly, but monthly if span larger than a year or something.
- [ ] Generally do more data processing in SQL and less in PHP.
- [ ] Option for sorting unread articles by length or some interestingness measure (based on comparing titles with titles of starred articles).
- [ ] "Custom" option in dropdown menu that, when selected, replaces dropdown with a text input allowing the user to select a time range (backend code for human-readable time to timestamp parsing already exists).


## Text extraction

- [ ] Switch to https://github.com/scotteh/php-goose or (better, but Node) https://github.com/luin/readability.
- [ ] When getting the source and text for multi-page articles (from select websites, e.g. Ars Technica), fetch all pages, extract text individually and concat for an accuarate ERT.


## Sundry

- [ ] Use nullable timestamps for "archived" and "starred" instead of boolean flags, see also [here](https://news.ycombinator.com/item?id=26922759).
- [ ] Remove code duplication: List of articles ("normal"/search results, reading suggestion, longest articles in stats), `getArticles`/`getSearchResults` SQL queries (and similar ones in stats computation).
- [ ] Defer article source grabbing somehow. Introduce "fetched?" column in databse, set it to false initially when adding and don't yet fetch, then fetch via a cronjob or a daemon.
- [ ] Change auth such that a session expires after 30 days but is renewed on every page load, as opposed to the current system where a session is long but expires after a set amount of time, no matter how much ReAD has been used lately.
- [ ] For word count/ERT: Maybe give equivalent number of pages in a book. Might be more impressive/imaginable if the user's been reading a lot lately.
- [ ] When adding an article in unread mode, press alt (or similar) to immediately archive it. The search/add bar icon should change accordingly.
