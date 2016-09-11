# ReAD

A simple, responsive web app enabling you to

1. keep a **reading list** (of links to articles you're planning to read),
2. maintain a **searchable archive** of articles you've read, along with a list of favorites,
3. and **analyze when, what, and how much** you've read in a given time period.

Regarding the name: Originally, ReAD was supposed to be called "RAD", but since that'd be too silly, I snuck an "e" in there. It also makes for an excellent and only very hubristic [NeXT](https://en.wikipedia.org/wiki/NeXT) reference. Note that I'm the only known active (but very much so) user of this thing, so improvements and bug fixes will be implemented whenever I find time and motivation. For a rough roadmap, see [TODO.md](https://github.com/doersino/ReAD/blob/master/TODO.md).


## Screenshots

![desktop](https://github.com/doersino/ReAD/raw/master/screenshot-desktop.png)

![mobile](https://github.com/doersino/ReAD/raw/master/screenshots-mobile.png)

![stats](https://github.com/doersino/ReAD/raw/master/screenshots-stats.png)

<!-- As of September 9, 2016. -->


## Setup

1. Clone this repository.
2. Import `import.sql` into some MySQL database.
3. Copy `Config.class.php.example` to `Config.class.php` and enter your MySQL info.
4. Also take a look at the other options in there: e.g. after a few months, setting `ARTICLES_PER_TIME_GRAPH_STEP_SIZE = "weeks";` might be more interesting. Note that this graph, as well as the stats page, won't become very useful until you've been using ReAD for a while.
5. Optionally, add `<your base URL>/index.php?state=archived&s=<search query placeholder>` as a new search engine in your browser (you can substitute `archived` with `unread` or `starred`, depending on your primary use case).


## License (MIT)

The following license does not apply to files in `lib/`.

```
The MIT License (MIT)

Copyright (c) 2014 Noah Doersing

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in
all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
```
