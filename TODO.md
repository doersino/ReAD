# Feature ideas

Roughly in order of desirability times "doability". (Don't hold your breath – I haven't made any significant changes to ReAD in years, so I'm unlikely to one day wake up and start knocking these down from top to bottom.)

- [ ] Text extraction: Switch to https://github.com/scotteh/php-goose or (better, but Node) https://github.com/luin/readability.
- [ ] Text extraction: When getting the source and text for multi-page articles (from select websites, e.g. Ars Technica), fetch all pages, extract text individually and concat for an accuarate ERT.
- [ ] View: Add way of changing extracted text (and title?). Add "modified" column to table.
- [ ] View: Add some font settings?
- [ ] View & Stats: For word count/ERT: In some places, also equivalent number of pages in a book. Might be more impressive/imaginabe if the user's been reading a lot lately.
- [ ] Stats: (# of) days on which inbox zero: articles read until day - total number of articles added until that day, also: How many books worth of text per year?
- [ ] Stats: Number of archived artices per starred article per month.
- [ ] Stats: Set bounds for punchcard graph.
- [ ] Stats: Change added vs. archived graph to unread vs. archived graph (and switch vertical order), remove stand-alone unread graph in favor of this.
- [ ] Stats: Merge "articles per day" and "ERT per day" graphs.
- [ ] Stats: Weekdays vs. weekend.
- [ ] Stats: Switching from `period=year` to `period=month` currently always yields the last month of the year, even when it's in the future. Switch to current month instead.
- [ ] Stats: When going from a search results page to the stats page, only show stats for that search.
- [ ] Stats: In "Top 10 longest articles", when an article has no title, the gray "no title found" placeholder is not shown. Fix that!
- [ ] Stats: See `TODO` comments under `// top 10 longest articles` in `src/stats.php`.
- [ ] Stats: Make some graphs adjust to the time span – weekly, but monthly if span larger than a year or something.
- [ ] Stats: Generally do more data processing in SQL and less in PHP.
- [ ] Option for sorting unread articles by length or some interestingness measure (based on comparing titles with titles of starred articles).
- [ ] Stats: "Custom" option in dropdown menu that, when selected, replaces dropdown with a text input allowing the user to select a time range (backend code for human-readable time to timestamp parsing already exists).
- [ ] More powerful search (possibly full-text): with `AND`, `OR`, `NOT` operators, switch default meaning of space character from `AND` to `OR` and introduce grouping with `"`, as well as "host:" prefix. Maybe move search-related code to `Search.class.php`.
- [ ] Defer article source grabbing somehow. Introduce "fetched?" column in databse, set it to false initially when adding and don't yet fetch, then fetch via a cronjob or a daemon.
- [ ] Change auth such that a session expires after 30 days but is renewed on every page load, as opposed to the current system where a session is long but expires after a set amount of time, no matter how much ReAD has been used lately.
