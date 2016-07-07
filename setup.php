<?php

add_action('init', 'wgi_create_post_type');

function wgi_create_post_type()
{
    register_post_type(
        WGI_POST_TYPE,
        array(
            'labels' => array(
                'name' => __('Commits'),
                'singular_name' => __('Commit')
            ),
            'public' => true,
            'has_archive' => true,
            'taxonomies' => array('category', 'post_tag'),
        )
    );
}

add_action('init', 'wgi_create_repo_tax');

function wgi_create_repo_tax()
{
    register_taxonomy(
        WGI_HASHTAG_TAX,
        WGI_POST_TYPE,
        array(
            'label' => __('Git Repo'),
            'hierarchical' => true,
        )
    );
}

add_filter('cron_schedules', 'wgi_add_cron_schedule');
 
function wgi_add_cron_schedule($schedules)
{
    if (!isset($schedules[WGI_SCHEDULE])) {
        $schedules[WGI_SCHEDULE] = array(
            'interval' => 300,
            'display'  => esc_html__('Every Five Minutes'),
        );
    }
 
    return $schedules;
}
