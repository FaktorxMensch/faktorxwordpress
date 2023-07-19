<?php

function fxwp_description_widget()
{

    if (isset($_POST['fxwp_website_description'])) {
        $website_description = sanitize_text_field($_POST['fxwp_website_description']);
        update_option('fxwp_website_description', $website_description);

        $url = FXWP_API_URL . '/' . get_option('fxwp_api_key') . '/openai';

        // Prepare the body data. For example, we'll pass the description.
        $body = array(
            'website_description' => $website_description,
        );

        // We will also need to add 'body' to our array with data.
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'timeout' => 45,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers' => array(),
            'body' => $body,
            'cookies' => array()
        ));

        // Check for any errors
        if (is_wp_error($response)) {
            return; // Or handle the error in a way that suits your needs
        }

        // output the response
//        $response_body = wp_remote_retrieve_body($response);
//        $response_body = json_decode($response_body, true);
//        print_r($response_body);

    }

    $description = get_option('fxwp_website_description', false);
    $topics = get_option('fxwp_openai_topics', []);

    if ($description === false || isset($_GET['fxwp_website_description_edit'])) {
        echo '<form method="POST" action="?refresh_topics">';
        echo '<b>WICHTIG:</b> Bitte beschreiben Sie Ihr Unternehmen in einem Satz. Dieser Satz wird zur Suchmaschinenoptimierung verwendet. Bitte beschreiben Sie alle Inhalte Ihrer Website in diesem Satz. Dieser Satz wird auch verwendet, um die Themen zu generieren, die Sie in Ihrem Blog behandeln sollten.<br>';

        echo '<textarea placeholder="Schreiben Sie ein bis zwei Sätze" name="fxwp_website_description" rows="4" style="margin-top:10px;width:100%"></textarea>';
        echo '<input type="submit" value="Beschreibung speichern" class="button button-primary" style="width:100%">';
        echo '</form>';
    } else {

        if (empty($topics) || isset($_GET['refresh_topics'])) {
            $topics = fxwp_generate_topics($description);
            update_option('fxwp_openai_topics', $topics);
        }

        if (!empty($topics)) {
            echo '<p>Klicken Sie, um einen Blogbeitrag zu einem Thema zu generieren:</p>';
            echo '<ul style="margin-left:15px;list-style:disc">';
            foreach ($topics as $topic) {
<<<<<<< HEAD
                echo '<li><a class="topic full-block" href="topic.php?topic=' . urlencode($topic) . '">' . esc_html($topic) . '</a></li>';
=======
                echo '<li><a class="topic-title" href="admin.php?page=fxwp-topic-page&topic=' . urlencode($topic) . '">' . esc_html($topic) . '</a></li>';
>>>>>>> 17a3786 (add plugin)
            }
            echo '</ul>';
        }

        echo '<a href="?refresh_topics" style="text-decoration: none"><button class="button button-primary" style="width:100%;justify-content:center;text-align:center;display:flex;gap:4px;align-items: center" id="refresh_topics"><span class="dashicons dashicons-update"></span> ' . esc_html__('Neue Themen vorschlagen', 'fxwp') . '</button></a>';

        // fxwp_website_description_edit
        echo '<a href="?fxwp_website_description_edit" style="text-decoration: none"><button class="button" style="width:100%;justify-content:center;text-align:center;display:flex;gap:4px;align-items: center;margin-top:5px" id="fxwp_website_description_edit"><span class="dashicons dashicons-edit"></span> ' . esc_html__('Seiten-Beschreibung bearbeiten', 'fxwp') . '</button></a>';


        // have line with text ODER on it
        echo '<div style="display:flex;align-items:center;justify-content:center;margin-top:10px"><div style="width:100%;height:1px;background-color:#ddd"></div><div style="margin:0 10px">ODER</div><div style="width:100%;height:1px;background-color:#ddd"></div></div>';

        echo '
        <form method="GET" action="admin.php">
        <input type="hidden" name="page" value="fxwp-topic-page">
        <textarea class="topic full-block" type="text" id="topic" name="topic" placeholder="Geben Sie ein eigenes Thema ein ..." style="margin-top:10px;width:100%"></textarea>
        <button class="button button-primary" style="width:100%;margin-top:5px;justify-content:center;text-align:center;display:flex;gap:4px;align-items: center " id="generate_topic">
        Post zu diesem Thema generieren
        <span class="dashicons dashicons-controls-play"></span>
        </button>
        </form>
        ';

    }
}

function fxwp_generate_topics($description)
{
    // Initialize the API endpoint
    $url = FXWP_API_URL . '/' . get_option('fxwp_api_key') . '/blog/topics';

    // Use the WordPress HTTP API to make the request
    $response = wp_remote_get($url);

    // Check for any errors
    if (is_wp_error($response)) {
        return; // Or handle the error in a way that suits your needs
    }

    // Decode the JSON response
    $raw = wp_remote_retrieve_body($response);
    $data = json_decode($raw, true);

    // Check the JSON decoded successfully and that 'topics' is an array
    if ($data === null || !is_array($data['topics'])) {
        echo 'Error: Invalid response from API';
        return; // Or handle the error in a way that suits your needs
    }

    // Update the fxwp_openai_topics option
//    update_option('fxwp_openai_topics', $data['topics']);

    echo "<div class='notice notice-success is-dismissible'><p>Topics wurden erfolgreich aktualisiert.</p></div>";

    return $data['topics'];
}


function fxwp_register_description_widget()
{
    if (!current_user_can('manage_options')) {
        return;
    }

    wp_add_dashboard_widget(
        'fxwp_description_widget', // Widget slug.
        'Blog Themen', // Title.
        'fxwp_description_widget' // Display function.
    );
}

add_action('wp_dashboard_setup', 'fxwp_register_description_widget');
