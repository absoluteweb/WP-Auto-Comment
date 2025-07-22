<?php 

if (!defined('ABSPATH')) {
    exit; 
}

// page d'options
function acg_add_admin_menu() {
    add_options_page('WP Auto Comment', 'WP Auto Comment', 'manage_options', 'wp-auto-comment', 'acg_options_page');
}
add_action('admin_menu', 'acg_add_admin_menu');

function acg_options_page() {
    $comment_publish_mode = get_option('acg_comment_publish_mode', 'duration');
    $auto_comment_default = get_option('acg_auto_comment_default', 1);
    $delay_display = ($auto_comment_default && $comment_publish_mode === 'duration') ? '' : 'display:none;';
    $auto_comment_default_mode = get_option('acg_auto_comment_default_mode', 'all');
    $auto_comment_default_frequency = get_option('acg_auto_comment_default_frequency', 2); // par défaut toutes les 2 publications
    ?>
    <div class="wrap">
        <h1>WP Auto Comment</h1>
        <form action="options.php" method="post">
            <?php
            settings_fields('acg_options_group');
            do_settings_sections('acg_options_group');
            $api_key = get_option('acg_api_key', '');
            $min_words = get_option('acg_min_words', 5);
            $max_words = get_option('acg_max_words', 20);
            $cron_interval = get_option('acg_cron_interval', 5);
            $auto_comment_enabled = get_option('acg_auto_comment_enabled', 1); 
            $gpt_model = get_option('acg_gpt_model', 'gpt-4o-mini'); 
            $comment_count = get_option('acg_comment_count', 1); 
            $writing_styles = (array) get_option('acg_writing_styles', []); 
            $include_author_names = (array) get_option('acg_include_author_names', []); 
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row" colspan="2" style="padding:0px !important;">
                        <h2 style="margin:8px 0px !important;">Réglages générales</h2>
                        <p style="font-weight:400;">Pour utiliser ce plugin, vous devez générer une clé API sur OpenAI et l'enregistrer sur cette page d'options avant de passer aux étapes suivantes.</p>
                    </th>
                </tr>
                <tr valign="top">
                    <th scope="row">Clé API OpenAI</th>
                    <td>
                        <input type="text" name="acg_api_key" value="<?php echo esc_attr($api_key); ?>" />
                        <p><a href="https://platform.openai.com/api-keys" target="_blank">Générer une clé OpenAI</a></p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Publier par :</th>
                    <td>
                        <select name="acg_comment_publish_mode" id="comment_publish_mode">
                            <option value="duration" <?php selected($comment_publish_mode, 'duration'); ?>>Publier par durée</option>
                            <option value="visits" <?php selected($comment_publish_mode, 'visits'); ?>>Publier par visites (IP)</option>
                        </select>
                        <p>Choisissez comment vous souhaitez publier des commentaires.</p>
                    </td>
                </tr>

                <tr valign="top" id="ip-comment-interval-row" style="<?php echo $comment_publish_mode === 'visits' ? '' : 'display:none;'; ?>">
                    <th scope="row">Configuration du mode par visites</th>
                    <td>
                        <div style="margin-bottom: 15px;">
                            <label><strong>Nombre de commentaires à générer :</strong></label>
                            <input type="number" name="acg_comment_per_ip" value="<?php echo esc_attr(get_option('acg_comment_per_ip', 1)); ?>" min="1" max="10" style="width: 60px;" />
                            
                            <label style="margin-left: 20px;"><strong>Déclenchement toutes les :</strong></label>
                            <input type="number" name="acg_interval_per_ip" value="<?php echo esc_attr(get_option('acg_interval_per_ip', 1)); ?>" min="1" max="100" style="width: 60px;" />
                            <span>IP uniques</span>
                        </div>
                        
                        <div style="background: #f0f8ff; padding: 12px; border-radius: 5px; border-left: 4px solid #0073aa; margin: 10px 0;">
                            <h4 style="margin: 0 0 8px 0;">🎯 Fonctionnement amélioré :</h4>
                            <p style="margin: 5px 0;">• <strong>Collecte front-end authentique</strong> : Les IP sont récupérées lors des vraies visites des utilisateurs</p>
                            <p style="margin: 5px 0;">• <strong>Sélection aléatoire</strong> : Les commentaires sont ajoutés à des articles choisis au hasard parmi ceux ayant l'auto-commentaire activé</p>
                            <p style="margin: 5px 0;">• <strong>Distribution naturelle</strong> : Évite la systématisation en variant les articles concernés</p>
                            <p style="margin: 5px 0;">• <strong>Exemple</strong> : "2 commentaires / 15 IP" = 2 articles aléatoires recevront chacun 1 commentaire toutes les 15 visites uniques</p>
                        </div>
                        
                        <?php 
                        $global_ip_count = get_option('acg_global_ip_count', 0);
                        $last_ip_list = get_option('acg_last_ip_list', []);
                        $interval_per_ip = get_option('acg_interval_per_ip', 1);
                        $last_visitor_time = get_option('acg_last_visitor_ip_time', 0);
                        $last_comments_time = get_option('acg_comments_triggered_time', 0);
                        ?>
                        
                        <div style="background: #f9f9f9; padding: 12px; border-radius: 5px; margin: 10px 0;">
                            <h4 style="margin: 0 0 8px 0;">📊 Statut actuel :</h4>
                            <p style="margin: 5px 0;"><strong>IP uniques collectées :</strong> <span id="current-ip-count"><?php echo $global_ip_count; ?></span> / <?php echo $interval_per_ip; ?></p>
                            <p style="margin: 5px 0;"><strong>Prochains commentaires dans :</strong> <?php echo max(0, $interval_per_ip - $global_ip_count); ?> visites</p>
                            
                            <?php if ($last_visitor_time > 0): ?>
                                <p style="margin: 5px 0; font-size: 12px; color: #666;">
                                    <strong>Dernière visite détectée :</strong> <?php echo date('d/m/Y à H:i:s', $last_visitor_time); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($last_comments_time > 0): ?>
                                <p style="margin: 5px 0; font-size: 12px; color: #666;">
                                    <strong>Derniers commentaires générés :</strong> <?php echo date('d/m/Y à H:i:s', $last_comments_time); ?>
                                </p>
                            <?php endif; ?>
                            
                            <?php if ($global_ip_count > 0 && !empty($last_ip_list)): ?>
                                <p style="margin: 5px 0; font-size: 12px; color: #666;">
                                    <strong>Dernières IP visiteurs :</strong> <?php 
                                    $visible_ips = array_slice($last_ip_list, -3);
                                    // Masquer partiellement les IP pour la confidentialité
                                    $masked_ips = array_map(function($ip) {
                                        $parts = explode('.', $ip);
                                        if (count($parts) === 4) {
                                            return $parts[0] . '.' . $parts[1] . '.***.' . $parts[3];
                                        }
                                        return substr($ip, 0, -4) . '****'; // IPv6 ou autre format
                                    }, $visible_ips);
                                    echo implode(', ', $masked_ips);
                                    echo count($last_ip_list) > 3 ? '...' : '';
                                    ?>
                                </p>
                            <?php endif; ?>
                            
                            <button type="button" id="reset-ip-counter" class="button action" style="margin-top: 8px;">
                                🔄 Réinitialiser le compteur IP
                            </button>
                            <span id="reset-ip-status" style="margin-left: 10px; font-style: italic;"></span>
                            
                            <div style="margin-top: 10px; padding: 8px; background: #e7f3ff; border-radius: 3px;">
                                <p style="margin: 0; font-size: 11px; color: #0066cc;">
                                    <strong>ℹ️ Note technique :</strong> Les IP sont collectées en temps réel lors des visites front-end. 
                                    Compatible avec Cloudflare, proxies et load balancers. Les IP sont partiellement masquées pour la confidentialité.
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Modèle GPT</th>
                    <td>
                        <select name="acg_gpt_model">
                            <option value="gpt-4.1-mini" <?php selected($gpt_model, 'gpt-4.1-mini'); ?>>gpt-4.1-mini</option>
                            <option value="gpt-4.1" <?php selected($gpt_model, 'gpt-4.1'); ?>>gpt-4.1</option>
                            <option value="gpt-4o-mini" <?php selected($gpt_model, 'gpt-4o-mini'); ?>>gpt-4o-mini</option>
                            <option value="gpt-4o" <?php selected($gpt_model, 'gpt-4o'); ?>>gpt-4o</option>
                            <option value="gpt-3.5-turbo" <?php selected($gpt_model, 'gpt-3.5-turbo'); ?>>gpt-3.5-turbo</option>
                        </select>
                        <p>Sélectionnez un modèle d'OpenAI</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Nombre de mots (min)</th>
                    <td><input type="number" name="acg_min_words" value="<?php echo esc_attr($min_words); ?>" min="1" />
                        <p>Nombre de mots minimum dans un commentaire</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Nombre de mots (max)</th>
                    <td><input type="number" name="acg_max_words" value="<?php echo esc_attr($max_words); ?>" min="1" />
                        <p>Nombre de mots maximum dans un commentaire</p>
                    </td>
                </tr>

                <tr valign="top">
                    <td style="padding:0px !important;" colspan="2">
                        <h2 style="margin:8px 0px !important;">Contextualisation des personas</h2>
                        <p style="max-width: 640px;">Les personas peuvent être adaptés automatiquement à la thématique de votre site pour générer des commentaires plus pertinents et crédibles.</p>
                        
                        <?php 
                        // Inclure le fichier pour accéder aux fonctions
                        if (function_exists('acg_analyze_site_context')) {
                            $site_context = acg_analyze_site_context();
                        } else {
                            require_once plugin_dir_path(__FILE__) . 'generer-modele.php';
                            $site_context = acg_analyze_site_context();
                        }
                        ?>
                        
                        <div style="background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 10px 0;">
                            <h4>📊 Analyse de votre site :</h4>
                            <p><strong>Secteur détecté :</strong> <span id="detected-niche"><?php echo ucfirst($site_context['detected_niche']); ?></span>
                                <?php 
                                $cache_timestamp = get_option('acg_cached_site_niche_timestamp', 0);
                                $use_ai_detection = get_option('acg_use_ai_niche_detection', 1);
                                if ($cache_timestamp > 0 && $use_ai_detection): 
                                ?>
                                    <small style="color: #666;">(Analysé par OpenAI le <?php echo date('d/m/Y à H:i', $cache_timestamp); ?>)</small>
                                <?php else: ?>
                                    <small style="color: #666;">(Détection locale par mots-clés)</small>
                                <?php endif; ?>
                            </p>
                            <?php if (!empty($site_context['main_categories'])): ?>
                                <p><strong>Principales catégories :</strong> <span id="main-categories"><?php echo implode(', ', array_slice($site_context['main_categories'], 0, 5)); ?></span></p>
                            <?php endif; ?>
                            <?php if (!empty($site_context['popular_tags'])): ?>
                                <p><strong>Tags populaires :</strong> <span id="popular-tags"><?php echo implode(', ', array_slice($site_context['popular_tags'], 0, 8)); ?></span></p>
                            <?php endif; ?>
                            
                            <div style="margin-top: 15px;">
                                <button type="button" id="refresh-niche-detection" class="button action" style="margin-right: 10px;">
                                    🔄 Relancer la détection
                                </button>
                                <button type="button" id="test-new-detection" class="button button-secondary" style="margin-right: 10px;">
                                    🧪 Tester nouvelle détection
                                </button>
                                <span id="refresh-niche-status" style="color: #666; font-style: italic;"></span>
                            </div>
                            
                            <div id="test-results" style="display: none; margin-top: 15px; padding: 10px; background: #f0f8ff; border-radius: 5px; border-left: 4px solid #0073aa;">
                                <h4>🧪 Résultat du test :</h4>
                                <p><strong>Avant :</strong> <span id="old-detection"></span></p>
                                <p><strong>Maintenant :</strong> <span id="new-detection"></span></p>
                                <p style="font-size: 12px; color: #666;">Ce test utilise la liste élargie des secteurs (13 au lieu de 9). Utilisez "Relancer la détection" pour appliquer définitivement le nouveau résultat.</p>
                            </div>
                        </div>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Utiliser la contextualisation automatique</th>
                    <td>
                        <input type="checkbox" name="acg_use_site_context" value="1" <?php checked(get_option('acg_use_site_context', 1), 1); ?> />
                        <p>Adapte automatiquement les personas générés à la thématique détectée de votre site. Les personas auront des professions et centres d'intérêt cohérents avec votre contenu.</p>
                        
                        <?php if ($site_context['detected_niche'] !== 'général'): ?>
                            <p style="color: #0073aa;"><strong>✓ Recommandé :</strong> Votre site semble spécialisé en <em><?php echo $site_context['detected_niche']; ?></em>, la contextualisation améliorera la pertinence des commentaires.</p>
                        <?php else: ?>
                            <p style="color: #666;"><strong>ℹ️ Info :</strong> Aucune thématique spécifique détectée. Les personas seront générés de manière généraliste.</p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Méthode de détection de thématique</th>
                    <td>
                        <label>
                            <input type="radio" name="acg_use_ai_niche_detection" value="1" <?php checked(get_option('acg_use_ai_niche_detection', 1), 1); ?> />
                            <strong>Analyse OpenAI (Recommandé)</strong>
                        </label>
                        <p style="margin: 5px 0 10px 25px; color: #666;">Plus précise, analyse le contexte global du contenu. <em>Utilise votre clé API OpenAI.</em></p>
                        
                        <label>
                            <input type="radio" name="acg_use_ai_niche_detection" value="0" <?php checked(get_option('acg_use_ai_niche_detection', 1), 0); ?> />
                            <strong>Détection par mots-clés</strong>
                        </label>
                        <p style="margin: 5px 0 10px 25px; color: #666;">Méthode locale basée sur les catégories et tags. Gratuite mais moins précise.</p>
                        
                        <?php if (get_option('acg_use_ai_niche_detection', 1) && empty(get_option('acg_api_key', ''))): ?>
                            <p style="color: #d63638;"><strong>⚠️ Attention :</strong> Vous devez configurer votre clé API OpenAI pour utiliser l'analyse IA.</p>
                        <?php endif; ?>
                    </td>
                </tr>

                <tr valign="top">
                    <td style="padding:0px !important;" colspan="2">
                        <h2 style="margin:8px 0px !important;">Modèles de commentaires</h2>
                        <p style="max-width: 590px;">Chaque modèle peut comprendre des informations sur l'auteur (nom/prénom) ainsi que des caractéristiques spécifiques qui définissent le ton et le style du commentaire. Grâce à ces modèles, vous pouvez créer des personas en plus d'éviter les redondances de l'IA.</p><br>
                        <b>Vous pouvez générer ces modèles en masse avec gpt-4o-mini<?php echo ($site_context['detected_niche'] !== 'général') ? ' (adaptés à votre thématique ' . $site_context['detected_niche'] . ')' : ''; ?> :</b>
                        <div style="display: flex;flex-direction: column;align-items: flex-start;margin-bottom: 15px;">  
                            <p>Entrez le nombre de modèles à générer : 
                                <input type="number" id="template_count" min="1" value="1" style="width: 50px;" />
                            </p>
                            <div id="generated_templates"></div>
                            <button type="button" id="generate_templates_button" class="button action">Générer</button>
                        </div>
                        <hr>
                        <div id="writing-styles-container" style="gap: 10px; display: flex; flex-direction: column; margin-bottom: 10px;">
                            <style>
                                .writing-style{gap: 10px; display: flex; flex-direction: row; margin-bottom: 10px; flex-wrap: nowrap; align-content: center; align-items: center;}
                            </style>
                            <?php if (!empty($writing_styles)): ?>
                                <?php foreach ($writing_styles as $index => $style): ?>
                                    <div class="writing-style">
                                      <div style="display: flex; flex-direction: column; gap: 8px;">
                                          <span>Description des auteurs des commentaires (identité, style d'écriture..)</span>
                                          <textarea name="acg_writing_styles[<?php echo $index; ?>]" rows="4" cols="50"><?php echo esc_textarea($style); ?></textarea>
                                      </div>  
                                      <label>
                                           <input type="checkbox" name="acg_include_author_names[<?php echo $index; ?>]" value="1" <?php checked(isset($include_author_names[$index]) && $include_author_names[$index] == 1); ?> />
                                            S'adresse directement à l'auteur de l'article
                                      </label>
                                      <button type="button" class="button action remove-style-button">Supprimer</button>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p>Aucun modèle de commentaire n'est actuellement défini.</p>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button action" id="add-writing-style-button">Ajouter un modèle</button>
                    </td>
                </tr>

                <tr valign="top">
                    <th style="padding:0 !important;" scope="row" colspan="2"><hr /></th>
                </tr>
                <tr valign="top"><th scope="row" colspan="2"><h2 style="margin:8px 0px !important;">Commentaires automatiques</h2>
                    <p style="font-weight: 400; max-width: 640px;">
                        Vous pouvez créer des commentaires automatiquement à une fréquence donnée. Pour utiliser cette option, vous devez activer les cases à cocher "Commentaires automatiques" dans le tableau des publications sur la page listing des articles.
                    </p>
                </th></tr>

                <tr valign="top">
                    <th scope="row">Activer la génération de commentaires automatiques</th>                 
                    <td><input type="checkbox" name="acg_auto_comment_enabled" value="1" <?php checked($auto_comment_enabled, 1); ?> />  
                        <p>Cette option permet de générer automatiquement les commentaires sur les articles qui ont la case cochée "commentaire automatique".</p>
                    </td>
                </tr>

                <!-- Nouvelle option : désactiver par tranche horaire -->
                <tr valign="top">
                    <th scope="row">Désactiver les commentaires automatiques sur une plage horaire</th>
                    <td>
                        <input type="checkbox" id="acg_disable_auto_comment_hours" name="acg_disable_auto_comment_hours" value="1"
                            <?php checked(get_option('acg_disable_auto_comment_hours'), 1); ?> />
                        <label for="acg_disable_auto_comment_hours">Activer la restriction horaire</label>
                        <div id="acg_hour_range_fields" style="<?php echo get_option('acg_disable_auto_comment_hours') ? '' : 'display:none;'; ?> margin-top:10px;">
                            <label for="acg_disable_auto_comment_start_hour" style="margin-right:10px;">Heure de début:</label>
                            <input type="time" name="acg_disable_auto_comment_start_hour" id="acg_disable_auto_comment_start_hour"
                                value="<?php echo esc_attr(get_option('acg_disable_auto_comment_start_hour', '03:00')); ?>" min="00:00" max="23:59"
                            />
                            <label for="acg_disable_auto_comment_end_hour" style="margin-left:20px;margin-right:10px;">Heure de fin:</label>
                            <input type="time" name="acg_disable_auto_comment_end_hour" id="acg_disable_auto_comment_end_hour"
                                value="<?php echo esc_attr(get_option('acg_disable_auto_comment_end_hour', '07:00')); ?>" min="00:00" max="23:59"
                            />
                        </div>
                        <p>Les commentaires automatiques NE seront PAS publiés dans cette tranche horaire.<br>
                        Exemple : 3h00–7h00 = Pas de commentaires générés par l’IA entre 3h et 7h du matin.</p>
                    </td>
                </tr>

                <tr valign="top">
                    <th scope="row">Activer les commentaires automatiques pour les nouvelles publications</th>
                    <td>
                        <input type="checkbox" id="acg_auto_comment_default" name="acg_auto_comment_default" value="1" <?php checked($auto_comment_default, 1); ?> />
                        <p>Cette option permet de cocher la case "commentaire automatique" par défaut sur les nouvelles publications.</p>
                        
                        <div id="auto-comment-default-mode-container" style="<?php echo ($auto_comment_default ? '' : 'display:none;'); ?> margin-top:10px;">
    <label for="acg_auto_comment_default_mode"><b>Mode</b> :</label>
    <select name="acg_auto_comment_default_mode" id="acg_auto_comment_default_mode">
        <option value="all" <?php selected($auto_comment_default_mode, 'all'); ?>>Activer la case sur toutes les publications</option>
        <option value="frequency" <?php selected($auto_comment_default_mode, 'frequency'); ?>>Activer la case toutes les X publications</option>
        <option value="random" <?php selected($auto_comment_default_mode, 'random'); ?>>Activer la case aléatoirement (50% de chance)</option>
    </select>
    <span id="auto_comment_default_frequency_container" style="<?php echo ($auto_comment_default_mode === 'frequency') ? '' : 'display:none;'; ?>">
        <input type="number" name="acg_auto_comment_default_frequency" id="acg_auto_comment_default_frequency"
            value="<?php echo esc_attr($auto_comment_default_frequency); ?>" style="width:70px;" min="1" /> publications
    </span>
</div>
                        
                        
                        <div id="auto-comment-delay-container" style="<?php echo $delay_display; ?>">
                            <label for="acg_auto_comment_delay">Délai (minutes) avant la publication des commentaires :</label>
                            <input type="number" name="acg_auto_comment_delay" value="<?php echo esc_attr(get_option('acg_auto_comment_delay', 30)); ?>" min="0" />
                            <p>Temps d'attente avant la première publication de commentaires après la publication d'un nouvel article.</p>
                        </div>
                    </td>
                </tr>
                
                <tr valign="top" id="cron-settings-row" style="<?php echo $comment_publish_mode === 'visits' ? 'display: none;' : ''; ?>">
                    <th scope="row">Planifier les commentaires</th>
                    <td>
                        Publier entre <input style="width:50px;" type="number" name="acg_comment_min_per_post" value="<?php echo esc_attr(get_option('acg_comment_min_per_post', 1)); ?>" min="1" /> et <input style="width:50px;" type="number" name="acg_comment_max_per_post" value="<?php echo esc_attr(get_option('acg_comment_max_per_post', 5)); ?>" min="1" /> commentaires toutes les <input style="width:50px;" type="number" name="acg_cron_interval" value="<?php echo esc_attr($cron_interval); ?>" min="1" /> minutes par publication.
                        <p>Ces commentaires sont générés tant que l'option "Commentaire automatique" est activée sur l'article. Vous pouvez la désactiver à tout moment pour arrêter la génération.</p>
                    </td>
                </tr>
                
                <tr valign="top" id="duration-limits-row" style="<?php echo $comment_publish_mode === 'visits' ? 'display: none;' : ''; ?>">
                    <th scope="row">Garde-fous intelligents</th>
                    <td>
                        <div style="background: #fff3cd; padding: 12px; border-radius: 5px; border-left: 4px solid #ffc107; margin-bottom: 15px;">
                            <h4 style="margin: 0 0 8px 0;">🛡️ Protection contre la génération illimitée</h4>
                            <p style="margin: 5px 0;">Pour éviter les coûts API excessifs et maintenir la crédibilité, des limites automatiques sont appliquées :</p>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                            <div>
                                <label><strong>Limite par âge d'article :</strong></label><br>
                                <input type="number" name="acg_max_article_age_days" value="<?php echo esc_attr(get_option('acg_max_article_age_days', 30)); ?>" min="1" max="365" style="width: 60px;" /> jours
                                <p style="font-size: 12px; color: #666; margin: 5px 0;">Auto-désactivation après X jours</p>
                            </div>
                            
                            <div>
                                <label><strong>Limite de commentaires générés :</strong></label><br>
                                <input type="number" name="acg_max_plugin_comments_per_post" value="<?php echo esc_attr(get_option('acg_max_plugin_comments_per_post', 25)); ?>" min="1" max="100" style="width: 60px;" /> commentaires
                                <p style="font-size: 12px; color: #666; margin: 5px 0;">Maximum créés par le plugin</p>
                            </div>
                        </div>
                        
                        <div>
                            <label><strong>Seuil de sécurité global :</strong></label>
                            <input type="number" name="acg_max_total_comments_per_post" value="<?php echo esc_attr(get_option('acg_max_total_comments_per_post', 50)); ?>" min="1" max="200" style="width: 60px;" /> commentaires total
                            <p style="font-size: 12px; color: #666; margin: 5px 0;">Auto-désactivation si trop de commentaires (tous confondus)</p>
                        </div>
                        
                        <div style="background: #d1ecf1; padding: 10px; border-radius: 5px; margin-top: 15px;">
                            <p style="margin: 0; font-size: 12px;"><strong>💡 Comment ça marche :</strong> L'auto-comment se désactive automatiquement sur un article quand une limite est atteinte. Vous pouvez le réactiver manuellement si besoin.</p>
                        </div>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        
        <!-- Section de maintenance -->
        <div style="margin-top: 30px; padding: 15px; background: #fff; border: 1px solid #ccd0d4; border-radius: 4px;">
            <h2>🧹 Maintenance</h2>
            <p>Cette version a supprimé la fonctionnalité de limite maximale de commentaires par article (qui était arbitraire et déroutante).</p>
            <p>Vous pouvez nettoyer les anciennes données inutiles de votre base de données :</p>
            
            <button type="button" id="cleanup-deprecated-data" class="button action">
                🗑️ Nettoyer les anciennes données
            </button>
            <span id="cleanup-status" style="margin-left: 10px; font-style: italic;"></span>
            
            <p style="font-size: 12px; color: #666; margin-top: 10px;">
                <strong>Contrôle des commentaires :</strong> Utilisez maintenant le bouton on/off "Commentaire automatique" sur chaque article pour contrôler la génération. Plus simple et plus clair !
            </p>
        </div>
    </div>

<script>
(function(){
    // Gestion dynamique des options visibles/masquées
    function updateOptionsVisibility() {
        var mode = document.getElementById('comment_publish_mode').value;
        var autoCommentDefault = document.getElementById('acg_auto_comment_default').checked;
        document.getElementById('ip-comment-interval-row').style.display    = (mode === 'visits')   ? '' : 'none';
        document.getElementById('cron-settings-row').style.display  = (mode === 'visits')   ? 'none' : '';
        document.getElementById('duration-limits-row').style.display = (mode === 'visits')   ? 'none' : '';
        document.getElementById('auto-comment-delay-container').style.display = (mode === 'duration' && autoCommentDefault) ? '' : 'none';
    }
    document.getElementById('comment_publish_mode').addEventListener('change', updateOptionsVisibility);
    document.getElementById('acg_auto_comment_default').addEventListener('change', updateOptionsVisibility);
    document.addEventListener('DOMContentLoaded', updateOptionsVisibility);

    // Plage horaire activation
    document.getElementById('acg_disable_auto_comment_hours').addEventListener('change', function() {
        document.getElementById('acg_hour_range_fields').style.display = this.checked ? '' : 'none';
    });

    // Gestion modèles de commentaires (add/supprimer/génération)
    function bindRemoveButtons() {
        document.querySelectorAll('.remove-style-button').forEach(function(button) {
            button.onclick = function() {
                button.closest('.writing-style').remove();
            }
        });
    }
    bindRemoveButtons();
    // Ajouter nouveau modèle
    document.getElementById('add-writing-style-button').addEventListener('click', function () {
        var container = document.getElementById('writing-styles-container');
        var nextIndex = container.querySelectorAll('.writing-style').length;
        var div = document.createElement('div');
        div.className = 'writing-style';
        div.innerHTML = `
          <div style="display: flex; flex-direction: column; gap: 8px;">
            <span>Description des auteurs des commentaires (identité, style d'écriture..)</span>
            <textarea name="acg_writing_styles[`+nextIndex+`]" rows="4" cols="50"></textarea>
          </div>
          <label>
            <input type="checkbox" name="acg_include_author_names[`+nextIndex+`]" value="1" />
            S'adresse directement à l'auteur de l'article
          </label>
          <button type="button" class="button action remove-style-button">Supprimer</button>
        `;
        container.appendChild(div);
        bindRemoveButtons();
    });
    // Génération IA (ajax, si activée côté serveur)
    document.getElementById('generate_templates_button').addEventListener('click', function() {
        var count = parseInt(document.getElementById('template_count').value);
        if (isNaN(count) || count < 1) {
            alert("Veuillez entrer un nombre valide.");
            return;
        }
        var generatedTemplatesContainer = document.getElementById('generated_templates');
        generatedTemplatesContainer.innerHTML = "";
        var index = 0;
        function generateTemplate() {
            if (index >= count) return;
            var loadingMessage = document.createElement('p');
            loadingMessage.textContent = "Génération du template " + (index + 1) + " en cours...";
            generatedTemplatesContainer.appendChild(loadingMessage);
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'acg_generate_comment_templates',
                    count: 1,
                    nonce: '<?php echo wp_create_nonce('generate_templates_nonce'); ?>'
                },
                success: function(response) {
                    loadingMessage.remove();
                    if (response.success) {
                        var template = response.data.templates[0];
                        var writingStylesContainer = document.getElementById('writing-styles-container');
                        var nextIndex = writingStylesContainer.querySelectorAll('.writing-style').length;
                        var div = document.createElement('div');
                        div.className = 'writing-style';
                        div.innerHTML = `
                          <div style="display: flex; flex-direction: column; gap: 8px;">
                            <span>Description des auteurs des commentaires (identité, style d'écriture..)</span>
                            <textarea name="acg_writing_styles[`+nextIndex+`]" rows="4" cols="50"></textarea>
                          </div>
                          <label>
                            <input type="checkbox" name="acg_include_author_names[`+nextIndex+`]" value="1" />
                            S'adresse directement à l'auteur de l'article
                          </label>
                          <button type="button" class="button action remove-style-button">Supprimer</button>
                        `;
                        writingStylesContainer.appendChild(div);
                        bindRemoveButtons();
                        div.querySelector('textarea').value = template;
                        index++;
                        generateTemplate();
                    } else {
                        alert("Erreur lors de la génération des templates: " + response.data.message);
                    }
                },
                error: function() {
                    loadingMessage.remove();
                    alert("Une erreur s'est produite lors de la communication avec le serveur.");
                }
            });
        }
        generateTemplate();
    });
})();
    
    
    
        function updateDefaultModeVisibility() {
        var defaultChecked = document.getElementById('acg_auto_comment_default').checked;
        document.getElementById('auto-comment-default-mode-container').style.display = defaultChecked ? '' : 'none';
        var mode = document.getElementById('acg_auto_comment_default_mode').value;
        document.getElementById('auto_comment_default_frequency_container').style.display = (mode === 'frequency') ? '' : 'none';
    }
    document.getElementById('acg_auto_comment_default_mode').addEventListener('change', updateDefaultModeVisibility);
    document.getElementById('acg_auto_comment_default').addEventListener('change', updateDefaultModeVisibility);
    document.addEventListener('DOMContentLoaded', updateDefaultModeVisibility);
    
    
