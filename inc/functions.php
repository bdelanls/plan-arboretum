<?php

require_once plugin_dir_path(__FILE__) . 'proj4php/proj4php-autoload.php';

use proj4php\Proj4php;
use proj4php\Proj;
use proj4php\Point;


/**
 * Convertit les coordonnées de la projection RGF93 CC44 (EPSG:3944) vers la projection WGS84 (EPSG:4326).
 *
 * Cette fonction utilise la bibliothèque Proj4php pour effectuer la transformation des coordonnées.
 *
 * @param float $x La coordonnée X dans la projection RGF93 CC44.
 * @param float $y La coordonnée Y dans la projection RGF93 CC44.
 *
 * @return array Un tableau associatif contenant les coordonnées converties :
 *               - 'lat' (float) : La latitude dans la projection WGS84.
 *               - 'lng' (float) : La longitude dans la projection WGS84.
 *
 * @throws Exception Si la bibliothèque Proj4php rencontre une erreur lors de la transformation.
 */
function convertir_coords_rgf93cc44_to_wgs84($x, $y) {
    $proj4 = new Proj4php();

    $proj4->addDef("EPSG:3944", "+proj=lcc +lat_1=43.199291 +lat_2=44.800709 +lat_0=44 +lon_0=3 +x_0=1700000 +y_0=3200000 +ellps=GRS80 +units=m +no_defs");

    $projSrc = new Proj("EPSG:3944", $proj4);
    $projDest = new Proj("EPSG:4326", $proj4);

    $pointSrc = new Point($x, $y, $projSrc);
    $pointDest = $proj4->transform($projSrc, $projDest, $pointSrc);

    return [
        'lat' => $pointDest->y,
        'lng' => $pointDest->x
    ];
}


/**
 * Génère un fichier JSON contenant les informations des arbres publiés.
 *
 * Cette fonction récupère tous les articles de type "arbre", vérifie la présence
 * des métadonnées nécessaires (numéro, coordonnées), et convertit les coordonnées
 * RGF93CC44 en WGS84. Les données valides sont ensuite enregistrées dans un fichier
 * JSON dans le dossier "uploads/carte-data".
 *
 * @return array Un tableau associatif contenant :
 *               - 'success' (bool) : Indique si l'opération a réussi.
 *               - 'nb_valides' (int) : Nombre d'arbres valides inclus dans le fichier JSON.
 *               - 'nb_erreurs' (int) : Nombre d'arbres avec des erreurs.
 *               - 'erreurs' (array) : Liste des erreurs rencontrées pour chaque arbre.
 */
