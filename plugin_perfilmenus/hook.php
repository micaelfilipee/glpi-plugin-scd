<?php
/**
 * Hooks file for plugin_perfilmenus
 */

/**
 * Redefine GLPI menus according to profile configuration
 *
 * @param array $menus
 *
 * @return array
 */
function plugin_perfilmenus_redefine_menus(array $menus) {
    require_once __DIR__ . '/inc/config.class.php';

    return PluginPerfilmenusConfig::redefineMenus($menus);
}
