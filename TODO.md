# Feature ideas

Roughly in order of desirability times "doability".

- [ ] Update readme with new screenshots, info and link to this document.
- [ ] Enable browser to cache `style.php`. Straightforward way: any call of `index.php` compiles it to `style.css`, provided it changed recently. See: http://stackoverflow.com/a/7476264
- [ ] Stats: "Custom" option in dropdown menu that, when selected, replaces dropdown with a text input allowing the user to select a time range (backend code for human-readable time to timestamp parsing already exists).
- [ ] Stats: weekdays vs. weekend.
- [ ] Stats: way of visualizing (average) time between adding and reading/archiving articles.
- [ ] More powerful search: with `AND`, `OR`, `NOT` operators, switch default meaning of space character from `AND` to `OR` and introduce grouping with `"`.
- [ ] Long shot: Since we're storing the raw HTML in the `read_sources` table, a way of displaying that would make sense. This would go hand in hand with article text extraction (sanitize!) and improved title detection. Would allow for all kinds of different font choices (more general: themes), might be beyond scope. Before taking this on (essentially adding another separate subsection to this thing, in addition to main view and Stats), major refactoring needed.
- [ ] Possibly switch to prettier and more lightweight icon font, e.g. subset of
    - http://fontawesome.io/icons/
    - https://github.com/google/material-design-icons/releases/tag/1.0.0
