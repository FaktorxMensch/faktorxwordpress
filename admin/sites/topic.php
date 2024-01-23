<?php

// Display the plugin settings page
function fxwp_topic_page()
{

    // check if user is allowed access
    if (!current_user_can('manage_options')) return;

    fxwp_show_deactivated_feature_warning('fxwp_deact_ai');

    $subnav_menu = [
        [
            "title" => "E-Mail Kampagnen",
            "slug" => "email-kampagnen",
        ],
        [
            "title" => "Blogartikel",
            "slug" => "blogartikel",
        ]
    ];

    // find  current subnav via $_GET from array
    $subnav = array_filter($subnav_menu, function ($item) {
        if (!isset($_GET['subnav'])) return false;
        return $item['slug'] == $_GET['subnav'];
    });

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

    <?php
    if ($subnav) {
        ?>
        <div class="breadcrumb" style="margin-top:20px">
            <a href="admin.php?page=fxwp-topic-page" class="button button-secondary">&larr; Zurück</a>
        </div>
        <?php

    }
    ?>

    <div class="wrap" style="padding: 10px 20px;background:white;margin-top:20px;padding-bottom:20px">

        <?php
        if (!$subnav && !isset($_GET['topic'])) {
            ?>
            <div class="fxm-subnav" style="margin-top:20px;">
                <h1>Bitte Assistenten auswählen:</h1>
                <p>Wählen Sie einen Assistenten aus, der Ihnen bei der Erstellung von
                    Inhalten hilft.</p>
                <div style="display:flex;flex-wrap:wrap;flex-direction: column;gap:10px">
                    <?php
                    foreach ($subnav_menu as $item) {
                        ?>
                        <a href="admin.php?page=fxwp-topic-page&subnav=<?php echo $item['slug']; ?>"
                           class="button button-secondary"><?php echo $item['title']; ?></a>
                        <?php
                    }
                    ?>
                </div>
            </div>
            <?php
        }
        ?>


        <?php
        // handle subnav
        if (@$_GET["subnav"] == "email-kampagnen") {
            ?>
            <div id="app">

                <div v-if="loading"
                     style="display:flex;justify-content:center;align-items:center;flex-direction:column;max-width:90%;margin:auto;">
                    <div class="loader"></div>
                    <br>
                    <p style="max-width:30em;text-align:center;">Generierung der Inhalte. Dies kann 1 - 2 Minuten
                        dauern, da die Inhalte von einer Künstlichen Intelligenz generiert werden.</p>
                    <b>Bitte schließen Sie die Seite nicht während dieses Prozesses.</b></p>
                </div>

                <template v-else-if="generated.length > 0">
                    <div v-html="generated" :style="!generated.includes('<') ? 'white-space:pre-wrap' : ''"
                         class="good-prose"></div>
                </template>
                <template v-else>
                    <h1>E-Mail Kampagnen</h1>
                    <p>Erstellen Sie eine E-Mail Kampagne mit dem Assistenten.</p>
                    <div class="form-item">
                        <label for="thema">Thema:</label>
                        <textarea id="thema" v-model="thema"
                                  placeholder="Zum Beispiel: 'Diese Woche sind Summer Sales für alle Luftmatratzen'"></textarea>
                    </div>

                    <div class="form-item">
                        <label for="schluesselwoerter">Schlüsselwörter:</label>
                        <input id="schluesselwoerter" v-model="schluesselwoerter" type="text"
                               placeholder="Zum Beispiel: 'Sommerverkauf', '30% Rabatt', 'Neue Kollektion'">
                    </div>

                    <div class="form-item">
                        <label for="stil">Stil:</label>
                        <select id="stil" v-model="stil">
                            <option v-for="option in stile" v-bind:value="option">
                                {{ option}}
                            </option>
                        </select>
                    </div>

                    <div class="form-item">
                        <label for="laenge">Länge:</label>
                        <select id="laenge" v-model="laenge">
                            <option v-for="option in laengen" v-bind:value="option">
                                {{ option}}
                            </option>
                        </select>
                    </div>

                    <div class="form-item">
                        <button @click="submit">Text generieren</button>
                    </div>
                </template>
            </div>
            <script>
                const {createApp} = Vue
                createApp({
                    data: () => ({
                        url: '<?php echo FXWP_API_URL; ?>' + '/' + '<?php echo get_option('fxwp_api_key'); ?>' + '/email-kampagnen',
                        thema: 'Diese Woche sind Summer Sales für alle Luftmatratzen',
                        schluesselwoerter: 'Sommerverkauf, 30% Rabatt, Neue Kollektion',
                        stil: 'Überzeugend',
                        loading: false,
                        laenge: 'Kurz',
                        stile: [
                            "Überzeugend",
                            "Informativ",
                            "Emotional",
                            "Humorvoll",
                            "Prägnant",
                            "Provokativ",
                            "Kreativ",
                            "Storytelling",
                            "Call-to-Action",
                            "Markenbildend"
                        ],
                        laengen: [
                            "Kurz",
                            "Mittel",
                            "Lang"
                        ],
                        generated: '',
                    }),

                    methods: {
                        submit() {
                            this.loading = true;
                            fetch(this.url, {
                                method: "POST",
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    thema: this.thema,
                                    schluesselwoerter: this.schluesselwoerter,
                                    stil: this.stil,
                                    laenge: this.laenge,
                                })
                            }).then(response => response.json())
                                .then((res) => {

                                    console.log(this.error)

                                    if (res.error) {
                                        this.loading = false;
                                        alert(res.error);
                                        this.error = res.error;
                                        return;
                                    }

                                    this.generated = res.generated;
                                    this.loading = false;

                                })
                                .catch((error) => {
                                    alert(error)
                                    console.error('Error:', error);
                                });
                        },
                    },
                }).mount('#app')
            </script>
        <?php
        } else if (@$_GET["subnav"] == "blogartikel" || isset($_GET['topic'])) {
        if (!isset($_GET['topic'])) {
        ?>
            <div class="fxm-subnav" style="margin-top:20px;">
                <h1>Bitte geben Sie ein Thema vor:</h1>
                <p>Wählen Sie ein Thema aus, zu dem Sie einen Blogartikel erstellen möchten.</p>
                <form action="admin.php" method="get">
                    <input type="hidden" name="page" value="fxwp-topic-page">
                    <input type="hidden" name="subnav" value="blogartikel">
                    <input type="text" name="topic" placeholder="Titel des Artikel eingeben"
                           style="width:100%;margin-bottom:10px">
                    <input type="submit" value="Neuen Artikel generieren" class="button button-secondary">
                </form>
            </div>
        <?php
        } else {
        ?>
            <div id="schreibwerkstatt-app">

                <h1 style="display:block; margin-bottom:30px;margin-top:10px;text-align:center" v-if="loading">
                    Schreibwerkstatt</h1>
                <h1 v-else-if="error.length==0"><?php echo sanitize_text_field($_GET['topic']); ?></h1>

                <div v-if="loading"
                     style="display:flex;justify-content:center;align-items:center;flex-direction:column">
                    <div class="loader"></div>
                    <br>
                    <p style="max-width:30em;text-align:center;">Generierung der Inhalte. Dies kann bis zu 5 Minuten
                        dauern, da die Inhalte von einer Künstlichen Intelligenz generiert werden.</p>
                    <b>Bitte schließen Sie die Seite nicht während dieses Prozesses.</b></p>
                </div>
                <div v-else-if="error.length>0">
                    <h2>Es ist ein Fehler aufgetreten</h2>
                    <p>{{ error }}</p>
                    <p>Nachfolgend der Fehlercode:</p>
                    <textarea>{{errorDetails}}</textarea>
                    <a href="index.php?" class="button button-secondary">&larr; Zurück</a>&nbsp;
                    <a href="https://faktorxmensch.com/support" target="_blank" class="button button-primary">Support
                        kontaktieren &rarr;</a>
                </div>
                <div v-else>
                    <label>
                        Titel:
                        <input v-model="title" name="title" type="text" style="width:100%;" class="regular-text">
                    </label>
                    <template  v-if="typeof images !== 'undefined' && images.length > 0">
                    <br/>
                    <br/>
                    <label>
                        Bitte wählen Sie ein Bild aus:
                    </label>
                    <div class="image-gallery" style="margin-top:5px;">
                        <div v-for="image in images" class="img-container"
                             :class="{ selected: selectedImage === image.largeImageURL }">
                            <input v-model="selectedImage" type="radio" :value="image.largeImageURL"
                                   style="display: none">
                            <img :src="image.previewURL" @click="selectedImage = image.largeImageURL">
                        </div>
                    </div></template>
                    <template v-else>
                        <div class="good-prose" v-html="content"></div>
                    </template>
                    <button
                        class="button button-primary"
                        style="display:flex;gap:4px;align-items: center"
                        @click="submit">Blog Beitrag erstellen
                        <span class=" dashicons dashicons-arrow-right-alt2"></span>
                    </button>
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
                        errorDetails:'',
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
                                let post = res.post;
                                if (res.error) {
                                    this.loading = false;
                                    if(res.error !== 'Could not parse response')
                                        alert(res.error);
                                    try {
                                        console.log('api had error but gave textResponse', res)
                                        this.errorDetails = res.textResponse;
                                        post = JSON.parse(res.textResponse);
                                    } catch (e) {
                                        this.error = res.error;
                                        console.log('eror in eror', e)
                                        return;
                                    }
                                }

                                // const post = res.post;

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
        <?php }
        } ?>

    </div>

    <style>
        .form-container {
            width: 100%;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f9f9f9;
            border-radius: 8px;
            font-family: Arial, sans-serif;
        }

        .form-item {
            margin-bottom: 20px;
        }

        .form-item label {
            display: block;
            margin-bottom: 10px;
            font-weight: bold;
        }

        .form-item input, .form-item textarea, .form-item select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-item button {
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .form-item button:hover {
            background-color: #0056b3;
        }

        .good-prose {
            word-wrap: break-word;
            max-width: 40rem;
        }

        .good-prose p {
            font-size: 1rem;
        }

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
