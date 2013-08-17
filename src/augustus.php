<?php

namespace Augustus;

require_once('./src/markdown.php');
use \Michelf\Markdown;

class Augustus {
	private $options = [	'forced' => false,
				'clean'  => false];

	private $config = [];

	private $tags = [];
	private $categories = [];

	public function __construct()
	{
		$config = $this->read_config();
		$config['template'] = './templates/'.$config['template'];
		$this->config = $config;
	}
	private function read_config()
	{
		$config = file_get_contents('.config');
		$config = json_decode($config, true);
		return $config;
	}
	public function print_array($arr, $level = 0) {
		foreach ($arr as $key => $val) {
			for($i=0; $i<$level; $i++)
				$key = '   '.$key;
			
			if(is_array($val)) {	
				printf("%s:\n",$key);
				$this->print_array($val, $level+1);
			}
			else {
				printf("%-15s = %s\n", $key, $val);
			}
		}	
	}
	public function configure($args)
	{
		$config = $this->read_config();
		if (empty($args)) {
			printf("Current configuration:\n");
			$this->print_array($config);
		} else {
			if (!array_key_exists($args, $config)) {
				echo "Invalid configuration node.\n";
				return false;
			} else if($args == 'syndication') {
				$config['syndication'] = 
					$this->configure_feeds();
			} else {
				printf("New value for %s [%s]: ", 
					$args, $config[$args]);
				$conf = trim(fgets(STDIN));
				if(empty($conf))
					$conf = $config[$args];
				$config[$args] = $conf;
			}
			$config = json_encode($config, 
					  JSON_PRETTY_PRINT
					| JSON_UNESCAPED_SLASHES);
			file_put_contents('.config', $config);

			printf("`%s` set to '%s'.\n", $args, $conf);
		}
	}
	public function configure_feeds()
	{
		$config = $this->read_config()['syndication'];

		echo "Syndication configuration wizard\n\n";
		printf("Blog Title:\n\"%s\"\n> ", $config['title']);
		$title = trim(fgets(STDIN));
		if(empty($title)) $title = $config['title'];
		
		printf("Blog description:\n\"%s\"\n> ", $config['description']);
		$description = trim(fgets(STDIN));
		if(empty($description)) $description = $config['description'];
		
		printf("Domain:\n\"%s\"\n> ", $config['url']);
		$url = trim(fgets(STDIN));
		if(empty($url)) $url = $config['url'];
		
		echo "Author:\n";
		printf("Name [%s]: ", $config['author']['name']);
		$author['name'] = trim(fgets(STDIN));
		if(empty($author['name'])) $author['name'] = $config['author']['name'];

		printf("E-mail [%s]: ", $config['author']['email']);
		$author['email'] = trim(fgets(STDIN));
		if(empty($author['email'])) $author['email'] = $config['author']['email'];

		if(empty($config['atom_id']))
			$config['atom_id'] = 'tag:'.$url.','.date('Y').':'.md5($title);

		$json = ['title'    => $title,
			 'description' => $description,
			 'url'     => $url,
			 'author'  => $author,
			 'atom_id'  => $config['atom_id']];
		return $json;
	}

