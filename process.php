<?php

// TODO: pagination

require_once(WGI_PLUGIN_PATH . 'vendor/autoload.php');

if (!wp_next_scheduled(WGI_CRON)) {
    wp_schedule_event(time(), WGI_SCHEDULE, WGI_CRON);
}

add_action(WGI_CRON, 'wgi_cron');

function wgi_cron()
{
    wgi_get_commits();
}

function wgi_run_action()
{
    if (!is_user_logged_in()) {
        return false;
    }

    if (isset($_POST[WGI_ACTION_LATEST])) {
        wgi_get_commits();
    }

    if (isset($_POST[WGI_ACTION_OLDER])) {
        wgi_get_commits(false);
    }
}

add_action('init', 'wgi_run_action');


function wgi_connect($options)
{
    $client = new GitHubClient();
    $client->setCredentials($options[WGI_GITHUB_USER], $options[WGI_GITHUB_PASSWORD]);
    return $client;
}

function wgi_get_repo_term($name, $slug, $description, $url)
{
    $term = get_term_by('slug', $slug, WGI_HASHTAG_TAX);

    if ($term) {
        return $term->term_id;
    } else {
        $args = array(
            'description' => $description,
            'slug' => $slug,
        );

        $term = wp_insert_term($name, WGI_HASHTAG_TAX, $args);
        update_term_meta($term['term_id'], 'url', $url);
        update_term_meta($term['term_id'], 'github_id', $slug);
        return $term['term_id'];
    }
}

function wgi_process_repos($options, $client, $last = true)
{
    // $client->setPage();
    // $client->setPageSize(2);
    $repos = $client->repos->listUserRepositories($options[WGI_GITHUB_USER]);

    foreach ($repos as $repo) {
        $repo_name = $repo->getName();
        $repo_id = $repo->getId();
        $repo_url = $repo->getHtmlUrl();
        $repo_description = $repo->getDescription();

        $repo_term_id = wgi_get_repo_term($repo_name, $repo_id, $repo_description, $repo_url);

        wgi_process_branches($options, $client, $repo_name, $repo_term_id);
    }
}

function wgi_process_branches($options, $client, $repo_name, $repo_term_id, $last = true)
{
    $branches = $client->repos->listBranches($options[WGI_GITHUB_USER], $repo_name);

    foreach ($branches as $branch) {
        $branch_name = $branch->getName();
        
        wgi_process_commits($options, $client, $repo_name, $branch_name, $repo_term_id);
    }
}

function wgi_does_commit_sha_exist($sha)
{
    $args = array(
        'meta_key' => WTI_META_COMMIT_SHA,
        'meta_value' => $sha,
        'post_type' => WGI_POST_TYPE,
        'post_status' => 'any',
    );

    $posts = get_posts($args);

    if (count($posts) === 0) {
        return false;
    } else {
        return $posts[0]->ID;
    }
}

function wgi_limit_string_len($x, $length)
{
    if (strlen($x) <= $length) {
        return $x;
    } else {
        $y = substr($x, 0, $length) . '...';
        return $y;
    }
}

function wgi_get_commit($repo_term_id, $branch, $last = true)
{
    $args = array(
        'post_type' => WGI_POST_TYPE,
        'post_status' => 'any',
        'orderby' => 'date',
        'tax_query' => array(
            array(
                'taxonomy' => WGI_HASHTAG_TAX,
                'field'    => 'term_id',
                'terms'    => $repo_term_id,
            ),
        ),
        'meta_key' => WTI_META_BRANCH,
        'meta_value' => $branch,

    );

    if ($last) {
        $args['order'] = 'DESC';
        $operator = '+';
    } else {
        $args['order'] = 'ASC';
        $operator = '-';
    }

    $posts = get_posts($args);

    if (count($posts) === 0) {
        return null;
    } else {
        $post_date = date('c', strtotime($posts[0]->post_date . ' ' . $operator . '  1 sec'));
        return $post_date;
    }
}

function wgi_is_valid_author($options, $email)
{
    $whitelist = explode(',', $options[WGI_WHITELIST]);

    $whitelist = array_map('trim', $whitelist);

    if (in_array($email, $whitelist)) {
        return true;
    }

    return false;
}

function wgi_get_defined_from_date($options)
{
    if (!isset($options[WGI_COMMITS_FROM])) {
        return null;
    }

    if ('' == $options[WGI_COMMITS_FROM]) {
        return null;
    }

    if ($timestamp = strtotime($options[WGI_COMMITS_FROM])) {
        return date('c', $timestamp);
    }

    return null;
}

function wgi_process_commits($options, $client, $repo_name, $branch_name, $repo_term_id, $last = true)
{
    $since = null;
    $until = null;

    if ($last) {
        $since = wgi_get_commit($repo_term_id, $branch_name, $last);
        $since_defined = wgi_get_defined_from_date($options);

        if (!$since) {
            $since = $since_defined;
        } elseif ($since_defined) {
            if (strtotime($since) < strtotime($since_defined)) {
                $since = $since_defined;
            }
        }
    } else {
        $until = wgi_get_commit($repo_term_id, $branch_name, $last);
    }

    $commits = $client->repos->commits->listCommitsOnRepository(
        $options[WGI_GITHUB_USER],
        $repo_name,
        $branch_name,
        null,
        null,
        $since,
        $until
    );

    foreach ($commits as $commit) {
        $commit_data = $commit->getCommit();
        $author = $commit_data->getAuthor();
        $email = $author->getEmail();
        $name = $author->getName();

        if (!wgi_is_valid_author($options, $email)) {
            continue;
        }

        $post_data = array(
            'post_status' => 'publish',
            'post_type' => WGI_POST_TYPE,
        );

        $commit_sha = $commit->getSha();
        $post_data['meta_input'][WTI_META_COMMIT_SHA] = $commit_sha;

        if ($post_id = wgi_does_commit_sha_exist($commit_sha)) {
            continue;
        }

        $post_data['meta_input']['url'] = $commit->getHtmlUrl();
        $post_data['meta_input'][WTI_META_BRANCH] = $branch_name;
        $post_data['meta_input']['email'] = $email;
        $post_data['meta_input']['user'] = $name;
        
        $date = $author->getDate();
        $commit_date = date('Y-m-d H:i:s', strtotime($date));
        $post_data['post_date'] = $commit_date;

        $commit_message = $commit_data->getMessage();
        $post_data['post_content'] = $commit_message;

        $title = wgi_limit_string_len($commit_message, 75);
        $post_data['post_title'] = $title;

        $post_data['tax_input'][WGI_HASHTAG_TAX] = array($repo_term_id);

        $post_id = wp_insert_post($post_data);
    }
}

function wgi_get_commits($last = true)
{
    $options = wgi_get_options();
    $client = wgi_connect($options);
    wgi_process_repos($options, $client, $last);
}
