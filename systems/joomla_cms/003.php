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
* joomla1.5_003 Import Users module
*
* @package         ImpEx.joomla1.5
*
*/

class joomla_cms_003 extends joomla_cms_000
{
    var $_dependent     = '001';

    private $groups = array('Administrator' => 70, 'Author' => 71, 'Manager' => 72, 'Registered' => 73, 'Super Administrator' => 74);

    function joomla_cms_003(&$displayobject)
    {
        $this->_modulestring = $displayobject->phrases['import_user'];
    }

    function init(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
    {
        if ($this->check_order($sessionobject,$this->_dependent))
        {
            if ($this->_restart)
            {
                if (
                    $this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_users')
                    AND
                    $this->restart($sessionobject, $displayobject, $Db_target, $Db_source,'clear_imported_usergroups')
                    )
                {
                    $displayobject->display_now("<h4>{$displayobject->phrases['users_cleared']}</h4>");
                    $this->_restart = true;
                }
                else
                {
                    $sessionobject->add_error(substr(get_class($this) , -3), $displayobject->phrases['user_restart_failed'], $displayobject->phrases['check_db_permissions']);
                }
            }

            // Start up the table
            $displayobject->update_basic('title',$displayobject->phrases['import_user']);
            $displayobject->update_html($displayobject->do_form_header('index',substr(get_class($this) , -3)));
            $displayobject->update_html($displayobject->make_hidden_code(substr(get_class($this) , -3),'WORKING'));
            $displayobject->update_html($displayobject->make_table_header($this->_modulestring));

            // Ask some questions
            $displayobject->update_html($displayobject->make_input_code($displayobject->phrases['users_per_page'],'userperpage', 2000));
            $displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['email_match'], "email_match",0));
           # $displayobject->update_html($displayobject->make_yesno_code($displayobject->phrases['get_avatars'], 'get_avatars',0));
           # $displayobject->update_html($displayobject->make_input_code($displayobject->phrases['avatar_path'], 'get_avatars_path',$sessionobject->get_session_var('get_avatars_path'),1,60));

            $displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],$displayobject->phrases['reset']));

            $sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_done', '0');
            $sessionobject->add_session_var(substr(get_class($this) , -3) . '_objects_failed', '0');
            $sessionobject->add_session_var('userstartat','0');
    
            // Incative usergroup
            $target_db_type         = $sessionobject->get_session_var('targetdatabasetype');
            $target_table_prefix    = $sessionobject->get_session_var('targettableprefix');
            $usergroup_object       = new ImpExData($Db_target, $sessionobject, 'usergroup');

            $try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));

            $try->set_value('mandatory', 'importusergroupid',        '69');
            $try->set_value('nonmandatory', 'title',                "{$displayobject->phrases['imported']} {$displayobject->phrases['users']}");
            $try->import_usergroup($Db_target, $target_db_type, $target_table_prefix);
            unset($try);

            foreach ($this->groups AS $title => $id)
            {
                $try = (phpversion() < '5' ? $usergroup_object : clone($usergroup_object));
                $try->set_value('mandatory', 'importusergroupid', $id);
                $try->set_value('nonmandatory', 'title',          $title);
                $try->import_usergroup($Db_target, $target_db_type, $target_table_prefix);
                unset($try);
            }
        }
        else
        {
            // Dependant has not been run
            $displayobject->update_html($displayobject->do_form_header('index',''));
            $displayobject->update_html($displayobject->make_description("<p>{$displayobject->phrases['dependant_on']}<i><b> " . $sessionobject->get_module_title($this->_dependent) . "</b> {$displayobject->phrases['cant_run']}</i> ."));
            $displayobject->update_html($displayobject->do_form_footer($displayobject->phrases['continue'],''));
            $sessionobject->set_session_var(substr(get_class($this) , -3),'FALSE');
            $sessionobject->set_session_var('module','000');
        }
    }

    function resume(&$sessionobject, &$displayobject, &$Db_target, &$Db_source)
    {
        // Set up working variables.
        $displayobject->update_basic('displaymodules','FALSE');
        $target_database_type    = $sessionobject->get_session_var('targetdatabasetype');
        $target_table_prefix    = $sessionobject->get_session_var('targettableprefix');
        $source_database_type    = $sessionobject->get_session_var('sourcedatabasetype');
        $source_table_prefix    = $sessionobject->get_session_var('sourcetableprefix');

        // Per page vars
        $user_start_at            = $sessionobject->get_session_var('userstartat');
        $user_per_page            = $sessionobject->get_session_var('userperpage');
        $class_num                = substr(get_class($this) , -3);


        // Start the timing
        if(!$sessionobject->get_session_var($class_num . '_start'))
        {
            $sessionobject->timing($class_num ,'start' ,$sessionobject->get_session_var('autosubmit'));
        }

        // Get the banned and done (associated users)
        $usergroups     =    $this->get_imported_group_ids($Db_target, $target_database_type, $target_table_prefix);

        // Get a page worths of users
        $user_array  =  $this->get_joomla_user_details($Db_source, $source_database_type, $source_table_prefix, $user_start_at, $user_per_page);

        $displayobject->display_now("<h4>{$displayobject->phrases['importing']} " . $user_array['count'] . " {$displayobject->phrases['users']}</h4><p><b>{$displayobject->phrases['from']}</b> : " . $user_start_at . " ::  <b>{$displayobject->phrases['to']}</b> : " . ($user_start_at + $user_array['count']) . "</p>");

        $user_object = new ImpExData($Db_target, $sessionobject, 'user');

        foreach ($user_array['data'] as $user_id => $user)
        {
            $try = (phpversion() < '5' ? $user_object : clone($user_object));

            // Auto associate
            if ($sessionobject->get_session_var('email_match'))
            {
                $try->_auto_email_associate = true;
            }

            # Also has "name"
            $try->set_value('mandatory', 'username',     $user['username']);
            $try->set_value('mandatory', 'email',        $user['email']);
            if ($usergroups[$this->groups[$user['usertype']]])
            {
                $try->set_value('mandatory', 'usergroupid',  $usergroups[$this->groups[$user['usertype']]]);
            }
            else
            {
                $try->set_value('mandatory', 'usergroupid',  $usergroups[69]);
            }

            $try->set_value('mandatory', 'importuserid', $user_id);
    
            // Can't import password so just rand it for now
            $try->set_value('nonmandatory', 'password',     $this->fetch_user_salt());
            $try->set_value('nonmandatory', 'lastactivity', strtotime($user['lastvisitDate']));
            $try->set_value('nonmandatory', 'joindate',     strtotime($user['registerDate']));
            $try->set_value('nonmandatory', 'options',      $this->_default_user_options);

            if($try->is_valid())
            {
                if($try->import_user($Db_target, $target_database_type, $target_table_prefix))
                {
                    if(shortoutput)
                    {
                        $displayobject->display_now('.');
                    }
                    else
                    {
                        $displayobject->display_now('<br /><span class="isucc"><b>' . $try->how_complete() . '%</b></span> ' . $displayobject->phrases['user'] . ' -> ' . $try->get_value('mandatory','username'));
                    }

                    $sessionobject->add_session_var($class_num . '_objects_done',intval($sessionobject->get_session_var($class_num . '_objects_done')) + 1 );
                }
                else
                {
                    $sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
                    $sessionobject->add_error($Db_target, 'warning', $class_num, $user_id, $displayobject->phrases['user_not_imported'], $displayobject->phrases['user_not_imported_rem']);
                    $displayobject->display_now("<br />{$displayobject->phrases['failed']} :: {$displayobject->phrases['user_not_imported']}");
                }
            }
            else
            {
                $sessionobject->set_session_var($class_num . '_objects_failed',$sessionobject->get_session_var($class_num. '_objects_failed') + 1 );
                $sessionobject->add_error($Db_target, 'invalid', $class_num, $user_id, $displayobject->phrases['invalid_object'], $try->_failedon);
                $displayobject->display_now("<br />{$displayobject->phrases['invalid_object']}" . $try->_failedon);
            }
            unset($try);
        }

        // Check for page end
        if ($user_array['count'] == 0 OR $user_array['count'] < $user_per_page)
        {
            $sessionobject->timing($class_num,'stop', $sessionobject->get_session_var('autosubmit'));
            $sessionobject->remove_session_var($class_num . '_start');

            $this->build_user_statistics($Db_target, $target_database_type, $target_table_prefix);

            $displayobject->update_html($displayobject->module_finished($this->_modulestring,
                $sessionobject->return_stats($class_num, '_time_taken'),
                $sessionobject->return_stats($class_num, '_objects_done'),
                $sessionobject->return_stats($class_num, '_objects_failed')
            ));

            $sessionobject->set_session_var($class_num ,'FINISHED');
            $sessionobject->set_session_var('module','000');
            $sessionobject->set_session_var('autosubmit','0');
        }

        $sessionobject->set_session_var('userstartat',$user_start_at+$user_per_page);
        $displayobject->update_html($displayobject->print_redirect('index.php',$sessionobject->get_session_var('pagespeed')));
    }// End resume
}//End Class
/*======================================================================*/
?>