	public function new_post()
	{
		echo "Creating a new post\nTitle: ";
		$title = trim(fgets(STDIN));

		echo "Publish date [".date('Y-m-d')."]: ";
		$date = trim(fgets(STDIN));
		if(empty($date))
			$date = date('Y-m-d');

		echo "Category [Uncategorized]: ";
		$category = trim(fgets(STDIN));
		if(empty($category))
			$category = 'Uncategorized';

		echo "Tags (separate by commas): ";
		$tags = array_map('trim',(explode(',', fgets(STDIN))));

		$atom_id = 'tag:'.$this->config['syndication']['url'];
		$atom_id += ','.$date.':'.md5($title);

		$json = ['title'    => $title,
			 'category' => $category,
			 'tags'     => $tags,
			 'pubdate'  => $date,
			 'slug'     => $this->slug($title), 
			 'layout'   => 'post',
			 'atom_id'  => $atom_id];

		$md  = "Post goes here\n\n";
		$md .= "---EOF---\n";
		$md .= json_encode($json, JSON_PRETTY_PRINT);
		
		$filename = 'posts/'.$date.'_'.$json['slug'].'.md';
		file_put_contents($filename, $md);
		system('subl -w ./'.$filename);

		exit("Blog post saved as $filename.\n");
	}
	public function new_page()
	{
		echo "Creating a new static page\nTitle: ";
		$title = trim(fgets(STDIN));
		$slug = $this->slug($title);

		echo "Path [/$slug]: ";
		$path = trim(fgets(STDIN));
		if(empty($path))
			$path = '/'.$slug;

		$json = ['title'    => $title,
			 'slug'     => $slug, 
			 'layout'   => 'page',
			 'path'     => $path];

		$md  = "Post goes here\n\n";
		$md .= "---EOF---\n";
		$md .= json_encode($json, JSON_PRETTY_PRINT
					| JSON_UNESCAPED_SLASHES);
		
		$filename = 'pages/'.$json['slug'].'.md';
		file_put_contents($filename, $md);
		system('subl -w ./'.$filename);

		exit("Static page saved as $filename.\n");
	}

	public function check_config()
	{
		$config = $this->read_config();
		if(empty($config['syndication']['atom_id'])) {
			echo "\e[31mWARNING: You're building on default syndication settings. "
				."Doing this is inadvisable, as your \e[0m\n"
				."Would you like to configure them now? "
				."[\e[32mYes\e[0m] / \e[31mNo\e[0m / \e[33mAbort\e[0m: ";
			$c = trim(fgets(STDIN));
			switch (strtolower($c[0])) {
				case 'n':
					echo "\e[33mWarning ignored, continuing build\e[0m\n";
					return true;
					break;
				case 'a':
					return false;
					break;
				case 'y':
				default:
					$this->configure('syndication');
					return true;
					break;
			}
		} else {
			return true;
		}
	}

	public function build()
	{
		echo "Building in progress...\n\n";
		if(!$this->check_config()) {
			exit("\e[33mAborted by user\e[0m.\n\nBuild halted.\n");
		}
		if ($this->options['clean'] == true
			&& $this->options['forced'] == true) {
			echo "Cleaning up build directory ";
			$this->clean_build();
			echo "\n";
		}
		
		$this->copy_site_assets();
		
		$this->write_indicies();
		
		$this->tags = $this->tag_list('tags');
		$this->categories = $this->tag_list('categories');

		$pages = $this->checksum('pages');
		$posts = $this->checksum('posts');
		echo "Rendering pages ";
		$files = array_merge($posts, $pages);
		foreach ($files as $file) {
			$this->render_page($file);
			echo '.';
		}
		echo " \e[32mOK\e[0m\nRendering index ";
		$this->render_index('index');

		$json = file_get_contents('./posts/.tags');
		$json = json_decode($json, true);
		foreach ($json as $tag => $vars) {
			$vars = $vars;
			$vars['title'] = $tag;
			$this->render_index('tag', $vars);
		}

		unset($vars);

		$json = file_get_contents('./posts/.categories');
		$json = json_decode($json, true);
		foreach ($json as $tag => $vars) {
			$vars = $vars;
			$vars['title'] = $tag;
			$this->render_index('category', $vars);
		}
		echo " \e[32mOK\e[0m\n";
		
		$files = $this->write_checksums('posts');
		$files = $this->write_checksums('pages');
		$this->generate_feeds();
		echo "\nFinished building site.\n";
	}

	private function tag_list($type) 
	{
		$json = file_get_contents("./posts/.$type");
		$json = json_decode($json, true);
		foreach ($json as $tag => $vars) {
			$tags[$tag] = $vars;
			$tags[$tag]['title'] = $tag;
		}
		natcasesort($tags);
		return $tags;
	}

