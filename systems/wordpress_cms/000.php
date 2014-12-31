<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| ####################################################################
|| # vBulletin Impex
|| # ----------------------------------------------------------------
|| # All PHP code in this file is Copyright 2000-2014 vBulletin Solutions Inc.
|| # This code is made available under the Modified BSD License -- see license.txt
|| # http://www.vbulletin.com 
|| ####################################################################
\*======================================================================*/
/** 
* wordpress_000
*
* @package      ImpEx.wordpress
*
*/

class wordpress_cms_000 extends ImpExModule
{
    /**
    * Supported version
    *
    * @var    string
    */
    var $_version = '2.9.1';
    var $_tier = '1';
    var $_product = 'cms';
    
    /**
    * Module string
    *
    * Class string for phpUnit header
    *
    * @var    array
    */
    var $_modulestring     = 'Wordpress - CMS';
    var $_homepage     = 'http://www.wordpress.org';

    /**
    * Valid Database Tables
    *
    * @var    array
    */
    var $_valid_tables = array (
        'comments', 'links', 'options', 'postmeta', 'posts', 'redirection_groups', 'redirection_items', 'redirection_logs', 'redirection_modules',
        'terms', 'term_relationships', 'term_taxonomy', 'usermeta', 'users', 'yarpp_keyword_cache', 'yarpp_related_cache'
    );


    function wordpress_cms_000()
    {
    }

    /**
    * HTML parser
    *
    * @param    string    mixed            The string to parse
    * @param    boolean                    Truncate smilies
    *
    * @return    array
    */
    function wordpress_html($text, $truncate_smilies = false)
    {
        // Quotes
        // With name

        for($i=0;$i<10;$i++)
        {
            $text = preg_replace('#\[quote:([a-z0-9]+)="(.*)"\](.*)\[/quote:\\1\]#siU', '[quote=$2]$3[/quote]', $text);
        }
            // Without
        for($i=0;$i<10;$i++)
        {
            $text = preg_replace('#\[quote:([a-z0-9]+)\](.*)\[/quote:\\1\]#siU', '[quote]$2[/quote]', $text);
        }

        $text = preg_replace('#\[code:([0-9]+):([a-z0-9]+)\](.*)\[/code:\\1:\\2\]#siU', '[code]$3[/code]', $text);

        // Bold , Underline, Italic
        $text = preg_replace('#\[b:([a-z0-9]+)\](.*)\[/b:\\1\]#siU', '[b]$2[/b]', $text);
        $text = preg_replace('#\[u:([a-z0-9]+)\](.*)\[/u:\\1\]#siU', '[u]$2[/u]', $text);
        $text = preg_replace('#\[i:([a-z0-9]+)\](.*)\[/i:\\1\]#siU', '[i]$2[/i]', $text);

        // Images
        $text = preg_replace('#\[img:([a-z0-9]+)\](.*)\[/img:\\1\]#siU', '[img]$2[/img]', $text);

        // Lists
        $text = preg_replace('#\[list(=1|=a)?:([a-z0-9]+)\](.*)\[/list:(u:|o:)?\\2\]#siU', '[list$1]$3[/list]', $text);

        // Lists items
        $text = preg_replace('#\[\*:([a-z0-9]+)\]#siU', '[*]', $text);

        // Color
        $text = preg_replace('#\[color=([^:]*):([a-z0-9]+)\](.*)\[/color:\\2\]#siU', '[color=$1]$3[/color]', $text);

        // Font
        $text = preg_replace('#\[font=([^:]*):([a-z0-9]+)\](.*)\[/font:\\2\]#siU', '[font=$1]$3[/font]', $text);

        // Text size
        $text = preg_replace('#\[size=([0-9]+):([a-z0-9]+)\](.*)\[/size:\\2\]#siUe', "\$this->pixel_size_mapping('\\1', '\\3')", $text);

        // center
        $text = preg_replace('#\[align=center:([a-z0-9]+)\](.*)\[/align:\\1\]#siU', '[center]$2[/center]', $text);

        if($truncate_smilies)
        {
            $text = str_replace(array_keys($truncate_smilies), $truncate_smilies, $text);
        }

        $text = html_entity_decode($text);

        return $text;
    }



