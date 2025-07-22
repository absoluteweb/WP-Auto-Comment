<?php

if (!defined('ABSPATH')) {
    exit; 
}

// Analyser le contexte et la thématique du site
function acg_analyze_site_context() {
    $context = [];
    
    // Informations de base du site
    $context['site_name'] = get_bloginfo('name');
    $context['site_description'] = get_bloginfo('description');
    $context['site_language'] = get_bloginfo('language');
    
    // Récupérer les catégories principales (top 10 par nombre d'articles)
    $categories = get_categories([
        'number' => 10,
        'orderby' => 'count',
        'order' => 'DESC',
        'hide_empty' => true
    ]);
    
    $category_names = [];
    $category_context = '';
    foreach ($categories as $category) {
        if ($category->count > 0) {
            $category_names[] = $category->name;
            $category_context .= $category->name . ' (' . $category->count . ' articles), ';
        }
    }
    $context['main_categories'] = array_slice($category_names, 0, 5);
    $context['category_text'] = rtrim($category_context, ', ');
    
    // Analyser les tags populaires
    $tags = get_tags([
        'number' => 15,
        'orderby' => 'count',
        'order' => 'DESC'
    ]);
    
    $tag_names = [];
    foreach ($tags as $tag) {
        if ($tag->count > 1) {
            $tag_names[] = $tag->name;
        }
    }
    $context['popular_tags'] = array_slice($tag_names, 0, 10);
    
    // Analyser les articles récents pour détecter les thèmes
    $recent_posts = get_posts([
        'numberposts' => 20,
        'post_status' => 'publish',
        'post_type' => 'post'
    ]);
    
    $content_sample = '';
    $titles_sample = '';
    foreach ($recent_posts as $post) {
        $titles_sample .= $post->post_title . '. ';
        $excerpt = wp_trim_words($post->post_content, 20);
        $content_sample .= $excerpt . ' ';
    }
    
    $context['recent_titles'] = $titles_sample;
    $context['content_sample'] = wp_trim_words($content_sample, 100);
    
    // Détecter le secteur avec OpenAI (avec cache) ou méthode locale
    $context['detected_niche'] = acg_detect_site_niche_with_ai($context);
    
    return $context;
}

// Détection de niche via OpenAI (avec cache)
function acg_detect_site_niche_with_ai($context) {
    $api_key = get_option('acg_api_key', '');
    
    // Vérifier si on a un résultat en cache
    $cached_niche = get_option('acg_cached_site_niche', '');
    $cache_timestamp = get_option('acg_cached_site_niche_timestamp', 0);
    $use_ai_detection = get_option('acg_use_ai_niche_detection', 1);
    
    // Si cache valide (moins de 30 jours) et détection AI activée, utiliser le cache
    if (!empty($cached_niche) && $use_ai_detection && (time() - $cache_timestamp) < (30 * 24 * 3600)) {
        return $cached_niche;
    }
    
    // Si pas de clé API ou détection AI désactivée, utiliser la méthode locale
    if (empty($api_key) || !$use_ai_detection) {
        return acg_detect_site_niche_local($context);
    }
    
    // Préparer les données pour l'analyse OpenAI
    $analysis_data = "SITE À ANALYSER :\n";
    $analysis_data .= "Nom du site : " . $context['site_name'] . "\n";
    $analysis_data .= "Description : " . $context['site_description'] . "\n";
    $analysis_data .= "Principales catégories : " . implode(', ', $context['main_categories']) . "\n";
    if (!empty($context['popular_tags'])) {
        $analysis_data .= "Tags populaires : " . implode(', ', $context['popular_tags']) . "\n";
    }
    $analysis_data .= "Échantillon de titres d'articles : " . wp_trim_words($context['recent_titles'], 50) . "\n";
    $analysis_data .= "Échantillon de contenu : " . $context['content_sample'];
    
    $prompt = 'Analyse ce site web et détermine sa thématique principale. Réponds UNIQUEMENT avec un des secteurs suivants (un seul mot) :

SECTEURS DISPONIBLES :
- cuisine (alimentation, gastronomie, recettes, nutrition culinaire)
- technologie (informatique, développement, digital, applications, gadgets)  
- lifestyle (mode, beauté, voyage, décoration, bien-être général)
- santé (médecine, fitness, nutrition santé, thérapie, soins)
- business (entreprise, marketing, finance, économie, entrepreneuriat)
- éducation (formation, apprentissage, enseignement, pédagogie)
- famille (enfants, parentalité, maternité, éducation familiale)
- loisirs (hobby, divertissement, culture, art, sport récréatif)
- général (si aucun secteur ne domine clairement)

DONNÉES DU SITE :
' . $analysis_data . '

Réponds UNIQUEMENT avec le nom du secteur le plus approprié (un seul mot, en minuscules) :';

    $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
        'timeout' => 60,
        'headers' => [
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json',
        ],
        'body' => json_encode([
            'model' => 'gpt-4o-mini',
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => 0.1, // Faible température pour plus de cohérence
            'max_tokens' => 20
        ]),
    ]);

    if (is_wp_error($response)) {
        error_log('Erreur détection niche OpenAI: ' . $response->get_error_message());
        return acg_detect_site_niche_local($context);
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (isset($data['choices'][0]['message']['content'])) {
        $detected_niche = trim(strtolower($data['choices'][0]['message']['content']));
        
        // Valider que la réponse est dans nos secteurs autorisés
        $valid_niches = ['cuisine', 'technologie', 'lifestyle', 'santé', 'business', 'éducation', 'famille', 'loisirs', 'général'];
        
        if (in_array($detected_niche, $valid_niches)) {
            // Sauvegarder en cache
            update_option('acg_cached_site_niche', $detected_niche);
            update_option('acg_cached_site_niche_timestamp', time());
            
            return $detected_niche;
        }
    }
    
    // En cas d'échec, utiliser la méthode locale
    error_log('Détection OpenAI échouée, utilisation méthode locale');
    return acg_detect_site_niche_local($context);
}

