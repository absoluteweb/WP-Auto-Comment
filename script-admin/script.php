<?php  

if (!defined('ABSPATH')) {
    exit; 
}

// === COLLECTE D'IP FRONT-END (MODE VISITES) ===

// Collecter l'IP du visiteur réel lors des visites front-end
function acg_collect_visitor_ip() {
    // Uniquement si le mode visites est activé
    $comment_publish_mode = get_option('acg_comment_publish_mode', 'duration');
    if ($comment_publish_mode !== 'visits') {
        return;
    }
    
    // Uniquement si le plugin est activé
    $enabled = get_option('acg_auto_comment_enabled', 1);
    if (!$enabled) {
        return;
    }
    
    // Récupérer la vraie IP du visiteur avec fallbacks pour proxies/CDN
    $visitor_ip = acg_get_real_visitor_ip();
    
    if (empty($visitor_ip) || $visitor_ip === 'unknown') {
        return;
    }
    
    // Récupérer les données actuelles
    $global_ip_count = get_option('acg_global_ip_count', 0);
    $last_ip_list = get_option('acg_last_ip_list', []);
    $interval_per_ip = get_option('acg_interval_per_ip', 1);
    
    // Vérifier si cette IP est nouvelle (pas vue récemment)
    if (!in_array($visitor_ip, $last_ip_list)) {
        $last_ip_list[] = $visitor_ip;
        $global_ip_count++;
        
        // Garder seulement les X dernières IP pour éviter une liste trop longue
        $max_ip_history = max($interval_per_ip * 2, 10); // Minimum 10 pour éviter les répétitions trop rapides
        if (count($last_ip_list) > $max_ip_history) {
            $last_ip_list = array_slice($last_ip_list, -$max_ip_history);
        }
        
        // Sauvegarder les nouvelles données
        update_option('acg_global_ip_count', $global_ip_count);
        update_option('acg_last_ip_list', $last_ip_list);
        update_option('acg_last_visitor_ip_time', time()); // Timestamp de la dernière IP collectée
        
        // Log pour debug (optionnel)
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[WP Auto Comment] Nouvelle IP collectée: ' . $visitor_ip . ' (Total: ' . $global_ip_count . '/' . $interval_per_ip . ')');
        }
    }
}

// Récupérer la vraie IP du visiteur (gestion proxies, CDN, etc.)
function acg_get_real_visitor_ip() {
    // Ordre de priorité pour récupérer la vraie IP
    $ip_headers = [
        'HTTP_CF_CONNECTING_IP',     // Cloudflare
        'HTTP_CLIENT_IP',            // Proxy
        'HTTP_X_FORWARDED_FOR',      // Load balancer/proxy
        'HTTP_X_FORWARDED',          // Proxy
        'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
        'HTTP_FORWARDED_FOR',        // Proxy
        'HTTP_FORWARDED',            // Proxy
        'REMOTE_ADDR'                // IP directe (standard)
    ];
    
    foreach ($ip_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ip = $_SERVER[$header];
            
            // Si plusieurs IP (proxy chain), prendre la première
            if (strpos($ip, ',') !== false) {
                $ip = trim(explode(',', $ip)[0]);
            }
            
            // Valider que c'est une IP valide
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
    }
    
    // Fallback : REMOTE_ADDR même si c'est une IP privée (réseaux locaux)
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            return $ip;
        }
    }
    
    return 'unknown';
}

// Hook pour collecter les IP lors des visites front-end
add_action('wp', 'acg_collect_visitor_ip');

// === AJAX ET FONCTIONS ADMIN EXISTANTES ===

// AJAX pour basculer l'état des commentaires automatiques
function acg_toggle_auto_comment() {
    check_ajax_referer('acg_nonce', 'nonce');

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(['message' => 'Permissions insuffisantes']);
    }

    $post_id = intval($_POST['post_id']);
    $current_state = get_post_meta($post_id, '_acg_auto_comment_enabled', true);
    $new_state = $current_state ? '0' : '1';
    
    update_post_meta($post_id, '_acg_auto_comment_enabled', $new_state);
    
    wp_send_json_success(['new_state' => $new_state]);
}
add_action('wp_ajax_acg_toggle_auto_comment', 'acg_toggle_auto_comment');

// Enqueue des scripts admin
function acg_enqueue_admin_scripts($hook) {
    if ($hook == 'edit.php') {
        wp_enqueue_script('jquery');
        wp_enqueue_script('acg-admin-script', plugin_dir_url(__FILE__) . 'admin-script.js', array('jquery'), '1.0.0', true);
        wp_localize_script('acg-admin-script', 'acg_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('acg_nonce')
        ));
    }
}
add_action('admin_enqueue_scripts', 'acg_enqueue_admin_scripts');

// Créer le fichier JavaScript si inexistant
function acg_create_admin_js() {
    $js_file = plugin_dir_path(__FILE__) . 'admin-script.js';
    if (!file_exists($js_file)) {
        $js_content = "
jQuery(document).ready(function($) {
    $('.acg-auto-comment-toggle').change(function() {
        var post_id = $(this).data('post-id');
        var checkbox = this;
        
        $.ajax({
            url: acg_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'acg_toggle_auto_comment',
                post_id: post_id,
                nonce: acg_ajax.nonce
            },
            success: function(response) {
                if (!response.success) {
                    // Révertir l'état en cas d'erreur
                    checkbox.checked = !checkbox.checked;
                    alert('Erreur: ' + (response.data.message || 'Impossible de modifier l\'état'));
                }
            },
            error: function() {
                // Révertir l'état en cas d'erreur
                checkbox.checked = !checkbox.checked;
                alert('Erreur de communication avec le serveur');
            }
        });
    });
});
";
        file_put_contents($js_file, $js_content);
    }
}
add_action('admin_init', 'acg_create_admin_js');