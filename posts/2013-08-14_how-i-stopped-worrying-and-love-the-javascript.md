If you would've told me ten years ago that I'd be advocating the use of JavaScript for anything other than the teeny tiniest bit of flair, chances are I would've punched you right in the muzzle.

Then again, ten years ago, JavaScript was a Terrible language, absolutely terrible.  The current generation of JavaScript in use today is ECMAScript 5.1, which was released in 2011 (Internet Exploder excluded of course, they're still using ECMAScript 3).

While it's true that, in the beginning when JavaScript was first invented, it was designed as a shitty little scripting language (by the name of LiveScript) used to augment browser behaviour for Netscape users.  JavaScript started out being an interpreted scripting language, with slow and inefficient interpreters.  Today however, this is no longer the case, things have changed.

The JavaScript engine in [Google Chrome][1], the [V8 JavaScript Engine][2], is not an interpreter.  __V8 is a JavaScript *compiler*__ (actually a set of compilers, but that's not important right now).

Google's strategy for speeding up JavaScript was to compile it down to native machine code.  And for JavaScript that has been optimized for use with V8, execution is almost as fast as code written in a "real" language such as C.  So I'd call V8 a huge success, _and it's still getting better_. So the excuse of avoiding JavaScript and client side scripting because it's terribly slow and inefficient, is pretty much null and void at this point, it's a moot argument.

> What about JavaScript as a language?  Is it still a terrible experience to code in it?

Well, yes and no.  It has it's fair share of quirks, still, but it has evolved a lot since the '90s.  Short answer is, _it's gotten better_. And it's still getting better.  I'm told that [ECMAScript 6][3] is going to further improve the language's syntax, and add some features that are sought after.  One can only hope that the adoption of ECMAScript 6 will be sufficiently fast.

Then there's the whole JavaScript eco-system that has evolved in the past few years.  It's downright amazing how far it's gotten in a few short years.  We have front-end scaffolding utilities such as [Yeoman][4], task runners such as [Grunt][5], and package managers such as [Bower][6].  It's gotten to the point where the front-end tooling is matching that of any other area of development.  
We've had an increase in speed by several orders of magnitude (not even accounting for Mores law).  We have a good set of tools, with more and better tools on the way.  we have some amazing looking modern frameworks, such as [Meteor][7].  we have unit testing, we have virtually anything you can think of that you'd need to start developing a rich client side application today.

So go download [Yeoman][4] (requires [Node.js][8]) and start building your client side application today.  It comes bundled with [Grunt][5] and [Bower][6], so you can take a good look at the multitude of packages already available to you with `% bower search`.

Don't worry about whether or not JavaScript is fast enough, it is, and will only get faster.  Or that it's a language reserved for rank amateurs, it's not, take a look at [Gmail][9].

[1]: http://google.com/chrome
[2]: http://code.google.com/p/v8
[3]: http://ecma.org/harmony
[4]: http://yeoman.io
[5]: http://grunt.org
[6]: http://bower.org
[7]: http://meteor.org
[8]: http://nodejs.org
[9]: http://gmail.com/

---EOF---
{
    "title": "How I stopped worrying and love the JavaScript.",
    "category": "Uncategorized",
    "tags": [
        "JavaScript"
    ],
    "pubdate": "2013-08-14",
    "slug": "how-i-stopped-worrying-and-love-the-javascript",
    "layout": "post"
}