	private function format_url($meta, $type = 'post')
	{
		unset($url);
		switch($type) {
			case 'post':
				list (	$meta['year'], 
					$meta['month'], 
					$meta['day'] 
				) = explode ('-', $meta['pubdate']);
					
				$format = explode('/', $this->config['url_format']);

				foreach($format as $element) {
					$url .= '/'.$meta[$element];
				}
				break;
			case 'page':
				$url = $meta['path'];			
				break;
			case 'category':
				$url = '/category/'.$meta;
				break;
			case 'tag':
				$url = '/tag/'.$meta;
				break;
		}
		
		if ($this->config['pretty_urls'] == 'enabled') {
			$url .= '/';
		} else {
			$url .= '.html';
		}

		//$meta['url'] = $url;

		//return $meta['url'];

		//var_dump($url);
		return $url;
	}

	private function clean_build() 
	{
		//$dir = $this->build_dir;
		$dir = './build';
//		$dir = '../';

		if ($dir == '../' || $dir == './../') {
			echo "WARNING: Cleaning up in Parent directory!\n";
		}
		$files = scandir($dir);
		foreach ($files as $file) {
			if ($file[0] != '.') {
				$this->runlink("$dir/$file");
			}
		}

	}
	private function runlink($dir)
	{
		if (is_dir($dir)) {
			$files = scandir($dir);
			foreach ($files as $file) {
				if ($file[0] != '.') {
					$this->runlink("$dir/$file");
				}
			}
			rmdir($dir);
		} else {
			unlink($dir);
			echo '.';
		}
	}
	private function copy_site_assets()
	{
		echo "Copying layout assets to build directory ";
		$template = $this->config['template'];
		$files = scandir($template);
		foreach ($files as $file) {
			if ($file[0] != '.')
			if (is_dir("$template/$file")) {
					if (!file_exists("./build/$file"))
						mkdir("./build/$file");
				$this->copy_dir("$template/$file", 
					'./build/'.$file);
				echo '.';
			}
		}
		echo "\n";
	}
	private function copy_dir($src, $dst)
	{
		$files = scandir($src);
		foreach ($files as $file) {
			if($file[0] != '.') {
				if(is_dir("$src/$file")) {
					if (!file_exists("$dst/$file"))
						mkdir("$dst/$file");
					$this->copy_dir("$src/$file", "$dst/$file");
				} else {
					if (!copy("$src/$file", "$dst/$file")) 
						echo "$src/$file failed.\n";
				}
			}
		}
	}
	private function output_file($filename, $content)
	{
		$path = pathinfo($filename);
		if ($path['filename'] == 'index')  {
			$filename .= '';
		} else if ($this->config['pretty_urls'] == 'enabled') {
			$filename .= 'index.html';
		} else {
			$filename .= '';//'.html';
//			echo $filename."\n";
		}
		$path = pathinfo($filename);
		if (!file_exists($path['dirname']))
			mkdir($path['dirname'], 0777, true);

		file_put_contents($filename, $content);		
	}
	private function render_index($type, $var = [])
	{
		$dest = './build';
		$layout = $this->config['template'].'/'.$type.'.html';

		switch ($type) {
			case 'index':
				$dest .= '/index.html';
				break;
			case 'tag':
				$dest .= $var['url'];
				$json = file_get_contents('./posts/.tags');
				$json = json_decode($json, true);
				$tag = $var['title'];
				break;
			case 'category':
				$dest .= $var['url'];
				$json = file_get_contents('./posts/.categories');
				$json = json_decode($json, true);
				$category = $var['title'];
				break;
		}

		$posts = $this->read_index();

		$tags = $this->tags;
		$categories = $this->categories;
		$page_url = $this->get_urls();

		if ($type != 'index') {
			foreach ($var['files'] as $file) {
				$tmp[$file] = $posts[$file];
			}
			$posts = $tmp;
		}
		
		$posts = $this->get_post($posts);

/*		foreach ($posts as $post) {
			foreach ($post['tags'] as $tag) {
				$pt[] = ['title' => $tag,
					 'url' => $this->format_url(
						$this->slug($tag), 'tag'
					 )
					];
			}
			$posts[$post]['tags'] = $pt;
		}
*/
		krsort($posts);

		ob_start();
		include($this->config['template'].'/layout.html');
		$site = ob_get_contents();
		ob_end_clean();

//		echo "$dest\n";
		$this->output_file($dest, $site);
		echo '.';
	}
	private function get_post($posts)
	{
		if (is_array($posts)) {
			foreach ($posts as $file => $meta) {
				$buffer = file_get_contents("./posts/$file");
				$pattern = '/[\n]\s*[-]{2,}\s*EOF\s*[-]{2,}\s*[\n]/s';
				$content = preg_split($pattern, $buffer)[0];
				if ($this->config['prosedown'] == "enabled")
					$content = $this->prosedown($content);
				$content = Markdown::defaultTransform($content);

				$content = $this->more($content);

				$posts[$file]['content'] = $content;
			}
			return $posts;
		} else {
			$buffer = file_get_contents("./posts/$posts");
			$pattern = '/[\n]\s*[-]{2,}\s*EOF\s*[-]{2,}\s*[\n]/s';
			$content = preg_split($pattern, $buffer)[0];
			if ($this->config['prosedown'] == "enabled")
				$content = $this->prosedown($post);
			$content = Markdown::defaultTransform($content);
			return $content;
		}
	}
	private function timeago($date)
	{
		$time = strtotime($date);
		$iso = date('r', $time);
		$hr = date('F jS, Y', $time);
		$s = "<time class=\"timeago\" datetime=\"$iso\">$hr</time>";
		return $s;
	}
	private function read_index()
	{
		$json = json_decode(file_get_contents('./posts/.index'), true);
		
		foreach ($json as $file => $data) {
			unset($tags);
			
			$posts[$file] = $data;
/*
			list(	$posts[$file]['year'], 
				$posts[$file]['month'], 
				$posts[$file]['day'] 
			) = explode('-', $posts[$file]['pubdate']);
			
			$url = explode('/', $this->config['url_format']);
			foreach($url as $element) {
				$posts[$file]['url'] .= '/'.$posts[$file][$element];
			}
			$posts[$file]['url'] .= '.html';
*/
			//$posts[$file]['url'] = $this->format_url($posts[$file]);
			$posts[$file]['category'] = 
				sprintf('<a href="%s">%s</a>', 
					$this->categories[
						$posts[$file]['category']
					]['url'], 
					$posts[$file]['category']
				);
			foreach ($posts[$file]['tags'] as $tag) {
				$tags[] = ['title' => $tag,
					   'url' => $this->format_url(
					 	$this->slug($tag), 'tag'
					   )
				];
			}
			$posts[$file]['tags'] = $tags;

			$posts[$file]['date'] = $posts[$file]['pubdate'];
			$posts[$file]['pubdate'] = 
				$this->timeago($posts[$file]['pubdate']);
		}
		return $posts;
	}