</script>

<script>
    // Gestion du bouton de re-détection de niche
    document.getElementById('refresh-niche-detection').addEventListener('click', function() {
        var button = this;
        var statusSpan = document.getElementById('refresh-niche-status');
        
        // Désactiver le bouton et afficher le statut
        button.disabled = true;
        button.textContent = '🔄 Analyse en cours...';
        statusSpan.textContent = 'Analyse de votre site par OpenAI...';
        statusSpan.style.color = '#0073aa';
        
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'acg_refresh_niche_detection',
                nonce: '<?php echo wp_create_nonce('refresh_niche_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Mettre à jour l'affichage
                    document.getElementById('detected-niche').textContent = response.data.niche.charAt(0).toUpperCase() + response.data.niche.slice(1);
                    
                    if (response.data.categories && response.data.categories.length > 0) {
                        document.getElementById('main-categories').textContent = response.data.categories.join(', ');
                    }
                    
                    if (response.data.tags && response.data.tags.length > 0) {
                        document.getElementById('popular-tags').textContent = response.data.tags.join(', ');
                    }
                    
                    statusSpan.textContent = '✅ Détection mise à jour avec succès !';
                    statusSpan.style.color = '#00a32a';
                    
                    // Actualiser automatiquement la page après 2 secondes pour voir les nouveaux indicateurs de cache
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    statusSpan.textContent = '❌ Erreur : ' + (response.data.message || 'Impossible de détecter la niche');
                    statusSpan.style.color = '#d63638';
                }
            },
            error: function() {
                statusSpan.textContent = '❌ Erreur de communication avec le serveur';
                statusSpan.style.color = '#d63638';
            },
            complete: function() {
                // Réactiver le bouton
                button.disabled = false;
                button.textContent = '🔄 Relancer la détection';
                
                // Effacer le statut après 5 secondes
                setTimeout(function() {
                    statusSpan.textContent = '';
                }, 5000);
            }
        });
    });
    
    // Gestion du bouton de test de la nouvelle détection
    document.getElementById('test-new-detection').addEventListener('click', function() {
        var button = this;
        var statusSpan = document.getElementById('refresh-niche-status');
        var testResults = document.getElementById('test-results');
        
        // Désactiver le bouton et afficher le statut
        button.disabled = true;
        button.textContent = '🧪 Test en cours...';
        statusSpan.textContent = 'Test de la détection améliorée...';
        statusSpan.style.color = '#0073aa';
        testResults.style.display = 'none';
        
        jQuery.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: {
                action: 'acg_test_niche_detection',
                nonce: '<?php echo wp_create_nonce('test_niche_nonce'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    // Afficher les résultats du test
                    document.getElementById('old-detection').textContent = response.data.old_niche.charAt(0).toUpperCase() + response.data.old_niche.slice(1);
                    document.getElementById('new-detection').textContent = response.data.new_niche.charAt(0).toUpperCase() + response.data.new_niche.slice(1);
                    
                    testResults.style.display = 'block';
                    
                    if (response.data.old_niche !== response.data.new_niche) {
                        statusSpan.textContent = '✅ Amélioration détectée ! La nouvelle méthode donne un résultat différent.';
                        statusSpan.style.color = '#00a32a';
                        document.getElementById('new-detection').style.color = '#00a32a';
                        document.getElementById('new-detection').style.fontWeight = 'bold';
                    } else {
                        statusSpan.textContent = '👌 Même résultat avec la nouvelle méthode.';
                        statusSpan.style.color = '#0073aa';
                    }
                } else {
                    statusSpan.textContent = '❌ Erreur test : ' + (response.data.message || 'Impossible de tester');
                    statusSpan.style.color = '#d63638';
                }
            },
            error: function() {
                statusSpan.textContent = '❌ Erreur de communication lors du test';
                statusSpan.style.color = '#d63638';
            },
            complete: function() {
                // Réactiver le bouton
                button.disabled = false;
                button.textContent = '🧪 Tester nouvelle détection';
            }
        });
    });
