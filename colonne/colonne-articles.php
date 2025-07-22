<?php  

if (!defined('ABSPATH')) {
    exit; 
}

function acg_add_auto_comment_column($columns) {
    $columns['auto_comment'] = 'Commentaire automatique';
    $columns['comment_count'] = 'Commentaires';
    $columns['plugin_comments'] = 'Générés (Plugin)';
    return $columns;
}

// Contenu des colonnes
function acg_auto_comment_column_content($column_name, $post_id) {
    if ($column_name === 'auto_comment') {
        $is_enabled = get_post_meta($post_id, '_acg_auto_comment_enabled', true);
        $comment_publish_mode = get_option('acg_comment_publish_mode', 'duration');
        
        echo '<input type="checkbox" class="acg-auto-comment-toggle" data-post-id="' . esc_attr($post_id) . '" ' . checked($is_enabled, '1', false) . ' />';
        
        // Indicateur pour le mode IP
        if ($is_enabled && $comment_publish_mode === 'visits') {
            echo '<div style="margin-top: 5px; font-size: 11px; color: #0073aa;">🎲 Sélection aléatoire</div>';
        }
        
        // Afficher le timer SI mode durée ET activé ET délai restant
        if ($is_enabled && $comment_publish_mode === 'duration') {
            $auto_comment_delay = (int) get_option('acg_auto_comment_delay', 0);
            
            if ($auto_comment_delay > 0) {
                $published_time = strtotime(get_post_field('post_date_gmt', $post_id));
                $current_time = time();
                $delay_sec = $auto_comment_delay * 60;
                $time_left = ($published_time + $delay_sec) - $current_time;

                if ($time_left > 0) {
                    // minutes arrondi supérieur
                    $minutes = ceil($time_left / 60);
                    // Affichage en français
                    echo '<div class="acg-auto-comment-timer" style="white-space:nowrap;float:right;display:contents;">Dans ';
                    if ($minutes < 2) {
                        echo $minutes . ' minute';
                    } else {
                        echo $minutes . ' minutes';
                    }
                    echo '</div>';
                }
            }
        }
        
        // Afficher les garde-fous en mode durée
        if ($is_enabled && $comment_publish_mode === 'duration') {
            $plugin_comments = get_post_meta($post_id, '_acg_generated_comments_count', true) ?: 0;
            $max_plugin_comments = get_option('acg_max_plugin_comments_per_post', 25);
            $total_comments = wp_count_comments($post_id)->total_comments;
            $max_total_comments = get_option('acg_max_total_comments_per_post', 50);
            $published_time = strtotime(get_post_field('post_date_gmt', $post_id));
            $article_age_days = (time() - $published_time) / (24 * 3600);
            $max_age_days = get_option('acg_max_article_age_days', 30);
            
            $warnings = [];
            if ($plugin_comments >= $max_plugin_comments * 0.8) {
                $warnings[] = "⚠️ Limite plugin proche";
            }
            if ($total_comments >= $max_total_comments * 0.8) {
                $warnings[] = "⚠️ Limite totale proche";
            }
            if ($article_age_days >= $max_age_days * 0.8) {
                $warnings[] = "⚠️ Article ancien";
            }
            
            if (!empty($warnings)) {
                echo '<div style="margin-top: 3px; font-size: 10px; color: #d63638;">' . implode('<br>', $warnings) . '</div>';
            }
        }
    } elseif ($column_name === 'comment_count') {
        $comments_count = wp_count_comments($post_id)->total_comments;
        echo esc_html($comments_count);
    } elseif ($column_name === 'plugin_comments') {
        $plugin_comments = get_post_meta($post_id, '_acg_generated_comments_count', true) ?: 0;
        $max_plugin_comments = get_option('acg_max_plugin_comments_per_post', 25);
        
        $percentage = $max_plugin_comments > 0 ? ($plugin_comments / $max_plugin_comments) * 100 : 0;
        $color = '#000';
        
        if ($percentage >= 80) {
            $color = '#d63638'; // Rouge si proche limite
        } elseif ($percentage >= 60) {
            $color = '#dba617'; // Jaune si en approche
        }
        
        echo '<span style="color: ' . $color . ';">' . esc_html($plugin_comments) . '</span>';
        echo '<span style="color: #666; font-size: 11px;"> / ' . $max_plugin_comments . '</span>';
    }
}

// === ACTIONS EN LOT (BULK ACTIONS) ===

// Ajouter les actions en lot au dropdown
function acg_add_bulk_actions($bulk_actions) {
    $bulk_actions['acg_enable_auto_comment'] = '✅ Activer commentaires automatiques';
    $bulk_actions['acg_disable_auto_comment'] = '❌ Désactiver commentaires automatiques';
    return $bulk_actions;
}

