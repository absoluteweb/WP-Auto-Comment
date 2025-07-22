<?php 

if (!defined('ABSPATH')) {
    exit; 
}

function acg_cron_generate_comments() {
    $enabled = get_option('acg_auto_comment_enabled', 1);
    $comment_publish_mode = get_option('acg_comment_publish_mode', 'duration');
    $auto_comment_delay = get_option('acg_auto_comment_delay', 30) * 60; // Convertir en secondes

    // Désactiver le cron dans la tranche horaire (optionnelle)
    $disable_hours = get_option('acg_disable_auto_comment_hours', 0);
    $start_hour = get_option('acg_disable_auto_comment_start_hour', '03:00');
    $end_hour = get_option('acg_disable_auto_comment_end_hour', '07:00');
    
    if ($disable_hours && $start_hour && $end_hour) {
        $now = current_time('H:i');
        if (
            ($start_hour < $end_hour && $now >= $start_hour && $now < $end_hour) ||
            ($start_hour > $end_hour && ($now >= $start_hour || $now < $end_hour))
        ) {
            error_log('[WP Auto Comment] Désactivation automatique des commentaires pendant la tranche horaire : ' . $start_hour . ' - ' . $end_hour);
            return;
        }
    }

    if (!$enabled) {
        return;
    }

    $all_types = get_post_types(['public' => true, 'show_ui' => true]);
    $posts = get_posts([
        'numberposts' => -1,
        'post_type'   => $all_types,
        'post_status' => 'publish',
    ]);

    $api_key = get_option('acg_api_key', '');
    $min_words = get_option('acg_min_words', 5);
    $max_words = get_option('acg_max_words', 20);
    $gpt_model = get_option('acg_gpt_model', 'gpt-4o-mini');
    $writing_styles = get_option('acg_writing_styles', []);
    $include_author_names = get_option('acg_include_author_names', []); 

    if (empty($writing_styles)) {
        error_log('Aucun style d\'écriture disponible.');
        return; 
    }

    if (empty($api_key)) {
        error_log('Clé API OpenAI non configurée.');
        return;
    }

    // === MODE VISITES ===
    if ($comment_publish_mode === 'visits') {
        $comments_per_trigger = get_option('acg_comment_per_ip', 1);
        $interval_per_ip = get_option('acg_interval_per_ip', 1);
        
        // ✅ CORRECTION : Ne plus récupérer l'IP ici (c'était l'IP du serveur)
        // Les vraies IP des visiteurs sont collectées côté front-end via acg_collect_visitor_ip()
        
        // Récupérer le compteur global d'IP uniques collectées côté front-end
        $global_ip_count = get_option('acg_global_ip_count', 0);
        $last_ip_list = get_option('acg_last_ip_list', []);
        
        // Vérifier si on doit publier des commentaires
        if ($global_ip_count >= $interval_per_ip) {
            // Filtrer les articles éligibles pour les commentaires automatiques
            $eligible_posts = [];
            foreach ($posts as $post) {
                $auto_comment_enabled = get_post_meta($post->ID, '_acg_auto_comment_enabled', true);
                if ($auto_comment_enabled) {
                    $eligible_posts[] = $post;
                }
            }
            
            if (empty($eligible_posts)) {
                error_log('[WP Auto Comment] Aucun article éligible pour les commentaires automatiques.');
                return;
            }
            
            // Sélectionner aléatoirement X articles parmi les éligibles
            $selected_count = min($comments_per_trigger, count($eligible_posts));
            $selected_posts = [];
            
            if ($selected_count > 0) {
                // Mélanger les articles et prendre les X premiers
                shuffle($eligible_posts);
                $selected_posts = array_slice($eligible_posts, 0, $selected_count);
                
                // Créer un commentaire pour chaque article sélectionné
                foreach ($selected_posts as $post) {
                    $success = create_comment(
                        $post->ID, 
                        $post->post_content, 
                        $min_words, 
                        $max_words, 
                        $gpt_model, 
                        $writing_styles, 
                        $include_author_names
                    );
                    
                    if ($success) {
                        error_log('[WP Auto Comment] Commentaire ajouté à l\'article ID ' . $post->ID . ' (mode IP - vraies visites)');
                    }
                }
                
                // Réinitialiser le compteur après génération
                update_option('acg_global_ip_count', 0);
                update_option('acg_last_ip_list', []);
                update_option('acg_comments_triggered_time', time()); // Timestamp du dernier déclenchement
                
                error_log('[WP Auto Comment] ' . count($selected_posts) . ' commentaires générés suite à ' . $global_ip_count . ' visites uniques');
            }
        }
        
        return; // Sortir pour éviter le mode duration
    }

    // === MODE DURATION avec garde-fous intelligents ===
    foreach ($posts as $post) {
        $post_id = $post->ID;
        $post_content = $post->post_content;

        $auto_comment_enabled = get_post_meta($post_id, '_acg_auto_comment_enabled', true);
        if (!$auto_comment_enabled) {
            continue; 
        }

        $published_time = strtotime($post->post_date_gmt);
        $current_time = time();

        // Appliquer le délai AVANT toute génération !
        if (($current_time - $published_time) < $auto_comment_delay) {
            continue;
        }

        // === GARDE-FOUS INTELLIGENTS ===
        
        // 1. Limitation par âge d'article (évite les vieux articles submergés)
        $max_article_age_days = get_option('acg_max_article_age_days', 30); // 30 jours par défaut
        $article_age_days = ($current_time - $published_time) / (24 * 3600);
        
        if ($article_age_days > $max_article_age_days) {
            // Désactiver automatiquement l'auto-comment sur les vieux articles
            update_post_meta($post_id, '_acg_auto_comment_enabled', '0');
            error_log('[WP Auto Comment] Auto-comment désactivé pour article ID ' . $post_id . ' (âge: ' . round($article_age_days) . ' jours)');
            continue;
        }
        
        // 2. Limitation par nombre de commentaires générés par le plugin
        $total_comments = wp_count_comments($post_id)->total_comments;
        $plugin_comments = get_post_meta($post_id, '_acg_generated_comments_count', true) ?: 0;
        $max_plugin_comments = get_option('acg_max_plugin_comments_per_post', 25); // 25 par défaut
        
        if ($plugin_comments >= $max_plugin_comments) {
            // Désactiver automatiquement l'auto-comment si limite atteinte
            update_post_meta($post_id, '_acg_auto_comment_enabled', '0');
            error_log('[WP Auto Comment] Auto-comment désactivé pour article ID ' . $post_id . ' (limite: ' . $plugin_comments . ' commentaires générés)');
            continue;
        }
        
        // 3. Seuil de sécurité global (évite les articles avec trop de commentaires au total)
        $max_total_comments = get_option('acg_max_total_comments_per_post', 50); // 50 par défaut
        
        if ($total_comments >= $max_total_comments) {
            // Désactiver automatiquement l'auto-comment si trop de commentaires au total
            update_post_meta($post_id, '_acg_auto_comment_enabled', '0');
            error_log('[WP Auto Comment] Auto-comment désactivé pour article ID ' . $post_id . ' (total: ' . $total_comments . ' commentaires)');
            continue;
        }

        // 4. Générer entre X et Y commentaires par cycle (si tous les garde-fous passent)
        $min_comments = get_option('acg_comment_min_per_post', 1);
        $max_comments = get_option('acg_comment_max_per_post', 5);
        $comment_count = rand($min_comments, $max_comments);
        
        // Vérifier qu'on ne dépasse pas les limites avec ce cycle
        $remaining_plugin_slots = $max_plugin_comments - $plugin_comments;
        $remaining_total_slots = $max_total_comments - $total_comments;
        $max_this_cycle = min($comment_count, $remaining_plugin_slots, $remaining_total_slots);
        
        if ($max_this_cycle <= 0) {
            continue; // Aucune place disponible
        }

        // Générer les commentaires avec comptage
        for ($i = 0; $i < $max_this_cycle; $i++) {
            $success = create_comment($post_id, $post_content, $min_words, $max_words, $gpt_model, $writing_styles, $include_author_names);
            
            if ($success) {
                // Incrémenter le compteur de commentaires générés par le plugin
                $plugin_comments++;
                update_post_meta($post_id, '_acg_generated_comments_count', $plugin_comments);
            }
        }
        
        if ($max_this_cycle > 0) {
            error_log('[WP Auto Comment] ' . $max_this_cycle . ' commentaires générés pour article ID ' . $post_id . ' (total plugin: ' . $plugin_comments . ')');
        }
    }
}
add_action('acg_cron_hook', 'acg_cron_generate_comments');