</script>

<script>
    // Gestion du bouton de réinitialisation du compteur IP
    document.getElementById('reset-ip-counter')?.addEventListener('click', function() {
        var button = this;
        var statusSpan = document.getElementById('reset-ip-status');
        
        if (confirm('Êtes-vous sûr de vouloir réinitialiser le compteur d\'IP ? Les commentaires seront déclenchés dès la prochaine visite.')) {
            // Désactiver le bouton et afficher le statut
            button.disabled = true;
            button.textContent = '🔄 Réinitialisation...';
            statusSpan.textContent = 'Réinitialisation en cours...';
            statusSpan.style.color = '#0073aa';
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'acg_reset_ip_counter',
                    nonce: '<?php echo wp_create_nonce('reset_ip_counter_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        // Mettre à jour l'affichage
                        document.getElementById('current-ip-count').textContent = '0';
                        
                        statusSpan.textContent = '✅ Compteur réinitialisé avec succès !';
                        statusSpan.style.color = '#00a32a';
                        
                        // Actualiser la page après 2 secondes pour voir la mise à jour complète
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        statusSpan.textContent = '❌ Erreur : ' + (response.data.message || 'Impossible de réinitialiser');
                        statusSpan.style.color = '#d63638';
                    }
                },
                error: function() {
                    statusSpan.textContent = '❌ Erreur de communication avec le serveur';
                    statusSpan.style.color = '#d63638';
                },
                complete: function() {
                    // Réactiver le bouton
                    button.disabled = false;
                    button.textContent = '🔄 Réinitialiser le compteur IP';
                    
                    // Effacer le statut après 5 secondes
                    setTimeout(function() {
                        statusSpan.textContent = '';
                    }, 5000);
                }
            });
        }
    });
