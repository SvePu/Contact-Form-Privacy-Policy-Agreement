<?php

/**
 * Contact Form Privacy Policy Agreement Main Plugin File
 * Copyright 2022 SvePu
 *
 */

// Disallow direct access to this file for security reasons
if (!defined("IN_MYBB"))
{
    die("Direct initialization of this file is not allowed.");
}

if (defined('THIS_SCRIPT'))
{
    global $templatelist;

    if (isset($templatelist))
    {
        $templatelist .= ',';
    }

    if (THIS_SCRIPT == 'contact.php')
    {
        $templatelist .= 'contact_privacy_policy';
    }
}

if (defined('IN_ADMINCP'))
{
    $plugins->add_hook('admin_config_settings_begin', 'cfpp_agreement_settings');
    $plugins->add_hook("admin_settings_print_peekers", 'cfpp_agreement_peekers');
}
else
{
    $plugins->add_hook('contact_do_start', 'cfpp_agreement_do_start');
    $plugins->add_hook('contact_end', 'cfpp_agreement_end');
}


function cfpp_agreement_info()
{
    global $db, $lang;
    $lang->load('cfpp_agreement', true);

    return array(
        "name"          => $db->escape_string($lang->cfpp_agreement),
        "description"   => $db->escape_string($lang->cfpp_agreement_desc),
        "website"       => "https://github.com/SvePu/Contact-Form-Privacy-Policy-Agreement",
        "author"        => "SvePu",
        "authorsite"    => "https://github.com/SvePu",
        "version"       => "1.0",
        "codename"      => "cfpp_agreement",
        "compatibility" => "18*"
    );
}

function cfpp_agreement_install()
{
    global $db, $lang;
    $lang->load('cfpp_agreement', true);

    // Add Templates
    $templates = array(
        'contact_privacy_policy' => '<tr>
    <td class="trow1">&nbsp;</td>
    <td class="trow1" valign="top"><input type="checkbox" class="checkbox" name="cfpp_agreement" id="cfpp_agreement" value="1" /><label for="cfpp_agreement">{$lang->cfpp_agreement_simple}</label></td>
</tr>'
    );

    foreach ($templates as $name => $template)
    {
        $addtemplate = array(
            'title' => $db->escape_string($name),
            'template' => $db->escape_string($template),
            'version' => 1,
            'sid' => -2,
            'dateline' => TIME_NOW
        );

        $db->insert_query('templates', $addtemplate);
        unset($addtemplate);
    }

    // Add Settings
    $query = $db->simple_select('settinggroups', 'MAX(disporder) AS disporder');
    $disporder = (int)$db->fetch_field($query, 'disporder');

    $setting_group = array(
        'name' => 'cfpp_agreement',
        "title" => $db->escape_string($lang->setting_group_cfpp_agreement),
        "description" => $db->escape_string($lang->setting_group_cfpp_agreement_desc),
        'isdefault' => 0
    );

    $setting_group['disporder'] = ++$disporder;

    $gid = (int)$db->insert_query('settinggroups', $setting_group);

    $settings = array(
        'active' => array(
            'optionscode' => 'yesno',
            'value' => 1
        ),
        'showgroups' => array(
            'optionscode' => 'groupselect',
            'value' => '-1'
        ),
        'showlink' => array(
            'optionscode' => 'yesno',
            'value' => 0
        ),
        'link' => array(
            'optionscode' => 'text',
            'value' => ''
        )
    );

    $disporder = 0;

    foreach ($settings as $name => $setting)
    {
        $name = "cfpp_agreement_{$name}";

        $setting['name'] = $db->escape_string($name);

        $lang_var_title = "setting_{$name}";
        $lang_var_description = "setting_{$name}_desc";

        $setting['title'] = $db->escape_string($lang->{$lang_var_title});
        $setting['description'] = $db->escape_string($lang->{$lang_var_description});
        $setting['disporder'] = $disporder;
        $setting['gid'] = $gid;

        $db->insert_query('settings', $setting);
        ++$disporder;
    }

    rebuild_settings();
}

function cfpp_agreement_is_installed()
{
    global $mybb;
    if (isset($mybb->settings['cfpp_agreement_active']))
    {
        return true;
    }
    return false;
}

function cfpp_agreement_uninstall()
{
    global $db;

    // Remove Templates
    $db->delete_query("templates", "title IN('contact_privacy_policy')");

    // Remove Settings
    $db->delete_query("settinggroups", "name='cfpp_agreement'");
    $db->delete_query("settings", "name LIKE 'cfpp_agreement%'");

    rebuild_settings();
}

function cfpp_agreement_activate()
{
    require MYBB_ROOT . '/inc/adminfunctions_templates.php';
    find_replace_templatesets('contact', '#{\$captcha}#', "{\$captcha}\n{\$cfpp_agreement}");
}

function cfpp_agreement_deactivate()
{
    require MYBB_ROOT . '/inc/adminfunctions_templates.php';
    find_replace_templatesets('contact', '#\n{\$cfpp_agreement}#', '', 0);
}

function cfpp_agreement_settings()
{
    global $lang;
    $lang->load('cfpp_agreement', true);
}

function cfpp_agreement_peekers(&$peekers)
{
    $peekers[] = 'new Peeker($(".setting_cfpp_agreement_active"), $("#row_setting_cfpp_agreement_showgroups, #row_setting_cfpp_agreement_showlink, #row_setting_cfpp_agreement_link"), 1, true)';
    $peekers[] = 'new Peeker($(".setting_cfpp_agreement_showlink"), $("#row_setting_cfpp_agreement_link"), 1, true)';
}

function cfpp_agreement_do_start()
{
    global $mybb, $lang, $errors;

    if ($mybb->settings['cfpp_agreement_active'] != 1 || !is_member($mybb->settings['cfpp_agreement_showgroups']))
    {
        return;
    }

    if (!isset($lang->cfpp_agreement))
    {
        $lang->load('cfpp_agreement');
    }

    if ($mybb->get_input('cfpp_agreement', MyBB::INPUT_INT) != 1)
    {
        $errors[] = $lang->cfpp_agreement_error;
    }
}

function cfpp_agreement_end()
{
    global $mybb, $lang, $cfpp_agreement, $templates;
    $cfpp_agreement = '';

    if ($mybb->settings['cfpp_agreement_active'] != 1 || !is_member($mybb->settings['cfpp_agreement_showgroups']))
    {
        return;
    }

    if (!isset($lang->cfpp_agreement))
    {
        $lang->load('cfpp_agreement');
    }

    if ($mybb->settings['cfpp_agreement_showlink'] == 1 && !empty($mybb->settings['cfpp_agreement_link']))
    {
        $link = trim($mybb->settings['cfpp_agreement_link']);
        $link = $mybb->get_asset_url($link);

        if (function_exists('get_headers'))
        {
            $file_headers = @get_headers($link);
            if ($file_headers && $file_headers[0] != 'HTTP/1.1 404 Not Found')
            {
                $lang->cfpp_agreement_simple = $lang->sprintf($lang->cfpp_agreement_more, $link);
            }
        }
        else
        {
            $lang->cfpp_agreement_simple = $lang->sprintf($lang->cfpp_agreement_more, $link);
        }
    }

    eval('$cfpp_agreement = "' . $templates->get('contact_privacy_policy') . '";');
}
