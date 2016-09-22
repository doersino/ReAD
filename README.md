# ReAD

A simple, responsive web app enabling you to

1. keep a **reading list** (of links to articles you're planning to read),
2. maintain a **searchable archive** of articles you've read, along with a list of favorites,
3. and **analyze when, what, and how much** you've read in a given time period.


## Screenshots

![desktop](https://github.com/doersino/ReAD/raw/master/imgs/screenshot-desktop.png)

![mobile](https://github.com/doersino/ReAD/raw/master/imgs/screenshots-mobile.png)

![stats](https://github.com/doersino/ReAD/raw/master/imgs/screenshots-stats.png)

<!-- As of September 9, 2016. -->


## Setup

1. **Clone** this repository to a web server running PHP 5.4 or later and a recent version of MySQL/MariaDB.
2. **Import** `setup/import.sql` into some database.
3. Copy `setup/config.php.example` to `config.php` and **enter your database info**.

That's it, your ReAD install should now be accessible via any web browser! Consider the following optional steps as well:

* Take a look at the **other config options**: most notably, enter [your reading speed](http://www.readingsoft.com) for more accurate reading time estimates. Also, setting `ARTICLES_PER_TIME_GRAPH_STEP_SIZE = "weeks";` might be more interesting after a few months.
* If you want to keep things private (recommended), **password-protect** your ReAD install (e.g. [using `.htaccess`](http://stackoverflow.com/a/5229803)).
7. You could add `<your base URL>/index.php?state=archived&s=<search query placeholder>` as a new search engine in your browser (substitute `archived` with `unread` or `starred` depending on your primary use case).


## Notes

* *Regarding the name:* Originally, ReAD was supposed to be called "RAD", but since that'd be too silly, I snuck an "e" in there. It also makes for an excellent and only very hubristic [NeXT](https://en.wikipedia.org/wiki/NeXT) reference.
* The graph at the bottom of every page, as well as the statistics page, won't become very useful until you've been using ReAD for a while.
* Note that I'm the only known active (but very much so) user of this thing, so improvements and bug fixes will be implemented whenever I find time and motivation. For a rough roadmap, see [TODO.md](https://github.com/doersino/ReAD/blob/master/TODO.md).
* The [LICENSE](https://github.com/doersino/ReAD/blob/master/LICENSE) does **not** apply to files in the `deps/` directory: those are used with permission and come with their own licenses, which are usually noted at the the top of each file or in a central `LICENSE` file.
