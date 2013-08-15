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
		$config = (array) json_decode($config);
		return $config;
	}
	public function configure($args)
	{
		$config = $this->read_config();
		if (empty($args)) {
			printf("Current configuration:\n");
			foreach ($config as $key => $value) {
				printf("%-15s = %s\n", $key, $value);
			}
		} else {
			if (array_key_exists($args, $config)) {
				printf("New value for %s [%s]: ", 
					$args, $config[$args]);
				$conf = trim(fgets(STDIN));
				if(empty($conf))
					$conf = $config[$args];
				$config[$args] = $conf;
				$config = json_encode($config, 
						  JSON_PRETTY_PRINT
						| JSON_UNESCAPED_SLASHES);
				file_put_contents('.config', $config);

				printf("`%s` set to '%s'.\n", $args, $conf);
			} else {
				echo "Invalid configuration node.\n";
			}
		}
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

		$json = ['title'    => $title,
			 'category' => $category,
			 'tags'     => $tags,
			 'pubdate'  => $date,
			 'slug'     => $this->slug($title), 
			 'layout'   => 'post'];

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
	public function build()
	{
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
		echo " OK\nRendering index ";
		$this->render_index('index');

		$json = file_get_contents('./posts/.tags');
		$json = (array) json_decode($json);
		foreach ($json as $tag => $vars) {
			$vars = (array) $vars;
			$vars['title'] = $tag;
			$this->render_index('tag', (array) $vars);
		}

		unset($vars);

		$json = file_get_contents('./posts/.categories');
		$json = (array) json_decode($json);
		foreach ($json as $tag => $vars) {
			$vars = (array) $vars;
			$vars['title'] = $tag;
			$this->render_index('category', (array) $vars);
		}
		echo " OK\n";
		
		$files = $this->write_checksums('posts');
		$files = $this->write_checksums('pages');
		echo "\nFinished building site.\n";
	}

	private function tag_list($type) 
	{
		$json = file_get_contents("./posts/.$type");
		$json = (array) json_decode($json);
		foreach ($json as $tag => $vars) {
			$tags[$tag] = (array) $vars;
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
				$json = (array) json_decode($json);
				$tag = $var['title'];
				break;
			case 'category':
				$dest .= $var['url'];
				$json = file_get_contents('./posts/.categories');
				$json = (array) json_decode($json);
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
		$json = (array) json_decode(file_get_contents('./posts/.index'));
		
		foreach ($json as $file => $data) {
			unset($tags);
			
			$posts[$file] = (array) $data;
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
		$json = (array) json_decode($json);
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
		$json = (array) json_decode($json);
		foreach ($json as $data) {
			$data = (array) $data;
			$urls[$data['slug']] = $data['url'];
		}
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
				$json = (array) json_decode($post[1]);
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
				$json = (array) json_decode($post);
				$json['url'] = $this->format_url($json, 'page');
				$pages[$file] = $json;
			}
		}
		echo " OK\n";
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
		$files = (array) json_decode($files);

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