</script>

<script>
    // Gestion du bouton de nettoyage des données dépréciées
    document.getElementById('cleanup-deprecated-data')?.addEventListener('click', function() {
        var button = this;
        var statusSpan = document.getElementById('cleanup-status');
        
        if (confirm('Êtes-vous sûr de vouloir nettoyer les anciennes données ? Cette action supprimera les limites maximales obsolètes de la base de données.')) {
            // Désactiver le bouton et afficher le statut
            button.disabled = true;
            button.textContent = '🗑️ Nettoyage en cours...';
            statusSpan.textContent = 'Suppression des anciennes données...';
            statusSpan.style.color = '#0073aa';
            
            jQuery.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                data: {
                    action: 'acg_cleanup_deprecated_data',
                    nonce: '<?php echo wp_create_nonce('cleanup_deprecated_nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        statusSpan.textContent = '✅ ' + response.data.message;
                        statusSpan.style.color = '#00a32a';
                        
                        // Masquer le bouton car nettoyage terminé
                        setTimeout(function() {
                            button.style.display = 'none';
                            statusSpan.textContent = '✅ Base de données nettoyée !';
                        }, 3000);
                    } else {
                        statusSpan.textContent = '❌ Erreur : ' + (response.data.message || 'Impossible de nettoyer');
                        statusSpan.style.color = '#d63638';
                    }
                },
                error: function() {
                    statusSpan.textContent = '❌ Erreur de communication avec le serveur';
                    statusSpan.style.color = '#d63638';
                },
                complete: function() {
                    // Réactiver le bouton si pas masqué
                    if (button.style.display !== 'none') {
                        button.disabled = false;
                        button.textContent = '🗑️ Nettoyer les anciennes données';
                    }
                    
                    // Effacer le statut après 10 secondes
                    setTimeout(function() {
                        if (statusSpan.textContent.includes('❌')) {
                            statusSpan.textContent = '';
                        }
                    }, 10000);
                }
            });
        }
    });
