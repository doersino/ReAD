# Feature ideas

Roughly in order of desirability times "doability".

- [ ] Text extraction: switch to https://github.com/scotteh/php-goose or (better, but Node) https://github.com/luin/readability.
- [ ] View: Show percentage read *and* time left in/near progress bar.
- [ ] View: Add way of changing extracted text (and title?). Add "modified" column to table.
- [ ] View: Reconsider what should go into the meta section ("Added about 1 month ago on Someday, August 19, 2016"). Add font settings.
- [ ] View & Stats: For word count/ERT: in some places, also equivalent number of pages in a book. Might be more impressive/imaginabe if the user's been reading a lot lately.
- [ ] Stats: Number of archived artices per starred article per month.
- [ ] Stats: Set bounds for punchcard graph.
- [ ] Stats: change added vs. archived graph to unread vs. archived graph (and switch vertical order), remove stand-alone unread graph in favor of this.
- [ ] Stats: Merge "articles per day" and "ERT per day" graphs.
- [ ] Stats: weekdays vs. weekend.
- [ ] Stats: way of visualizing (average) time between adding and reading/archiving articles, possibly pie graph with brackets?
- [ ] Stats: text language distribution per week/month/year.
- [ ] Stats: switching from `period=year` to `period=month` currently always yields the last month of the year, even when it's in the future. Switch to current month instead.
- [ ] Update readme with new features.
- [ ] See `TODO` comments under `// top 10 longest articles` in `src/stats.php`.
- [ ] Option for sorting unread articles by length or some interestingness measure (based on comparing titles with titles of starred articles).
- [ ] When getting the source and text for multi-page articles (from select websites, e.g. Ars Technica), fetch all pages, extract text individually and concat for an accuarate ERT.
- [ ] Stats: "Custom" option in dropdown menu that, when selected, replaces dropdown with a text input allowing the user to select a time range (backend code for human-readable time to timestamp parsing already exists).
- [ ] Create `Theme.class.php` based on themes at the top of `style.php` and also including fonts, possibly icon fonts and stats graph colors. Use it as a unified way of defining and using themes.
- [ ] More powerful search (possibly full-text): with `AND`, `OR`, `NOT` operators, switch default meaning of space character from `AND` to `OR` and introduce grouping with `"`, as well as "host:" prefix. Maybe move search-related code to `Search.class.php`.
