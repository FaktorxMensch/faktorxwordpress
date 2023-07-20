<?php

// Display the plugin settings page
function fxwp_topic_page()
{

    // check if user is allowed access
    if (!current_user_can('manage_options')) return;

    ?>

    <div class="fxm-header"
         style="background:#171717;gap:30px;display:flex;align-items:center;padding:10px 20px;color:white;width:100%;margin-left:-20px">
        <img src="https://faktorxmensch.com/image/brand/icon--dark.svg" height="70">
        <h1 style="color:white">Schreibwerkstatt</h1>
        <div style="flex:2"></div>
        <a href="https://faktorxmensch.com" target="_blank"
           style="background:#E3A355;padding:7px 11px;margin-right:20px;text-decoration:none;border-radius:10px;color:white;text-transform:uppercase;display:inline-block">Kontakt
            aufnehmen</a>
    </div>

    <script src="https://unpkg.com/vue@3/dist/vue.global.js"></script>
    <div class="wrap" style="padding: 10px 20px;background:white;margin-top:20px;padding-bottom:20px">

        <div id="schreibwerkstatt-app">

            <h1 style="display:block; margin-bottom:30px;margin-top:10px;text-align:center" v-if="loading">
                Schreibwerkstatt</h1>
            <h1 v-else-if="error.length==0"><?php echo sanitize_text_field($_GET['topic']); ?></h1>

            <div v-if="loading" style="display:flex;justify-content:center;align-items:center;flex-direction:column">
                <div class="loader"></div>
                <br>
                <p style="max-width:30em;text-align:center;">Generierung der Inhalte. Dies kann bis zu 5 Minuten dauern, da die Inhalte von einer Künstlichen Intelligenz generiert werden.</p>
                    <b>Bitte schließen Sie die Seite nicht während dieses Prozesses.</b></p>
            </div>
            <div v-else-if="error.length>0">
                <h2>Es ist ein Fehler aufgetreten</h2>
                <p>{{ error }}</p>
                <a href="index.php?" class="button button-secondary">&larr; Zurück</a>&nbsp;
                <a href="https://faktorxmensch.com/support" target="_blank" class="button button-primary">Support kontaktieren &rarr;</a>
            </div>
            <div v-else>
                <label>
                    Titel:
                    <input v-model="title" name="title" type="text" style="width:100%;" class="regular-text">
                </label>
                <br/>
                <br/>
                <label>
                    Bitte wählen Sie ein Bild aus:
                </label>
                <div class="image-gallery" style="margin-top:5px;">
                    <div v-for="image in images" class="img-container"
                         :class="{ selected: selectedImage === image.largeImageURL }">
                        <input v-model="selectedImage" type="radio" :value="image.largeImageURL" style="display: none">
                        <img :src="image.previewURL" @click="selectedImage = image.largeImageURL">
                    </div>
                </div>
                <button
                    class="button button-primary"
                    style="display:flex;gap:4px;align-items: center"
                    @click="submit">Blog Beitrag erstellen
                    <span class=" dashicons dashicons-arrow-right-alt2"></span>
                </button>
            </div>
        </div>

    </div>

    <script>
        const {createApp} = Vue
        createApp({
            data: () => ({
                title: '',
                content: '',
                selectedImage: '',
                images: [],
                loading: true,
                error: '',
                url: '<?php echo FXWP_API_URL; ?>' + '/' + '<?php echo get_option('fxwp_api_key'); ?>' + '/blog/topic'
            }),
            methods: {
                submit() {
                    this.loading = true
                    fetch('<?php echo rest_url('fxwp/v1/create_post'); ?>', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            title: this.title,
                            content: this.content,
                            image_url: this.selectedImage
                        })
                    })
                        .then(response => response.json())
                        .then(data => {
                            window.location.href = '<?php echo admin_url('post.php'); ?>?post=' + data + '&action=edit';
                        });
                }
            },
            created() {
                fetch(this.url, {
                    method: "POST",
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({topic: '<?php echo sanitize_text_field($_GET['topic']); ?>'})
                }).then(response => response.json())
                    .then((res) => {

                        console.log(this.error)

                        if(res.error) {
                            this.loading = false;
                            alert(res.error);
                            this.error = res.error;
                            return;
                        }

                        const post = res.post;

                        this.images = post.pixabay_images;
                        this.title = post.post_title;
                        this.content = post.post_content;
                        this.loading = false;

                        // choose random image
                        this.selectedImage = this.images[Math.floor(Math.random() * this.images.length)].largeImageURL;

                    })
            }
        }).mount('#schreibwerkstatt-app')
    </script>
    <style>
        .img-container {
            width: 120px;
            height: 120px;
            position: relative;
            border: 2px solid transparent;
            background-color: white;
            border-radius: 5px;
            overflow: hidden;
            margin-bottom: 10px;
        }

        .img-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            position: absolute;
        }

        .img-container:hover {
            cursor: pointer;
            border-color: #ddd;
        }

        .img-container.selected {
            border-color: #fff;
            box-shadow: 0 0 0 2px #E3A355;
        }

        .image-gallery {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            /*justify-content: center;*/
        }

        .loader {
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #E3A355;
            width: 120px;
            height: 120px;
            -webkit-animation: spin 2s linear infinite; /* Safari */
            animation: spin 2s linear infinite;
        }

        /* Safari */
        @-webkit-keyframes spin {
            0% {
                -webkit-transform: rotate(0deg);
            }
            100% {
                -webkit-transform: rotate(360deg);
            }
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }
            100% {
                transform: rotate(360deg);
            }
        }

        .button.button-primary {
            background-color: #E3A355;
            border-color: #E3A355;
        }
        .button.button-primary:hover {
            background-color: #c78b42;
            border-color: #c78b42;
        }
    </style>

    <!---->
    <!--    <div id="app">{{ message }}</div>-->
    <!---->
    <!--    <script>-->
    <!--        createApp({-->
    <!--            data() {-->
    <!--                return {-->
    <!--                    message: 'Hello Vue!'-->
    <!--                }-->
    <!--            }-->
    <!--        }).mount('#app')-->
    <!--    </script>-->
    <?php

}

