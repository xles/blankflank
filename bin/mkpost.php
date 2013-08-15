#!/usr/bin/php
<?php
$delimiter = '---EOF---';

$test = '
# Your fancy ass blog post title

And some paragraphs to go with it.  
Such as this one.

And this one.
// Fancypants JSON metadata goes here:
--EOF--
{
	"title": "Your fancy ass blog post title",
	"tags": ["Fancy", "blog post"],
	"category": "Uncategorized"
}
';

$moo = ['blub' => ['moo' => 'boo', 'foo' => 'bar']];

//var_dump($argv);
$post = preg_split('/[\n]\s*[-]{2,}\s*EOF\s*[-]{2,}\s*[\n]/s', $test);
var_dump($post[0]);
var_dump(json_decode($post[1]));
//var_dump(json_decode($json));

//var_dump(json_encode($moo));


if ($argc >= 2) {
	$foo = new Moo();
} else {
	
}


class Moo {
	public function __construct()
	{
		echo "moo\n";

	}
	public function set_title($args) 
	{
		return null;
	}
	public function set_tags($args)
	{

	}
	public function set_category($args)
	{

	}
	public function set_pubdate()
	{

	}
	public function __destruct()
	{

	}
}
