# Augstus
## `gusto help`

```
Augustus is a static page generator and blog engine, written in php 5.4

Usage: gusto [options] <command> [<args>].

Available commands:
   add         Adds new entry to 
   rm          Remove an entry from
   edit        Alters an entry in
   list        Lists entries in
   build       Generates the static pages.
   configure   List and set configuration options.
   help        Prints this help file.

Build options:
   -f   Forced build.  Re-generates all pages regardless of checksum.
   -c   Clean build.  Wipes the build/ directory clean prior to generating
        static pages.  Must be used together with -f

Examples:
   gusto add post          Add new post.
   gusto -cf build         Clean build directory and generate static pages.
```

## Recommended installation and deployment using GitHub Pages, 
with Augustus as a submodule.

* Make new github repo `username.github.io`

* `$ git clone https://github.com/username/username.github.io.git`

* `$ echo "yourfancydomain.com" > CNAME`

* `$ git add CNAME`

* `$ git commit -m "Added CNAME file."`

* `$ git push`

* `$ git submodule add https://github.com/xles/augustus.git .gusto`

* `$ cd .gusto/`

  * `$ ./gusto configure` edit the options you want.

  * `$ ./gusto new post` follow the CLI prompts.

  * `$ ./gusto build`

  * `$ ./gusto publish`

  * `$ cd ..`

* `git add .`

* `git commit -m "Wrote some blog posts"`

* `git push`


## F.A.Q.

### What's with the name, where'd "Augustus" and "Gusto" come from?

Isn't it obvious?  [Augustus "Gusto" Gummi][1], the character voiced by 
[Rob Paulsen][2] in Disney's Adventures of the Gummi Bears.

### Why didn't you just use Jekyll?

Because I don't want to.

### PHP 5.4 isn't widely deployed yet!

That's not really a question, and also it's irrelevant as this is intended
to be run on your workstation and not on the server. 

[1]: <http://en.wikipedia.org/wiki/Disney%27s_Adventures_of_the_Gummi_Bears#Gummi-Glen>

[2]: <http://en.wikipedia.org/wiki/Rob_Paulsen>
