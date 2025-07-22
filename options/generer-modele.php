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
    
    // Détecter le secteur probable
    $context['detected_niche'] = acg_detect_site_niche($context);
    
    return $context;
}

// Détecter le secteur/niche du site
function acg_detect_site_niche($context) {
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