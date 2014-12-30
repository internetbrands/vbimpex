<?php if (!defined('IDIR')) { die; }
/*======================================================================*\
|| #################################################################### ||
|| # vBulletin [#]version[#] - Licence Number [#]license[#]
|| # ---------------------------------------------------------------- # ||
|| # All PHP code in this file is �2000-[#]year[#] vBulletin Solutions Inc. # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------- VBULLETIN IS NOT FREE SOFTWARE ---------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html # ||
|| #################################################################### ||
\*======================================================================*/
/** 
* joomla1.5_000 
*
* @package      ImpEx.joomla1.5
* @version      $Revision: 2255 $
* @author       Jerry Hutchings <jerry.hutchings@vbulletin.com>
* @checkedout   $Name:  $
* @date         $Date: 2009-12-17 19:35:15 -0800 (Thu, 17 Dec 2009) $
* @copyright    http://www.vbulletin.com/license.html
*
*/

class joomla_cms_000 extends ImpExModule
{
    /**
    * Supported version
    *
    * @var    string
    */
    var $_version = '1.5';
    var $_tier = '1';
    var $_product = 'cms';
    
    /**
    * Module string
    *
    * Class string for phpUnit header
    *
    * @var    array
    */
    var $_modulestring     = 'Joomla - CMS';
    var $_homepage     = 'http://www.joomla.org';

    /**
    * Valid Database Tables
    *
    * @var    array
    */
    var $_valid_tables = array (
        'banner', 'bannerclient', 'bannertrack', 'categories', 'components', 'contact_details', 'content', 'content_frontpage', 'content_rating', 'core_acl_aro', 'core_acl_aro_groups',
        'core_acl_aro_map', 'core_acl_aro_sections', 'core_acl_groups_aro_map', 'core_log_items', 'core_log_searches', 'groups', 'menu', 'menu_types', 'messages', 'messages_cfg',
        'migration_backlinks', 'modules', 'modules_menu', 'newsfeeds', 'plugins', 'polls', 'poll_data', 'poll_date', 'poll_menu', 'sections', 'session', 'stats_agents', 'templates_menu',
        'users', 'weblinks'
    );


    function joomla_cms_000()
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
    function joomla_html($text, $truncate_smilies = false)
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
    * Select the Joomla node types
    *
    *
    * @return    array
    */
    function joomla_get_node_types(&$Db_object, &$databasetype, &$tableprefix)
    {
        $return_array = array();


        if ($databasetype == 'mysql')
        {
            $node_types = $Db_object->query("SELECT type, name FROM {$tableprefix}node_type ");

            while ($node = $Db_object->fetch_array($node_types))
            {
                $return_array["$node[type]"] = $node['name'];
            }

            return $return_array;
        }
        else
        {
            return false;
        }        
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
    function get_joomla_members_list(&$Db_object, &$databasetype, &$tableprefix, &$start_at, &$per_page)
    {
        $return_array = array();

        // Check that there isn't a empty value
        if(empty($per_page)) { return $return_array; }

        // Check Mandatory Fields.
        $req_fields = array(
            'id'     => 'mandatory',
            'name'    => 'mandatory'
        );

        if(!$this->check_table($Db_object, $databasetype, $tableprefix, "users", $req_fields))
        {
            return $return_array;
        }

        if ($databasetype == 'mysql')
        {
            $user_list = $Db_object->query("SELECT id, name FROM {$tableprefix}users ORDER BY id LIMIT {$start_at}, {$per_page}");

            while ($user = $Db_object->fetch_array($user_list))
            {
                $return_array["$user[id]"] = $user['name'];
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
    function get_joomla_user_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
    {
        $return_array = array();

        // Check that there isn't a empty value
        if(empty($per_page)) { return $return_array; }

        // Check Mandatory Fields.
        $req_fields = array(
            'id'     => 'mandatory',
            'name'    => 'mandatory'
        );

        if(!$this->check_table($Db_object, $databasetype, $tableprefix, "users", $req_fields))
        {
            return $return_array;
        }

        if ($databasetype == 'mysql')
        {
            $user_list = $Db_object->query("SELECT * FROM {$tableprefix}users ORDER BY id LIMIT {$start}, {$per_page}");

            while ($user = $Db_object->fetch_array($user_list))
            {
                $return_array['data']["$user[id]"] = $user;
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
    function get_joomla_content_details(&$Db_object, &$databasetype, &$tableprefix, &$start, &$per_page)
    {
        $return_array = array('data' => array(), 'count' => 0);

        // Check that there isn't a empty value
        if(empty($per_page)) { return $return_array; }


        if ($databasetype == 'mysql')
        {
            $sql = "
            SELECT * FROM {$tableprefix}content
            ORDER BY id
            LIMIT {$start}, {$per_page}";

            $details_list = $Db_object->query($sql);

            while ($detail = $Db_object->fetch_array($details_list))
            {
                $return_array['data']["$detail[id]"] = $detail;
                $return_array['lastid'] = $detail['id'];
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
|| # CVS: $RCSfile: 000.php,v $ - $Revision: 2255 $
|| ####################################################################
\*======================================================================*/
