#!/usr/bin/php
<?php
/**
 * Augustus, a static page generator
 */
namespace Augustus;
$version = "Augustus 0.0.1\n";
date_default_timezone_set('UTC');

if ($argc == 1) 
	exit("Ambiguous command, see `gusto help` for more info.\n");

include('./src/augustus.php');

$gusto = new Augustus();

if (in_array('--help', $argv))
	print_help();
if (in_array('--version', $argv))
	exit($version);

if ($argc == 2) {
	if ($argv[1] == 'help')
		print_help();
	$options = [];
	$method = $argv[1];
	$args = [];
} else {
	if ($argv[1][0] == '-') {
		$options = array_slice(str_split($argv[1]), 1);
		$method = implode('_',array_slice($argv, 2, 2));
		$args = array_slice($argv, 4);
	} else if ($argv[1] == 'configure') {
		$options = [];
		$method = $argv[1];
		$args = $argv[2];
	} else {
		$options = [];
		$method = implode('_',array_slice($argv, 1, 2));
		$args = array_slice($argv, 3);
	}
}

if (in_array('h', $options))
	print_help();

$gusto->set_options($options);

if (method_exists($gusto, $method)) {
	$gusto->$method($args);
} else {
	if ($argv[1][0] == '-')
		$cmd = implode(' ',array_slice($argv, 2));
	else
		$cmd = implode(' ',array_slice($argv, 1));

	exit("Unknown command '{$cmd}'.\n");
}


/**
 * Prints help and usage to the termianl
 */
function print_help()
{
	$help = 
'Augustus is a static page generator and blog engine, written in php 5.4

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
';
	exit($help);
}