	public function render_page($file)
	{
		$dest = './build';

		$filename = pathinfo($file, PATHINFO_FILENAME);
		list($date, $slug) = explode('_', $filename);
		list($year, $month, $day) = explode('-', $date);
		//var_dump($date, $slug, $year, $month, $day);


		$buffer = file_get_contents($file);
		$pattern = '/[\n]\s*[-]{2,}\s*EOF\s*[-]{2,}\s*[\n]/s';
		list($content, $json) = preg_split($pattern, $buffer);

		if ($this->config['prosedown'] == "enabled")
			$content = $this->prosedown($content);
		$content = Markdown::defaultTransform($content);
		$json = json_decode($json, true);
		$page_title = $json['title'];
		$layout = $this->config['template'].'/'.$json['layout'].'.html';

		$tags = $this->tags;
		$categories = $this->categories;

		$category = [	'title' => $json['category'],
				'url' => $this->format_url(
					$this->slug($json['category']),
					'category'
				)
		];
		$pubdate = $this->timeago($json['pubdate']);
		$title = $json['title'];
		
		if ($json['layout'] == 'post')
		foreach ($json['tags'] as $tag) {
			$pt[] = [	'title' => $tag,
					'url' => $this->format_url(
						$this->slug($tag), 'tag'
					)
				];
		}
		$post_tags = $pt;
		$page_url = $this->get_urls();

		$site['title'] = $this->config['syndication']['title'];
		$site['description'] = $this->config['syndication']['description'];

		if ($this->config['comments'] == 'intensedebate')
			$intensedebate = $this->config['intensedebate'];

		switch ($json['layout']) {
			case 'post':
		//		$dest .= "$year/$month/$slug";
				$dest .= $this->format_url($json);
				break;
			case 'page':
				$dest .= $this->format_url($json, 'page');
		//		$dest .= $json['path']."$slug";
				break;
		}
		
		ob_start();
		include($this->config['template'].'/layout.html');
		$site = ob_get_contents();
		ob_end_clean();

		$this->output_file($dest, $site);
	}