function create_comment($post_id, $post_content, $min_words, $max_words, $gpt_model, $writing_styles, $include_author_names) {
    $current_index = array_rand($writing_styles);
    $style = $writing_styles[$current_index];

    $include_author_name = is_array($include_author_names) && in_array($current_index, $include_author_names);
    if ($include_author_name) {
        $post_author_id = get_post_field('post_author', $post_id); 
        $post_author_first_name = get_user_meta($post_author_id, 'first_name', true);
        $post_author_display_name = get_the_author_meta('display_name', $post_author_id);
        $post_author = !empty($post_author_first_name) ? $post_author_first_name : $post_author_display_name;
        $inclureauteur = "Adresse toi directement à l'auteur de l'article en début de commentaire : " . $post_author . ", en répondant : ";
    } else {
        $inclureauteur = ""; 
    }
    $full_prompt = [
        [
            'role' => 'system',
            'content' => 'Voici le contenu de l\'article : ' . $post_content . '. Voici le style d\'écriture à prendre en compte ainsi que les informations sur le persona à imiter pour la réponse : ' . $style
        ],
        [
            'role' => 'user',
            'content' => ' '. $inclureauteur . 'Donne-moi un JSON avec la variable "auteur" et la variable "commentaire".  Écris un commentaire (désoptimisé) d\'environ entre ' . intval($min_words) . ' et ' . intval($max_words) . ' mots. Si le prénom et le nom de famille sont spécifiés dans le style d\'écriture ci-dessus/infos du persona à imiter, utilise exactement les mêmes dans la variable auteur. Sinon, invente un nom et un prénom uniques qui ne sont pas classiques. Rédige un commentaire court et pertinent en utilisant ces informations. Commentaire dans la langue dans laquelle est rédigé l\'article. Donne un avis naturel avec des mots simples.'
        ]
    ];

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 100,
        'headers' => [
            'Authorization' => 'Bearer ' . get_option('acg_api_key', ''),
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => $gpt_model,
            'messages' => $full_prompt,
            'temperature' => 1.0,
            'max_tokens' => 500,
            'top_p' => 1,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'response_format' => [
                'type' => 'json_object'
            ]
        ]),
    ]);

    if (is_wp_error($response)) {
        error_log('Erreur API: ' . $response->get_error_message());
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['choices'][0]['message']['content'])) {
        $json_response = json_decode($data['choices'][0]['message']['content'], true);
        
        if (isset($json_response['auteur']) && isset($json_response['commentaire'])) {
            $comment_content = trim($json_response['commentaire']);
            $comment_author = trim($json_response['auteur']);

            $comment_data = array(
                'comment_post_ID' => $post_id,
                'comment_content' => $comment_content,
                'comment_author' => $comment_author,
                'comment_approved' => 1,
                // Marquer le commentaire comme généré par le plugin
                'comment_meta' => array(
                    '_acg_generated' => '1',
                    '_acg_generated_time' => current_time('timestamp')
                )
            );

            $comment_id = wp_insert_comment($comment_data);
            
            if ($comment_id) {
                // Ajouter les meta données séparément pour plus de sécurité
                add_comment_meta($comment_id, '_acg_generated', '1');
                add_comment_meta($comment_id, '_acg_generated_time', current_time('timestamp'));
                return true;
            }
        } else {
            error_log('La réponse JSON n\'est pas au format attendu pour l\'article ID ' . $post_id);
        }
    } else {
        error_log('Aucune réponse valide reçue de l\'API pour l\'article ID ' . $post_id);
    }
    
    return false;
}