// Détection locale (ancienne méthode) utilisée comme fallback
function acg_detect_site_niche_local($context) {
    $categories_text = strtolower(implode(' ', $context['main_categories']));
    $tags_text = strtolower(implode(' ', $context['popular_tags']));
    $description_text = strtolower($context['site_description']);
    $full_text = $categories_text . ' ' . $tags_text . ' ' . $description_text;
    
    // Définir des mots-clés pour différents secteurs
    $niches = [
        'cuisine' => ['cuisine', 'recette', 'food', 'gastronomie', 'restaurant', 'chef', 'plat', 'ingrédient', 'culinaire'],
        'technologie' => ['tech', 'technologie', 'développement', 'programmation', 'digital', 'web', 'app', 'logiciel', 'informatique'],
        'lifestyle' => ['lifestyle', 'mode', 'beauté', 'voyage', 'décoration', 'bien-être', 'fashion', 'tendance'],
        'santé' => ['santé', 'médecine', 'fitness', 'nutrition', 'sport', 'wellness', 'thérapie', 'exercice'],
        'business' => ['business', 'entreprise', 'marketing', 'finance', 'économie', 'startup', 'management', 'leadership'],
        'éducation' => ['éducation', 'formation', 'apprentissage', 'école', 'enseignement', 'pédagogie', 'cours'],
        'famille' => ['famille', 'enfant', 'parent', 'maternité', 'éducation enfant', 'bébé', 'parentalité'],
        'loisirs' => ['hobby', 'loisir', 'divertissement', 'jeu', 'culture', 'art', 'musique', 'cinéma']
    ];
    
    $scores = [];
    foreach ($niches as $niche => $keywords) {
        $score = 0;
        foreach ($keywords as $keyword) {
            $score += substr_count($full_text, $keyword);
        }
        $scores[$niche] = $score;
    }
    
    // Retourner le secteur avec le meilleur score
    $detected_niche = array_search(max($scores), $scores);
    return max($scores) > 0 ? $detected_niche : 'général';
}

// AJAX pour relancer la détection de niche
function acg_refresh_niche_detection() {
    check_ajax_referer('refresh_niche_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permissions insuffisantes']);
    }
    
    // Supprimer le cache
    delete_option('acg_cached_site_niche');
    delete_option('acg_cached_site_niche_timestamp');
    
    // Relancer l'analyse
    $context = acg_analyze_site_context();
    
    wp_send_json_success([
        'niche' => $context['detected_niche'],
        'categories' => $context['main_categories'],
        'tags' => array_slice($context['popular_tags'], 0, 8)
    ]);
}
add_action('wp_ajax_acg_refresh_niche_detection', 'acg_refresh_niche_detection');

// AJAX pour réinitialiser le compteur IP
function acg_reset_ip_counter() {
    check_ajax_referer('reset_ip_counter_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permissions insuffisantes']);
    }
    
    // Réinitialiser les compteurs IP
    update_option('acg_global_ip_count', 0);
    update_option('acg_last_ip_list', []);
    
    wp_send_json_success(['message' => 'Compteur IP réinitialisé avec succès']);
}
add_action('wp_ajax_acg_reset_ip_counter', 'acg_reset_ip_counter');

// Fonction de nettoyage des anciennes postmeta inutiles
function acg_cleanup_deprecated_postmeta() {
    global $wpdb;
    
    // Supprimer toutes les postmeta _acg_max_comments devenues inutiles
    $deleted = $wpdb->delete(
        $wpdb->postmeta,
        array('meta_key' => '_acg_max_comments'),
        array('%s')
    );
    
    if ($deleted !== false) {
        error_log('[WP Auto Comment] Nettoyage : ' . $deleted . ' entrées _acg_max_comments supprimées');
        return $deleted;
    }
    
    return false;
}

// AJAX pour nettoyer les anciennes données
function acg_cleanup_deprecated_data() {
    check_ajax_referer('cleanup_deprecated_nonce', 'nonce');
    
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permissions insuffisantes']);
    }
    
    $deleted = acg_cleanup_deprecated_postmeta();
    
    if ($deleted !== false) {
        wp_send_json_success(['message' => $deleted . ' anciennes données supprimées avec succès']);
    } else {
        wp_send_json_error(['message' => 'Erreur lors du nettoyage des données']);
    }
}
add_action('wp_ajax_acg_cleanup_deprecated_data', 'acg_cleanup_deprecated_data');

