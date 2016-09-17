# Feature ideas

Roughly in order of desirability times "doability".

- [ ] Setting for light/dark theme: "desktop", "mobile", or "both"
- [ ] Stats: remove output of time taken (or at least add an option).
- [ ] Stats: current streak (and: what if current = longest?).
- [ ] Stats: weekdays vs. weekend.
- [ ] Stats: added per day minus read per day, red/green below/above zero line.
- [ ] Stats: way of visualizing (average) time between adding and reading/archiving articles.
- [ ] Stats: "Custom" option in dropdown menu that, when selected, replaces dropdown with a text input allowing the user to select a time range (backend code for human-readable time to timestamp parsing already exists).
- [ ] Improve action buttons, possibly allowing to remove duplicate markup for mobile and desktop.
- [ ] More powerful search: with `AND`, `OR`, `NOT` operators, switch default meaning of space character from `AND` to `OR` and introduce grouping with `"`, as well as host: prefix.
- [ ] Long shot: Since we're storing the raw HTML in the `read_sources` table, a way of displaying that (or computing some stats/info about it, e.g. language distribution per week/month/year in stats, estimated reading time for each article in the unread/archived/starred views => "approx. time spent reading" for each day/week/month/year in stats) would make sense. This would go hand in hand with article text extraction (sanitize!) and improved title detection. Would allow for all kinds of different font choices (more general: themes), might be beyond scope. Before taking this on (essentially adding another separate subsection to this thing, in addition to main view and Stats), some refactoring/cleanup needed. Resources:
    - Need to make sure internal files can't be accessed: http://stackoverflow.com/questions/25090563/php-determine-if-a-url-is-an-internal-or-external-url
    - Use `escapeshellarg()` and `shell_exec()` (equivalent to backtick operator) if using non-PHP library.
    - http://tomazkovacic.com/blog/2011/03/11/list-of-resources-article-text-extraction-from-html-documents/
    - http://boilerpipe-web.appspot.com/
    - https://github.com/kohlschutter/boilerpipe
    - http://www.unixuser.org/~euske/python/webstemmer/#extract
    - http://www.keyvan.net/2010/08/php-readability/
    - https://github.com/dotpack/php-boiler-pipe