	private function get_urls()
	{
		$json = file_get_contents('./pages/.index');
		$json = json_decode($json, true);
		foreach ($json as $data) {
			$data = $data;
			$urls[$data['slug']] = $data['url'];
		}
		$cfg = $this->read_config()['syndication'];
		$urls['atom_feed'] = $cfg['url'].'/feed/atom.xml';
		$urls['rss_feed'] = $cfg['url'].'/feed/rss.xml';
		return $urls;
	}
	public function write_indicies()
	{
		echo "Writing indicies ";
		$files = scandir('./posts/');
		foreach ($files as $file) {
			if ($file[0] != '.') {
				$tmp = file_get_contents('./posts/'.$file);
				$pattern = '/[\n]\s*[-]{2,}\s*EOF\s*[-]{2,}\s*[\n]/s';
				$post = preg_split($pattern, $tmp);
				$json = json_decode($post[1], true);
				$cats[$json['category']]['slug'] = 
					$this->slug($json['category']);
				$cats[$json['category']]['url'] =
					$this->format_url(
						$cats[$json['category']]['slug'],
						'category'
					);
				$cats[$json['category']]['files'][] = $file;
				foreach ($json['tags'] as $tag) {
					$tags[$tag]['slug'] = $this->slug($tag);
					$tags[$tag]['url'] = 
						$this->format_url(
							$tags[$tag]['slug'], 'tag'
						);
					$tags[$tag]['files'][] = $file;
					echo '.';
				}
				$json['url'] = $this->format_url($json);
				$index[$file] = $json;
				echo '.';
			}
		}
		$files = scandir('./pages/');
		foreach ($files as $file) {
			if ($file[0] != '.') {
				$tmp = file_get_contents('./pages/'.$file);
				$pattern = '/[\n]\s*[-]{2,}\s*EOF\s*[-]{2,}\s*[\n]/s';
				$post = preg_split($pattern, $tmp)[1];
				$json = json_decode($post, true);
				$json['url'] = $this->format_url($json, 'page');
				$pages[$file] = $json;
			}
		}
		echo " \e[32mOK\e[0m\n";
		$cats = json_encode($cats, JSON_PRETTY_PRINT 
					 | JSON_UNESCAPED_SLASHES);
		$tags = json_encode($tags, JSON_PRETTY_PRINT
					 | JSON_UNESCAPED_SLASHES);
		$index = json_encode($index, JSON_PRETTY_PRINT
					   | JSON_UNESCAPED_SLASHES);
		$pages = json_encode($pages, JSON_PRETTY_PRINT
					   | JSON_UNESCAPED_SLASHES);
		if (file_put_contents('./posts/.categories', $cats)  &&
			file_put_contents('./posts/.tags', $tags) &&
			file_put_contents('./posts/.index', $index) &&
			file_put_contents('./pages/.index', $pages))
			return true;
		else
			return false;		
	}
	public function more($str) {
		if(!$this->config['more_tag'])
			return $str;
		/*
		$str = dom;
		inspect dom
		count p for config:more_tag
		drop after
		dom = str
		*/


		return $str; 
	}