    /**
    * Regex call back
    *
    * @param    string    mixed            The origional size
    * @param    string    mixed            The content text
    *
    * @return    array
    */
    function pixel_size_mapping($size, $text)
    {
        $text = str_replace('\"', '"', $text);

        if ($size <= 8)
        {
           $outsize = 1;
        }
        else if ($size <= 10)
        {
           $outsize = 2;
        }
        else if ($size <= 12)
        {
           $outsize = 3;
        }
        else if ($size <= 14)
        {
           $outsize = 4;
        }
        else if ($size <= 16)
        {
           $outsize = 5;
        }
        else if ($size <= 18)
        {
           $outsize = 6;
        }
        else
        {
           $outsize = 7;
        }

        return '[size=' . $outsize . ']' . $text .'[/size]';
    }

    /**
    * Returns the user_id => username array
    *
    * @param    object    databaseobject    The database object to run the query against
    * @param    string    mixed            Table database type
    * @param    string    mixed            The prefix to the table name i.e. 'vb3_'
    * @param    int        mixed            Start point
    * @param    int        mixed            End point
    *
    * @return    array
    */
    function get_wordpress_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
    {
        $return_array = array();

        // Check that there isn't a empty value
        if(empty($per_page)) { return $return_array; }

        if ($databasetype == 'mysql')
        {
            $user_list = $Db_object->query("SELECT ID, user_login FROM {$tableprefix}users ORDER BY id LIMIT {$start_at}, {$per_page}");

            while ($user = $Db_object->fetch_array($user_list))
            {
                $return_array["$user[ID]"] = $user['user_login'];
            }
    
            return $return_array;
        }
        else
        {
            return false;
        }
    }

    /**
    * Returns the user details array
    *
    * @param    object    databaseobject    The database object to run the query against
    * @param    string    mixed            Table database type
    * @param    string    mixed            The prefix to the table name i.e. 'vb3_'
    * @param    int        mixed            Start point
    * @param    int        mixed            End point
    *
    * @return    array
    */
    function get_wordpress_user_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
    {
        $return_array = array();

        // Check that there isn't a empty value
        if(empty($per_page)) { return $return_array; }


        if ($databasetype == 'mysql')
        {
            $user_list = $Db_object->query("SELECT * FROM {$tableprefix}users ORDER BY id LIMIT {$start}, {$per_page}");

            while ($user = $Db_object->fetch_array($user_list))
            {
                $return_array['data']["$user[ID]"] = $user;
                $return_array['lastid'] = $user['ID'];
            }
    
            $return_array['count'] = count($return_array['data']);
            return $return_array;
        }
        else
        {
            return false;
        }
    }

    
    /**
    * Returns the user details array
    *
    * @param    object    databaseobject    The database object to run the query against
    * @param    string    mixed            Table database type
    * @param    string    mixed            The prefix to the table name i.e. 'vb3_'
    * @param    int        mixed            Start point
    * @param    int        mixed            End point
    *
    * @return    array
    */
    function get_wordpress_content_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
    {
        $return_array = array('data' => array(), 'count' => 0);

        // Check that there isn't a empty value
        if(empty($per_page)) { return $return_array; }


        if ($databasetype == 'mysql')
        {
            $sql = "
            SELECT * FROM {$tableprefix}posts
            WHERE post_type='post'
            ORDER BY ID
            LIMIT {$start}, {$per_page}";

            $details_list = $Db_object->query($sql);

            while ($detail = $Db_object->fetch_array($details_list))
            {
                $return_array['data']["$detail[ID]"] = $detail;
                $return_array['lastid'] = $detail['ID'];
            }
        }
        else
        {
            return false;
        }
        $return_array['count'] = count($return_array['data']);
        return $return_array;
    }


}// Class
/*======================================================================*\
|| ####################################################################
|| # Downloaded: [#]zipbuilddate[#]
|| # CVS: $RCSfile: 000.php,v $ - $Revision: $
|| ####################################################################
\*======================================================================*/
