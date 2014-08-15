# ReAD

A simple, responsive web app that enables you to save links to articles you're going to read, as well as links to articles you've read in the past. Comes with a basic search function and fancy graphs.

![desktop](https://github.com/doersino/ReAD/raw/master/screenshot-720px.png)
![mobile](https://github.com/doersino/ReAD/raw/master/screenshot-mobile-720px.png)

## Installation
1. Import `import.sql` into some MySQL database
2. Open `lib/meekrodb.2.3.class.php` and enter your MySQL info
3. Optionally, take a look at `Config.class.php`
4. Optionally, add `<your base URL>/index.php?state=archived&s=<search query placeholder>` as a new search engine in your browser (you can substitute `archived` with `unread` or `starred`, depending on your primary use case)

## License (MIT)
(This license does not neccessarily apply to files in `lib/`.)

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
