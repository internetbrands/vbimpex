<?

// The in dir is where all the XML files that you have just got frmo Zeroforum are
// The out dir in where the cleaned ones are going.
// THEY MUST BE DIFFRENT

// You have to do the user file by hand, it will tell you where the errors are when you run it

$in_dir		= '/home/jerry/www/new_zero/forums/';
$out_dir	= '/home/jerry/www/new_zero/forums_done/';

################################################################################
# CHANGE NOTHING BELOW THIS LINE # CHANGE NOTHING BELOW THIS LINE # CHANGE NOTHI
################################################################################

if (function_exists('set_time_limit') AND get_cfg_var('safe_mode')==0)
{
	@set_time_limit(0);
}

if ($in_dir == $out_dir)
{
	die('Not going to happen, use a diffrent dir for the output files .....');
}

if ($dh = opendir($in_dir))
{
	while (($filename = readdir($dh)) !== false)
	{
		if (substr($filename, -4) == '.xml')
		{
			$in_file 	= fopen($in_dir . $filename, "r");
			$out_file 	= fopen($out_dir . $filename, "w");
			
			while (!feof($in_file))
			{
				$buffer = trim(fgets($in_file, 40960));
			
				if (substr($buffer,0, 11) == '<post_text>' AND substr($buffer,0, 20) != '<post_text><![CDATA[')
				{
					$post++;
					$buffer = str_replace('<post_text>', '<post_text><![CDATA[', $buffer);
					$buffer = str_replace('</post_text>', ']]></post_text>', $buffer);
				}   

				if (substr($buffer,0, 12) == '<post_title>' AND substr($buffer,0, 21) != '<post_title><![CDATA[')
				{
					$post_t++;
					$buffer = str_replace('<post_title>', '<post_title><![CDATA[', $buffer);
					$buffer = str_replace('</post_title>', ']]></post_title>', $buffer);
				}
				
				if (substr($buffer,0, 13) == '<forum_title>' AND substr($buffer,0, 22) != '<forum_title><![CDATA[')
				{
					$forum_t++;
					$buffer = str_replace('<forum_title>', '<forum_title><![CDATA[', $buffer);
					$buffer = str_replace('</forum_title>', ']]></forum_title>', $buffer);
				}

				if (substr($buffer,0, 14) == '<thread_title>' AND substr($buffer,0, 23) != '<thread_title><![CDATA[')
				{
					$thread_t++;
					$buffer = str_replace('<thread_title>', '<thread_title><![CDATA[', $buffer);
					$buffer = str_replace('</thread_title>', ']]></thread_title>', $buffer);
				}
			   
			   fwrite($out_file, $buffer . "\n");
			}
			
			fclose($in_file);
			fclose($out_file);			
			echo "<br><br><b>$filename<b><br>Post text = $post<br>Forum titles = $forum_t<br>Post titles = $post_t<br>Thread titles = $thread_t";
			$post_total 	+= $post;
			$forum_t_total 	+= $forum_t;
			$post_t_total	+= $post_t;
			$thread_t_total	+= $thread_t;
			unset($filename, $post, $forum_t, $post_t, $thread_t);
		}
	}
	closedir($dh);
}
echo "<HR>Post total = $post_total<br>Forum title total = $forum_t_total<br>Post title total = $post_t_total<br>Thread title total = $thread_t_total";

?>
