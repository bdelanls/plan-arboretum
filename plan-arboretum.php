<?php
/**
 * Plugin Name: Plan Arboretum
 * Description: Gère la carte interactive des arbres de l'arboretum : conversion de coordonnées, génération de fichier JSON, affichage sur une carte, etc.
 * Version: 1.8
 * Author: Bertrand Delanlssays
 * Author URI: https://www.bdelanls.fr
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: plan-arboretum
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) exit;

require_once plugin_dir_path(__FILE__) . 'inc/functions.php';


/**
 * Enqueue les styles et scripts nécessaires pour le plan de l'arboretum.
 *
 * Cette fonction charge les fichiers CSS et JS de Leaflet ainsi que le script personnalisé
 * pour la carte, mais seulement si le shortcode [plan_arboretum] est utilisé dans le contenu.
 */
function plan_arboretum_enqueue_assets() {
    // Charger les fichiers seulement sur les pages où le shortcode est utilisé
    global $post;
    if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'plan_arboretum')) {

        wp_enqueue_style(
            'plan-arboretum-leaflet-css',
            plugin_dir_url(__FILE__) . 'leaflet/leaflet.css'
        );

        wp_enqueue_script(
            'plan-arboretum-leaflet-js',
            plugin_dir_url(__FILE__) . 'leaflet/leaflet.js',
            [],
            null,
            true
        );

        wp_enqueue_script(
            'carte-arboretum',
            plugin_dir_url(__FILE__) . 'js/carte.js',
            ['plan-arboretum-leaflet-js'], // dépendance à Leaflet
            null,
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'plan_arboretum_enqueue_assets');

/**
 * Shortcode pour afficher la carte de l'arboretum.
 *
 * @param array $atts Attributs du shortcode
 * @return string HTML du conteneur de la carte
 */
function afficher_plan_arboretum($atts) {
    // Paramètres avec valeurs par défaut sensées
    $atts = shortcode_atts([
        'height' => '500px',
        'width' => '100%',
        'zoom' => '15'
    ], $atts, 'plan_arboretum');

    // Générer un ID unique pour cette instance
    static $map_counter = 0;
    $map_counter++;
    $map_id = 'map-arboretum-' . $map_counter;

    // Configuration minimale pour le JavaScript
    $config = [
        'jsonUrl' => wp_upload_dir()['baseurl'] . '/carte-data/arbres.json',
        'zoom' => intval($atts['zoom'])
    ];

    wp_enqueue_script('carte-arboretum', plugin_dir_url(__FILE__) . 'js/carte.js', ['plan-arboretum-leaflet-js'], null, true);
    wp_localize_script('carte-arboretum', 'carteConfig_' . $map_counter, $config);

    return sprintf(
        '<div id="%s" class="carte-arboretum" style="width: %s; height: %s;" data-config="carteConfig_%d"></div>',
        esc_attr($map_id),
        esc_attr($atts['width']),
        esc_attr($atts['height']),
        $map_counter
    );
}
add_shortcode('plan_arboretum', 'afficher_plan_arboretum');


