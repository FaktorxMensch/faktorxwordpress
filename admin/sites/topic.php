<?php
/**
 * Schreibwerkstatt (KI-Texterstellung) -- derzeit abgeschaltet.
 *
 * Die Seite hat früher den API-Key der Website in den Browser geschrieben, damit
 * dieser direkt mit ProjectPilot sprechen konnte. Derselbe Key berechtigt aber
 * auch zu Verwaltungsaufrufen (monitor.php: Rollen ändern, Admin-Zugang
 * wiederherstellen), er darf deshalb nicht bei Kund:innen im Browser landen.
 * Bis es dafür einen eigenen, eingeschränkten Schlüssel gibt, zeigt die Seite
 * nur einen Hinweis. Die dazugehörigen REST-Routen (fxwp/v1/create_post und
 * fxwp/v1/content_render) gibt es nicht mehr.
 */

function fxwp_topic_page()
{
    // check if user is allowed access
    if (!current_user_can('manage_options')) return;

    fxwp_show_deactivated_feature_warning('fxwp_deact_ai');
    ?>
    <div class="fxm-header"
         style="background:#171717;gap:30px;display:flex;align-items:center;padding:10px 20px;color:white;width:100%;margin-left:-20px">
        <img src="https://faktorxmensch.com/image/brand/icon--dark.svg" height="70" alt="">
        <h1 style="color:white">Schreibwerkstatt</h1>
    </div>

    <div class="wrap" style="padding:20px;background:white;margin-top:20px;max-width:50em">
        <h1><?php echo esc_html__('Die Schreibwerkstatt ist derzeit nicht verfügbar', 'fxwp'); ?></h1>
        <p style="font-size:1.1em">
            <?php echo esc_html__('Wir haben die Anbindung der KI-Texterstellung umgestellt. Deshalb können hier im Moment keine Texte erstellt werden.', 'fxwp'); ?>
        </p>
        <p style="font-size:1.1em">
            <?php echo esc_html__('Sie möchten die Schreibwerkstatt weiter nutzen? Dann melden Sie sich bitte kurz bei uns – wir richten sie für Sie wieder ein.', 'fxwp'); ?>
        </p>
        <p>
            <a href="https://faktorxmensch.com/support" target="_blank" rel="noopener"
               class="button button-primary"><?php echo esc_html__('Support kontaktieren', 'fxwp'); ?> &rarr;</a>
        </p>
    </div>
    <?php
}