	public function generate_feeds() 
	{
		$cfg = $this->config['syndication'];
		$site_url = 'http://'.$cfg['url'].'/';

		$rss = new \SimpleXMLElement(
			'<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom"></rss>'); 
		$rss->addChild('channel'); 
		$rss->channel->addChild('title', $cfg['title']); 
		$l = $rss->channel->addChild('atom:link', false, 'http://www.w3.org/2005/Atom');
		$l->addAttribute('href', $site_url.'feed.rss'); 
		$l->addAttribute('rel', 'self'); 
		$l->addAttribute('type', 'application/rss+xml'); 
		$rss->channel->addChild('link', $site_url); 
		$rss->channel->addChild('description', $cfg['description']);
		$rss->channel->addChild('lastBuildDate', date(DATE_RSS)); 
		 
		$atom = new \SimpleXMLElement(
			'<feed xmlns="http://www.w3.org/2005/Atom"></feed>'); 
		$atom->addChild('title', $cfg['title']); 
		$l = $atom->addChild('link');
		$l->addAttribute('href', $site_url.'feed.atom'); 
		$l->addAttribute('rel', 'self'); 
		$l = $atom->addChild('link');
		$l->addAttribute('href', $site_url); 
		$atom->addChild('subtitle', $cfg['description']);
		$atom->addChild('updated', date(DATE_ATOM)); 
		$atom->addChild('id', $cfg['atom_id']); 

		/*   'tag:'.$cfg['url'].',date:hash'   */
		
		$posts = $this->get_post($this->read_index());

		foreach ($posts as $post) { 
			$post_url = 'http://'.$cfg['url'].$post['url'];
			
			$item = $rss->channel->addChild('item'); 
			$item->addChild('title', $post['title']); 
			$item->addChild('link', $post_url);
			$item->addChild('description', $post['content']); 
			$item->addChild('pubDate', 
				date(DATE_RSS, strtotime($post['date']))); 
			$item->addChild('guid', $post_url); 

			$entry = $atom->addChild('entry');
			$entry->addChild('title', $post['title']); 
			$entry->addChild('link');
			$entry->link->addAttribute('href', $post_url);
			$entry->addChild('content', $post['content']);
			$entry->content->addAttribute('type', 'html'); 
			$entry->addChild('updated', 
				date(DATE_ATOM, strtotime($post['date']))); 
			$entry->addChild('id', $post['atom_id']); 
			$entry->addChild('author');
			$entry->author->addChild('name', $cfg['author']['name']);
			$entry->author->addChild('email', $cfg['author']['email']);
		} 

		if(!file_exists('./build/feed'))
			mkdir('./build/feed');

		if ($rss->asXML('./build/feed/rss.xml') &&
				$atom->asXML('./build/feed/atom.xml'))
			return true;
		else 
			return false;
	}
	public function write_checksums($dir)
	{
		echo "Writing checksums for $dir ";
		$files = scandir("./$dir/");
		foreach ($files as $file) {
			if ($file[0] != '.') {
				$tmp[$file] = md5_file("./$dir/$file");
				echo '.';
			}
		}
		echo "\n";
		$json = json_encode($tmp, JSON_PRETTY_PRINT);
		if (file_put_contents("./$dir/.checksums", $json))
			return true;
		else
			return false;

	}
	public function checksum($dir)
	{
		$path = "./$dir/";
		$rebuild = [];
		if ($this->options['forced'] == true) {
			echo "Skipping checksums for $dir, build forced.\n";
			$tmp = scandir($path);
			foreach ($tmp as $file) {
				if ($file[0] != '.') {
					$rebuild[] = $path.$file;
				}
			}
			return $rebuild;
		}

		$files = file_get_contents($path.'.checksums');
		$files = json_decode($files, true);

		$tmp = array_diff(scandir($path), array_keys($files));
		echo "Checking for new $dir ";
		foreach ($tmp as $file) {
			if ($file[0] != '.') {
				$rebuild[] = $path.$file;
				echo '.';
			}
		}
		echo "\n";

		echo "Checking checksums for updated $dir ";
		foreach ($files as $file => $checksum) {
			if($checksum != md5_file($path.$file))
				$rebuild[] = $path.$file;
			echo '.';
		}
		echo "\n";
		return $rebuild;
	}
	public function rm_post($var)
	{
#		unlink($file);
		$this->write_checksums();
		$this->write_indicies();
	}
	public function edit_post($var)
	{

	}