function fxwp_content_render($request)
{

    $content = $request->get_param('content');
    // return the_cotent applied to the content
    return apply_filters('the_content', $content);

}

function fxwp_create_post($request)
{
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $title = $request->get_param('title');
    $content = $request->get_param('content');
    $image_url = $request->get_param('image_url');

    $new_post = array(
        'post_title' => wp_strip_all_tags($title),
        'post_content' => $content,
        'post_status' => 'draft',
        'post_type' => 'post'
    );


    // Insert the post into the database
    $post_id = wp_insert_post($new_post);

    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $result = media_sideload_image($image_url, $post_id, $title);
    if (is_wp_error($result)) {
        error_log($result->get_error_message());
        return $result;
    }

    // Download and attach the image to the post
    $attachments = get_posts(array('numberposts' => '1', 'post_parent' => $post_id, 'post_type' => 'attachment', 'post_mime_type' => 'image', 'orderby' => 'post_date', 'order' => 'DESC'));
    if (sizeof($attachments) > 0) {
        set_post_thumbnail($post_id, $attachments[0]->ID);
    }

    // Return the post ID
    return $post_id;
}

add_action('rest_api_init', function () {
    register_rest_route('fxwp/v1', '/create_post', array(
        'methods' => 'POST',
        'callback' => 'fxwp_create_post',
        'args' => array(
            'title' => array(
                'required' => true
            ),
            'content' => array(
                'required' => true
            ),
            'image_url' => array(
                'required' => true
            )
        )
    ));
    register_rest_route('fxwp/v1', '/content_render', array(
        'methods' => 'POST',
        'callback' => 'fxwp_content_render',
        'args' => array(
            'content' => array(
                'required' => true
            ),
        )
    ));
});