</script>

<?php
}

function acg_set_auto_comment_default($post_id, $post, $update) {
    // Eviter les autosaves/révisions
    if ($update) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    $all_types = get_post_types(['public' => true, 'show_ui' => true]);
    if (!in_array(get_post_type($post_id), $all_types)) return;

    // Ne s'applique QUE à la création
    $auto_comment_default = get_option('acg_auto_comment_default', 1);
    $mode = get_option('acg_auto_comment_default_mode', 'all');
    $frequency = max(intval(get_option('acg_auto_comment_default_frequency', 2)), 1);

    if (!$auto_comment_default) {
        update_post_meta($post_id, '_acg_auto_comment_enabled', '0');
        return;
    }

    $enabled = '0';
    switch ($mode) {
        case 'all':
            $enabled = '1';
            break;
        case 'frequency':
            // On stocke le nombre d'articles créés
            $counter = intval(get_option('acg_auto_comment_post_counter', 0)) + 1;
            update_option('acg_auto_comment_post_counter', $counter);
            // Seule la publication courante est impactée
            $enabled = ($frequency && ($counter % $frequency) === 0) ? '1' : '0';
            break;
        case 'random':
            $enabled = (mt_rand(0, 1) === 1) ? '1' : '0';
            break;
        default:
            $enabled = '1';
    }
    update_post_meta($post_id, '_acg_auto_comment_enabled', $enabled);
}
add_action('wp_insert_post', 'acg_set_auto_comment_default', 10, 3);