// Détecter le secteur/niche du site (fonction maintenue pour compatibilité)
function acg_detect_site_niche($context) {
    return acg_detect_site_niche_with_ai($context);
}

// AJAX pour générer les templates
function acg_generate_comment_templates() {
    check_ajax_referer('generate_templates_nonce', 'nonce');

    $count = intval($_POST['count']);
    $api_key = get_option('acg_api_key');

    if (empty($api_key)) {
        wp_send_json_error(['message' => 'Clé API OpenAI non configurée.']);
    }

    // Analyser le contexte du site
    $site_context = acg_analyze_site_context();
    $use_context = get_option('acg_use_site_context', 1);

    $templates = [];
    for ($i = 0; $i < $count; $i++) {
        
        // Construire le prompt contextualisé
        $base_prompt = 'Ta réponse doit être stockée dans la variable "template_com" au format JSON. Je ne veux pas de sous-variable, mais uniquement la variable template_com avec des caractères en valeur. Cette variable doit créer un persona unique et varié qui servira de base pour générer des commentaires.';
        
        if ($use_context && !empty($site_context['detected_niche']) && $site_context['detected_niche'] !== 'général') {
            $context_prompt = "\n\nCONTEXTE DU SITE :\n";
            $context_prompt .= "- Nom du site : " . $site_context['site_name'] . "\n";
            $context_prompt .= "- Description : " . $site_context['site_description'] . "\n";
            $context_prompt .= "- Secteur détecté : " . $site_context['detected_niche'] . "\n";
            $context_prompt .= "- Principales catégories : " . implode(', ', $site_context['main_categories']) . "\n";
            if (!empty($site_context['popular_tags'])) {
                $context_prompt .= "- Tags populaires : " . implode(', ', array_slice($site_context['popular_tags'], 0, 5)) . "\n";
            }
            
            $context_prompt .= "\nAdapte le persona pour qu'il soit NATURELLEMENT intéressé par cette thématique. Choisis une profession, des centres d'intérêt et un style d'écriture qui correspondent au secteur '" . $site_context['detected_niche'] . "'.";
        } else {
            $context_prompt = '';
        }

 $prompt = $base_prompt . $context_prompt . '

Le persona doit inclure les éléments suivants :

- **Nom de famille et Prénom** : Choisis un nom et un prénom uniques qui ne ressemblent pas aux précédents persona. Evite des noms trop courants.

- **Âge et Situation** : Indique un âge (entre 20 et 60 ans) et précise une brève mention de la situation de ce persona (étudiant, professionnel dans un secteur varié, parent, retraité, etc.), en s\'assurant que cela reflète la diversité des expériences.

- **Profession** : Il faut un nom de métier unique' . ($use_context && $site_context['detected_niche'] !== 'général' ? ', de préférence en lien avec le secteur ' . $site_context['detected_niche'] . ' ou complémentaire' : ', tout secteur confondue') . '. Sélectionne un métier sauf écrivain, rédacteur et la même thématique.

- **Style d\'Écriture** : Fournis un style d\'écriture distinctif' . ($use_context ? ' adapté au public cible de ce type de site' : '') . '. 

La réponse doit être concise, ne dépassant pas 50 mots, et se concentrer sur la création d\'un persona unique. Assure-toi que la réponse soit variée à chaque requête en intégrant des éléments aléatoires et des descriptions différentes.

Voici un exemple de ce à quoi pourrait ressembler une réponse :
{"template_com": "Nom de famille : Malik | Prénom : Gourmand | 35 ans | consultant en développement durable. Passionné de nature et d\'innovation, j\'écris souvent sur les astuces écologiques. Mon style est passionné et engageant. J\'aime inclure des faits intéressants pour enrichir le débat et j\'adore terminer par des appels à l\'action."}

Assure-toi que la réponse soit prête à être utilisée comme base pour générer des commentaires ultérieurs au sein d\'une application ou d\'un service. Utilise des mots simples :';

        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', [
            'timeout' => 100,
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'gpt-4o-mini',
                'messages' => [
                    ['role' => 'user', 'content' => $prompt]
                ],
               'temperature' => 1.2,
                 
                'max_tokens' => 1000,
                'response_format' => [
                    'type' => 'json_object'
                ]
            ]),
        ]);

        if (is_wp_error($response)) {
            wp_send_json_error(['message' => 'Erreur API: ' . $response->get_error_message()]);
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['choices'][0]['message']['content'])) {
            $json_response = json_decode($data['choices'][0]['message']['content'], true);
            if (json_last_error() === JSON_ERROR_NONE && isset($json_response['template_com'])) {
                $templates[] = $json_response['template_com']; 
            } else {
                wp_send_json_error(['message' => 'La réponse JSON n\'est pas au format attendu.']);
            }
        }
    }

    wp_send_json_success(['templates' => $templates]);
}
add_action('wp_ajax_acg_generate_comment_templates', 'acg_generate_comment_templates');