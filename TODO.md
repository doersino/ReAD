# Feature ideas

Roughly in order of desirability times "doability".

- [ ] Update readme with new screenshots, info and link to this document.
- [ ] Stats: current streak (what if current = longest?).
- [ ] Stats: weekdays vs. weekend.
- [ ] Stats: way of visualizing (average) time between adding and reading/archiving articles.
- [ ] Stats: "Custom" option in dropdown menu that, when selected, replaces dropdown with a text input allowing the user to select a time range (backend code for human-readable time to timestamp parsing already exists).
- [ ] Improve action buttons, possibly allowing to remove duplicate markup for mobile and desktop.
- [ ] More powerful search: with `AND`, `OR`, `NOT` operators, switch default meaning of space character from `AND` to `OR` and introduce grouping with `"`.
- [ ] Long shot: Since we're storing the raw HTML in the `read_sources` table, a way of displaying that (or computing some stats about it) would make sense. This would go hand in hand with article text extraction (sanitize!) and improved title detection. Would allow for all kinds of different font choices (more general: themes), might be beyond scope. Before taking this on (essentially adding another separate subsection to this thing, in addition to main view and Stats), major refactoring needed. Resources:
    - https://www.quora.com/Whats-the-best-method-to-extract-article-text-from-HTML-documents
    - http://tomazkovacic.com/blog/2011/03/11/list-of-resources-article-text-extraction-from-html-documents/
    - http://boilerpipe-web.appspot.com/
    - https://github.com/GravityLabs/goose
    - http://www.unixuser.org/~euske/python/webstemmer/#extract
    - http://www.keyvan.net/2010/08/php-readability/
- [ ] Possibly switch to prettier and more lightweight icon font, e.g. subset of
    - http://fontawesome.io/icons/
    - https://github.com/google/material-design-icons/releases/tag/1.0.0
