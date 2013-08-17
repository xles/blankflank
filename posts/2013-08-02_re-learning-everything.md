A few weeks ago I started coding on a project for a client.  While it is
true that I've been doing web development off and on for the past ten or
so years, there's one thing I've actively avoided;  _Javascript_.

To tell you the truth, most of my work has been backend code.  I've more
or less ignored the entire frontend side of programming, regardless of
environment.  The user interface has always been secondary to me.  That
changed a few years ago, when I was reading about _UI first software
development_.  

In particular it was these words:

> _"When writing end-user software, UI design should really come first.  
> To the end user, the UI __is__ the application."_  

This of course, made a whole lot of sense.  The user doesn't give half 
a rats arse how pristine the backend is, or how flexible and modular the 
code is.  If your UI is slow and ugly, your whole program is slow and 
ugly.

So what do you do when you need to make a snappy feeling web based 
application? _Javascript_.  As a long time defender of the plain HTML,
suddenly realising the need for Javascript is uncomfortable to say the
least.

In Javascript's defence though, Javascript has come a long way in ten 
years.  Javascript processing in browsers has increased by orders of
magnitude.  And with shiny frameworks such as [JQuery][1], it's almost
easy to use (if you don't need to do something advanced).

The project I'm currently working on however, required more than just
standard JQuery.  It called for a [<abbr title="Single-page application"
class="initialism">SPA</abbr> architecture][2], which unfortunately
means that it have to be built almost entirely with client side code, 
i.e. Javascript and CSS.  Fortunate for me there are a lot of good
frameworks on the market for client side development.  So far I've gone
for following:

* [Twitter Bootstrap][3], a CSS UI framework (the same one I'm using on
  this blog).
* [RequireJS][4], [<abbr title="Asynchronous module definition"
  class="initialism">AMD</abbr>][5] module loader.
* [KnockoutJS][6], data binding library.
* [Twitter Typeahead][7], an autocomplete library.
* [Mustache.js][8], a Javascript implementation of the [mustache][9]
  templating language.
* [DavisJS][10], a client side routing library.

Truth be told I'm feeling slightly overwhelmed.  While I've used 
Bootstrap and JQuery in the past, this is really the first proper client
side project I've worked on.  It's a learning experience, to say the 
least.  Learning to handle a half dozen new libraries at once is a bit
of a mess, but the documentation is good.  It's just a bit annoying 
having to spend ten times as much time reading documentation as I do
actual programming.

In the end I've gotten most of things to work, next up on the todo list
is to integrate Mustache and Knockout.  Hopefully this will make it 
possible for me to split my HTML out in multiple template files.  As it
is, everything is in a giant file, because I can't figure out how to
make Knockout load external template files...

Getting all of this to play together is a bit of a challenge, but who 
doesn't enjoy a good challenge?

[1]: http://jquery.com/
[2]: http://en.wikipedia.org/wiki/Single-page_application
[3]: http://getbootstrap.com/
[4]: http://requirejs.org/
[5]: http://en.wikipedia.org/wiki/Asynchronous_module_definition
[6]: http://knockoutjs.com/
[7]: http://twitter.github.io/typeahead.js/
[8]: https://github.com/janl/mustache.js
[9]: http://mustache.github.io/
[10]: http://davisjs.com/

---EOF---
{
    "title": "Re-learning everything.",
    "category": "Projects",
    "tags": [
        "JavaScript",
        "KnockoutJS",
        "RequireJS",
        "Mustache",
        "SPA"
    ],
    "pubdate": "2013-08-02",
    "slug": "re-learning-everything",
    "layout": "post"
}