// Traiter les actions en lot
function acg_handle_bulk_actions($redirect_to, $action, $post_ids) {
    // Vérifier les permissions
    if (!current_user_can('edit_posts')) {
        return $redirect_to;
    }
    
    // Vérifier les actions que nous gérons
    if (!in_array($action, ['acg_enable_auto_comment', 'acg_disable_auto_comment'])) {
        return $redirect_to;
    }
    
    $processed = 0;
    $enabled_value = ($action === 'acg_enable_auto_comment') ? '1' : '0';
    
    foreach ($post_ids as $post_id) {
        // Vérifier que l'utilisateur peut modifier cet article
        if (!current_user_can('edit_post', $post_id)) {
            continue;
        }
        
        // Mettre à jour la meta
        update_post_meta($post_id, '_acg_auto_comment_enabled', $enabled_value);
        $processed++;
    }
    
    // Ajouter les paramètres de résultat à l'URL de redirection
    $redirect_to = add_query_arg([
        'acg_bulk_action' => $action,
        'acg_processed' => $processed,
        'acg_total' => count($post_ids)
    ], $redirect_to);
    
    return $redirect_to;
}

// Afficher les notices de résultat des actions en lot
function acg_bulk_action_admin_notice() {
    if (!isset($_GET['acg_bulk_action']) || !isset($_GET['acg_processed'])) {
        return;
    }
    
    $action = sanitize_text_field($_GET['acg_bulk_action']);
    $processed = intval($_GET['acg_processed']);
    $total = intval($_GET['acg_total']);
    
    if ($processed == 0) {
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo '⚠️ Aucun article n\'a pu être traité. Vérifiez vos permissions.';
        echo '</p></div>';
        return;
    }
    
    $skipped = $total - $processed;
    
    if ($action === 'acg_enable_auto_comment') {
        $class = 'notice-success';
        $icon = '✅';
        $message = sprintf(
            _n(
                'Commentaires automatiques activés sur %s article.',
                'Commentaires automatiques activés sur %s articles.',
                $processed
            ),
            number_format_i18n($processed)
        );
    } else {
        $class = 'notice-success';
        $icon = '❌';
        $message = sprintf(
            _n(
                'Commentaires automatiques désactivés sur %s article.',
                'Commentaires automatiques désactivés sur %s articles.',
                $processed
            ),
            number_format_i18n($processed)
        );
    }
    
    echo '<div class="notice ' . $class . ' is-dismissible"><p>';
    echo $icon . ' ' . $message;
    
    if ($skipped > 0) {
        echo ' <em>(' . sprintf(
            _n(
                '%s article ignoré par manque de permissions.',
                '%s articles ignorés par manque de permissions.',
                $skipped
            ),
            number_format_i18n($skipped)
        ) . ')</em>';
    }
    
    echo '</p></div>';
}

// Ajouter les colonnes et actions à TOUS les post types publics
add_action('init', function () {
    $all_types = get_post_types(['public' => true, 'show_ui' => true]);
    foreach ($all_types as $post_type) {
        // Colonnes
        add_filter("manage_{$post_type}_posts_columns", 'acg_add_auto_comment_column');
        add_action("manage_{$post_type}_posts_custom_column", 'acg_auto_comment_column_content', 10, 2);
        
        // Actions en lot
        add_filter("bulk_actions-edit-{$post_type}", 'acg_add_bulk_actions');
        add_filter("handle_bulk_actions-edit-{$post_type}", 'acg_handle_bulk_actions', 10, 3);
    }
});

// Notice d'admin pour les résultats des actions en lot
add_action('admin_notices', 'acg_bulk_action_admin_notice');

// === FONCTIONS AJAX EXISTANTES ===

// enregistrer la valeur de la case à cocher en ajax
function acg_save_auto_comment() {
    if (isset($_POST['post_id']) && isset($_POST['enabled'])) {
        $post_id = intval($_POST['post_id']);
        $enabled = $_POST['enabled'] === 'true' ? '1' : '0';
        update_post_meta($post_id, '_acg_auto_comment_enabled', $enabled);
        wp_send_json_success();
    }
    wp_send_json_error();
}

add_action('wp_ajax_acg_save_auto_comment', 'acg_save_auto_comment');

function acg_enqueue_auto_comment_script() {
    ?>
    <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.acg-auto-comment-toggle').on('change', function() {
                var postId = $(this).data('post-id');
                var isChecked = $(this).is(':checked');
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'acg_save_auto_comment',
                        post_id: postId,
                        enabled: isChecked
                    },
                    success: function(response) {
                        if (!response.success) {
                            alert('Une erreur s\'est produite. Veuillez réessayer.');
                        }
                    },
                    error: function() {
                        alert('Une erreur s\'est produite lors de la communication avec le serveur.');
                    }
                });
            });
        });
    </script>
    <?php
}

add_action('admin_footer', 'acg_enqueue_auto_comment_script');