	public function new_category()
	{

	}
	public function edit_category()
	{

	}
	public function rm_category()
	{

	}

	public function set_options($options)
	{
		if (in_array('f', $options))
			$this->options['forced'] = true;
		if (in_array('c', $options))
			$this->options['clean'] = true;

	}
	private function slug($str)
	{
		$str = strtolower($str);
		$str = str_replace(' ', '-', $str);
		$str = preg_replace('/[^a-z0-9|\-|_]/','',$str);
		$words = explode('-',$str);
//		$str = substr($str, 0, $this->config->get('alias_length'));
		if(count($words) >= 3) {
			$word = strrchr($str, '-');
			if(!in_array(str_replace('-', '', $word), $words)) {
				$end = strrpos($str, $word);
				$str = substr($str, 0, $end);
			}
		}
		return $str;
	}
	private function prosedown($str)
	{
		// Em-dashes
		$str = preg_replace('/([^-\s])-{3}([^-\s])/m', '$1&mdash;$2',$str);
		$str = preg_replace('/(\S+[\s])-{3}([\s])/m', '$1&mdash;$2',$str);

		// En-dashes
		$str = preg_replace('/([^-\s])-{2}([^-\s])/m', '$1&ndash;$2',$str);
		$str = preg_replace('/(\S?[\s])-{2}([\s])/m', '$1&ndash;$2',$str);

		// Dinkus
		$str = preg_replace('/^[ |\t]*([ ]?\*[ ]+\*[ ]+\*)[ \t]*$/m',
			'<p class="scene-break">* * *</p>',$str);

		// Asterism
		$str = preg_replace('/^[ |\t]*([ ]?\*){3}[ \t]*$/m',
			'<p class="scene-break">&#8258;</p>',$str);

		// Horizontal rules
		$str = preg_replace('/^[ |\t]*([ ]?[\*\_\-\=\~][ ]?){3,}[ \t]*$/m',
			'<hr />',$str);

		// Emphasism
		$str = preg_replace('/(_)(?=\S)([^\r]*?\S)\1/',
			"<u>$2</u>",$str);
		$str = preg_replace('/(\*)(?=\S)([^\r]*?\S)\1/',
			"<strong>$2</strong>",$str);
		$str = preg_replace('/(\/)(?=\S)([^\r]*?\S)\1/',
			"<em>$2</em>",$str);
		$str = preg_replace('/\s(\-)(?=\S)([^\r]*?\S)\1\s/',
			" <s>$2</s> ",$str);

	//	str = str.replace(/\r\n/g,"\n");
	//	str = str.replace(/\n\r/g,"\n");
	//	str = str.replace(/\r/g,  "\n");

		//English spacing
		$str = preg_replace('/(\w[\.|\!|\?])[ ]{2}(\S)/m',
			'$1&ensp;$2',$str);

		return $str;	
	}
}