function acg_register_settings() {
    register_setting('acg_options_group', 'acg_api_key');
    register_setting('acg_options_group', 'acg_writing_styles');
    register_setting('acg_options_group', 'acg_include_author_names');
    register_setting('acg_options_group', 'acg_min_words');
    register_setting('acg_options_group', 'acg_max_words');
    register_setting('acg_options_group', 'acg_auto_comment_enabled'); 
    register_setting('acg_options_group', 'acg_gpt_model'); 
    register_setting('acg_options_group', 'acg_comment_count'); 
    register_setting('acg_options_group', 'acg_cron_interval'); 
    register_setting('acg_options_group', 'acg_comment_min_per_post'); 
    register_setting('acg_options_group', 'acg_comment_max_per_post'); 
    register_setting('acg_options_group', 'acg_auto_comment_default'); 
    register_setting('acg_options_group', 'acg_comment_publish_mode'); 
    register_setting('acg_options_group', 'acg_comment_per_ip'); 
    register_setting('acg_options_group', 'acg_interval_per_ip');
    register_setting('acg_options_group', 'acg_disable_auto_comment_hours');
    register_setting('acg_options_group', 'acg_disable_auto_comment_start_hour');
    register_setting('acg_options_group', 'acg_disable_auto_comment_end_hour');
    register_setting('acg_options_group', 'acg_auto_comment_delay');
    register_setting('acg_options_group', 'acg_auto_comment_default_mode');
    register_setting('acg_options_group', 'acg_auto_comment_default_frequency');
    register_setting('acg_options_group', 'acg_use_site_context');
    register_setting('acg_options_group', 'acg_use_ai_niche_detection');
    // Nouvelles options garde-fous
    register_setting('acg_options_group', 'acg_max_article_age_days');
    register_setting('acg_options_group', 'acg_max_plugin_comments_per_post');
    register_setting('acg_options_group', 'acg_max_total_comments_per_post');
}
add_action('admin_init', 'acg_register_settings');