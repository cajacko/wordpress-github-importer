<?php

add_action('admin_menu', 'wgi_admin_add_page');

function wgi_admin_add_page()
{
    add_options_page(WGI_PLUGIN_NAME, WGI_PLUGIN_NAME, 'manage_options', WGI_PLUGIN_ID, 'wgi_options_page');
}

function wgi_options_page()
{
    ?>
    <div>
        <form action="options.php" method="post">
            <?php settings_fields(WGI_OPTIONS_SLUG); ?>
            <?php do_settings_sections(WGI_PLUGIN_ID); ?>
             
            <input name="Submit" type="submit" value="<?php esc_attr_e('Save Changes'); ?>" />
        </form>

        <h2>Actions</h2>

        <form action="" method="POST">
            <input type="hidden" name="<?php echo WGI_ACTION_LATEST; ?>" value="true" />
            <input type="submit" value="<?php esc_attr_e('Get Latest Commits'); ?>" />
        </form>

        <form action="" method="POST">
            <input type="hidden" name="<?php echo WGI_ACTION_OLDER; ?>" value="true" />
            <input type="submit" value="<?php esc_attr_e('Get Older Commits'); ?>" />
        </form>
    </div>
    <?php
}

add_action('admin_init', 'wgi_admin_init');

function wgi_admin_init()
{
    register_setting(WGI_OPTIONS_SLUG, WGI_OPTIONS_SLUG, 'wgi_options_validate');
    add_settings_section(WGI_OPTIONS_SECTION, WGI_PLUGIN_NAME, 'wgi_section_text', WGI_PLUGIN_ID);
    add_settings_field(WGI_GITHUB_USER, 'Github Username', 'wgi_key_setting_string', WGI_PLUGIN_ID, WGI_OPTIONS_SECTION);
    add_settings_field(WGI_GITHUB_PASSWORD, 'Github Password', 'wgi_secret_setting_string', WGI_PLUGIN_ID, WGI_OPTIONS_SECTION);
    add_settings_field(WGI_WHITELIST, 'Whitelist Users (comma separated)', 'wgi_whitelist_setting_string', WGI_PLUGIN_ID, WGI_OPTIONS_SECTION);
    add_settings_field(WGI_COMMITS_FROM, 'Get commits from', 'wgi_commits_from_setting_string', WGI_PLUGIN_ID, WGI_OPTIONS_SECTION);
}

function wgi_section_text()
{
    echo '<p>Add in your Github details here.</p>';
}

function wgi_key_setting_string()
{
    $options = get_option(WGI_OPTIONS_SLUG);
    echo "<input id='" . WGI_GITHUB_USER . "' name='" . WGI_OPTIONS_SLUG . "[" . WGI_GITHUB_USER . "]' size='40' type='text' value='{$options[WGI_GITHUB_USER]}' />";
}

function wgi_commits_from_setting_string()
{
    $options = get_option(WGI_OPTIONS_SLUG);
    echo "<input id='" . WGI_COMMITS_FROM . "' name='" . WGI_OPTIONS_SLUG . "[" . WGI_COMMITS_FROM . "]' size='40' type='text' value='{$options[WGI_COMMITS_FROM]}' />";
}

function wgi_secret_setting_string()
{
    $options = get_option(WGI_OPTIONS_SLUG);
    echo "<input id='" . WGI_GITHUB_PASSWORD . "' name='" . WGI_OPTIONS_SLUG . "[" . WGI_GITHUB_PASSWORD . "]' size='40' type='text' value='{$options[WGI_GITHUB_PASSWORD]}' />";
}

function wgi_whitelist_setting_string()
{
    $options = get_option(WGI_OPTIONS_SLUG);
    echo "<input id='" . WGI_WHITELIST . "' name='" . WGI_OPTIONS_SLUG . "[" . WGI_WHITELIST . "]' size='40' type='text' value='{$options[WGI_WHITELIST]}' />";
}

function wgi_options_validate($input)
{
    return $input;
}

function wgi_get_options()
{
    $options = get_option(WGI_OPTIONS_SLUG);

    if (!isset($options[WGI_GITHUB_USER])) {
        return false;
    }

    if (!isset($options[WGI_GITHUB_PASSWORD])) {
        return false;
    }

    if (strlen($options[WGI_GITHUB_USER]) < 5) {
        return false;
    }

    if (strlen($options[WGI_GITHUB_PASSWORD]) < 5) {
        return false;
    }

    return $options;
}