function generer_json_arbres() {
    $args = [
        'post_type' => 'arbre',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ];

    $arbres = get_posts($args);
    $resultats = [];
    $erreurs = [];

    foreach ($arbres as $arbre) {
        $id = $arbre->ID;

        $numero = get_post_meta($id, 'id_arbre', true);
        $nom = get_the_title($id);
        $easting = get_post_meta($id, 'easting', true);
        $northing = get_post_meta($id, 'northing', true);
        $problemes = [];

        if (!$numero) $problemes[] = 'numéro manquant';
        if (!$easting) $problemes[] = 'coordonnée est manquante (easting)';
        if (!$northing) $problemes[] = 'coordonnée nord manquante (northing)';

        if (!empty($problemes)) {
            $erreurs[] = "- \"$nom\" : " . implode(', ', $problemes);
            continue; // On passe au suivant
        }

        $coords = convertir_coords_rgf93cc44_to_wgs84($easting, $northing);

        $url_absolue = get_permalink($id);
        $home_url = home_url();
        $url_relative = str_replace($home_url, '', $url_absolue);

        $resultats[] = [
            'id' => $id,
            'numero' => $numero,
            'nom' => $nom,
            'lat' => $coords['lat'],
            'lng' => $coords['lng'],
            'url' => $url_relative
        ];
    }

    $json = json_encode($resultats, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $upload_dir = wp_upload_dir();
    $carte_data_dir = $upload_dir['basedir'] . '/carte-data';

    if (!file_exists($carte_data_dir)) {
        if (!mkdir($carte_data_dir, 0755, true)) {
            return ['success' => false, 'message' => '❌ Impossible de créer le dossier : ' . $carte_data_dir];
        }
    }

    if (!is_writable($carte_data_dir)) {
        return ['success' => false, 'message' => '❌ Le dossier n’est pas accessible en écriture : ' . $carte_data_dir];
    }

    $chemin_fichier = $carte_data_dir . '/arbres.json';
    if (file_put_contents($chemin_fichier, $json) === false) {
        return ['success' => false, 'message' => '❌ Erreur lors de l’écriture du fichier.'];
    }

    // Sauvegarder la date de génération
    update_option('carte_arbres_derniere_generation', current_time('mysql', true));

    return [
        'success' => true,
        'nb_valides' => count($resultats),
        'nb_erreurs' => count($erreurs),
        'erreurs' => $erreurs
    ];
}




/**
 * Ajoute une sous-page personnalisée sous le type de publication "Arbre" dans le menu d'administration WordPress.
 *
 * Cette sous-page permet aux administrateurs de générer un fichier JSON pour les données du type de publication personnalisé "arbre".
 *
 * Détails de la sous-page :
 * - Menu parent : Menu du type de publication "Arbre" (edit.php?post_type=arbre)
 * - Titre de la page : "Générer le JSON des arbres"
 * - Titre du menu : "Générer JSON"
 * - Capacité requise : "manage_options" (accessible uniquement aux administrateurs)
 * - Identifiant du menu : "generer-json-arbres"
 * - Fonction de rappel : interface_generation_json_arbres (affiche le contenu de la sous-page)
 *
 * @action admin_menu Enregistre la sous-page lors de l'initialisation du menu d'administration.
 */
add_action('admin_menu', function() {
    add_submenu_page(
        'edit.php?post_type=arbre',
        'Générer le JSON des arbres',
        'Générer JSON',
        'manage_options',
        'generer-json-arbres',
        'interface_generation_json_arbres'
    );
});

/**
 * Génère l'interface pour créer ou régénérer le fichier JSON des arbres.
 *
 * Cette fonction fournit une interface d'administration dans WordPress pour générer un fichier JSON
 * contenant les données des arbres. Ce fichier JSON est utilisé pour afficher les arbres sur une
 * carte interactive de l'arboretum. L'interface inclut un bouton pour déclencher le processus de
 * génération et affiche des informations sur la dernière génération ainsi qu'un lien pour consulter
 * le fichier JSON généré.
 *
 * Fonctionnalités :
 * - Affiche un formulaire avec un bouton pour générer le fichier JSON.
 * - Montre un message de succès ou d'erreur après le processus de génération.
 * - Affiche la date de dernière modification du fichier JSON s'il existe.
 * - Fournit un lien pour consulter le fichier JSON généré.
 *
 * Dépendances :
 * - S'appuie sur la fonction `wp_upload_dir()` pour déterminer le dossier d'upload.
 * - Utilise la fonction `generer_json_arbres()` pour gérer la logique de génération du JSON.
 * - Utilise les fonctions WordPress comme `esc_html()` et `esc_url()` pour la sécurisation.
 *
 * Remarques :
 * - Le fichier JSON est stocké dans le dossier `/carte-data` à l'intérieur du dossier d'uploads de WordPress.
 * - Le fichier doit être régénéré à chaque ajout, suppression ou mise à jour des coordonnées des arbres.
 *
 * @return void
 */
function interface_generation_json_arbres() {
    $upload_dir = wp_upload_dir();
    $carte_data_dir = $upload_dir['basedir'] . '/carte-data';
    $json_file_path = $carte_data_dir . '/arbres.json';
    $json_file_url  = $upload_dir['baseurl'] . '/carte-data/arbres.json';

    $message = '';

    if (isset($_POST['generer_json'])) {
        $result = generer_json_arbres();

        if (isset($result['success']) && $result['success'] === true) {
            $nb_valides = $result['nb_valides'];
            $nb_erreurs = $result['nb_erreurs'];

            $message  = '<div class="notice notice-success"><p>✅ Fichier JSON généré avec succès.</p>';
            $message .= '<p>✔️ Arbres valides : ' . $nb_valides . '</p>';
            if ($nb_erreurs > 0) {
                $message .= '<p>⚠️ Arbres ignorés : ' . $nb_erreurs . '</p><ul>';
                foreach ($result['erreurs'] as $erreur) {
                    $message .= '<li>' . esc_html($erreur) . '</li>';
                }
                $message .= '</ul>';
            }
            $message .= '</div>';
        } elseif (isset($result['message'])) {
            $message = '<div class="notice notice-error"><p>' . esc_html($result['message']) . '</p></div>';
        } else {
            $message = '<div class="notice notice-error"><p>❌ Une erreur inconnue est survenue.</p></div>';
        }
    }

    echo '<div class="wrap">';
    echo '<h1>Générer le fichier JSON des arbres</h1>';
    echo '<p>Ce fichier est utilisé pour afficher les arbres sur la carte interactive de l’arboretum. <br>Il doit être régénéré à chaque modification des arbres (ajout, suppression ou changement de coordonnées).</p>';
    
    // Afficher le statut de synchronisation
    afficher_statut_json_arbres();
    
    echo $message;
    echo '<form method="post">';
    echo '<input type="submit" name="generer_json" class="button button-primary" value="Générer le fichier JSON">';
    echo '</form>';

    if (file_exists($json_file_path)) {
        $timestamp = filemtime($json_file_path);
        $last_modified = wp_date('d/m/Y à H:i:s', $timestamp);
        echo '<p><strong>Dernière génération :</strong> ' . $last_modified . '</p>';
        echo '<p><a href="' . esc_url($json_file_url) . '" target="_blank">📂 Voir le fichier JSON</a></p>';
    }

    // Documentation sur le shortcode
    documentation();

    echo '</div>';
}


/**
 * Vérifie le statut de synchronisation du fichier JSON des arbres.
 *
 * Compare la date de dernière génération du fichier JSON avec les dates de modification
 * des arbres pour déterminer si une régénération est nécessaire.
 *
 * @return array Un tableau associatif contenant :
 *               - 'up_to_date' (bool) : Indique si le fichier JSON est à jour.
 *               - 'modified_trees' (array) : Liste des arbres modifiés depuis la dernière génération.
 *               - 'last_generation' (string|false) : Date de dernière génération ou false si jamais généré.
 */
function verifier_statut_json_arbres() {
    $last_generation = get_option('carte_arbres_derniere_generation');
    
    if (!$last_generation) {
        return [
            'up_to_date' => false,
            'modified_trees' => [],
            'last_generation' => false
        ];
    }
    
    $arbres_modifies = get_posts([
        'post_type' => 'arbre',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'date_query' => [
            [
                'column' => 'post_modified',
                'after' => $last_generation,
                'inclusive' => false
            ]
        ]
    ]);
    
    return [
        'up_to_date' => empty($arbres_modifies),
        'modified_trees' => $arbres_modifies,
        'last_generation' => $last_generation
    ];
}

/**
 * Affiche le statut de synchronisation du fichier JSON dans l'interface d'administration.
 *
 * Génère un message d'information indiquant si le fichier JSON est à jour ou si des arbres
 * ont été modifiés depuis la dernière génération.
 *
 * @return void
 */
function afficher_statut_json_arbres() {
    $statut = verifier_statut_json_arbres();
    
    if (!$statut['last_generation']) {
        echo '<div class="notice notice-info">';
        echo '<p>ℹ️ Aucun fichier JSON n\'a encore été généré.</p>';
        echo '</div>';
        return;
    }
    
    if ($statut['up_to_date']) {
        echo '<div class="notice notice-success">';
        echo '<p>✅ Le fichier JSON est à jour.</p>';
        echo '</div>';
    } else {
        $nb_modifies = count($statut['modified_trees']);
        echo '<div class="notice notice-warning">';
        echo '<p>⚠️ <strong>' . $nb_modifies . ' arbre(s)</strong> ont été modifiés depuis la dernière génération :</p>';
        echo '<ul>';
        foreach ($statut['modified_trees'] as $arbre) {
            $date_modif = wp_date('d/m/Y à H:i', strtotime($arbre->post_modified));
            echo '<li>' . esc_html(get_the_title($arbre->ID)) . ' (modifié le ' . $date_modif . ')</li>';
        }
        echo '</ul>';
        echo '</div>';
    }
}


/**
 * Affiche la documentation pour l'utilisation du shortcode [plan_arboretum].
 *
 * Cette fonction génère une structure HTML qui fournit des informations sur l'utilisation du shortcode
 * pour afficher une carte interactive sur les pages ou articles. Elle inclut des détails sur les paramètres
 * disponibles, les valeurs par défaut et des exemples d'utilisation.
 */
function documentation() {
    echo '<hr>';
    echo '<h2>📋 Utilisation du shortcode</h2>';
    echo '<p>Pour afficher la carte interactive sur vos pages ou articles, utilisez le shortcode suivant :</p>';
    
    echo '<div style="background: #f1f1f1; padding: 15px; border-radius: 5px; margin: 15px 0;">';
    echo '<code>[plan_arboretum]</code>';
    echo '</div>';
    
    echo '<h3>Paramètres disponibles</h3>';
    echo '<table class="widefat striped">';
    echo '<thead><tr><th>Paramètre</th><th>Description</th><th>Valeur par défaut</th><th>Exemple</th></tr></thead>';
    echo '<tbody>';
    echo '<tr><td><code>height</code></td><td>Hauteur de la carte</td><td>500px</td><td><code>[plan_arboretum height="300px"]</code></td></tr>';
    echo '<tr><td><code>width</code></td><td>Largeur de la carte</td><td>100%</td><td><code>[plan_arboretum width="80%"]</code></td></tr>';
    echo '<tr><td><code>zoom</code></td><td>Niveau de zoom initial (10-19)</td><td>15</td><td><code>[plan_arboretum zoom="18"]</code></td></tr>';
    echo '</tbody>';
    echo '</table>';
    
    echo '<h3>Exemples d\'utilisation</h3>';
    echo '<ul>';
    echo '<li><strong>Carte standard :</strong> <code>[plan_arboretum]</code></li>';
    echo '<li><strong>Carte plus petite :</strong> <code>[plan_arboretum height="300px"]</code></li>';
    echo '<li><strong>Zoom rapproché :</strong> <code>[plan_arboretum zoom="18"]</code></li>';
    echo '<li><strong>Paramètres combinés :</strong> <code>[plan_arboretum height="400px" zoom="16"]</code></li>';
    echo '</ul>';
    
    echo '<div class="notice notice-info" style="margin-top: 20px;">';
    echo '<p>💡 <strong>Astuce :</strong> Vous pouvez également utiliser des paramètres d\'URL pour mettre en évidence des arbres spécifiques :</p>';
    echo '<p><code>/votre-page/?abr=12,175</code> mettra en évidence les arbres numéro 12 et 175.</p>';
    echo '</div>';
}