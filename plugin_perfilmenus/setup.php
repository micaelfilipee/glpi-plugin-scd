<?php
/**
 * Setup file for plugin_perfilmenus
 */

/**
 * Initialize plugin hooks
 */
function plugin_init_perfilmenus() {
    global $PLUGIN_HOOKS;

    $PLUGIN_HOOKS['csrf_compliant']['perfilmenus'] = true;
    $PLUGIN_HOOKS['config_page']['perfilmenus'] = 'front/config.form.php';
    $PLUGIN_HOOKS['redefine_menus']['perfilmenus'] = 'plugin_perfilmenus_redefine_menus';
}

/**
 * Plugin information
 */
function plugin_version_perfilmenus() {
    return [
        'name'           => __('Menu por perfil', 'perfilmenus'),
        'version'        => '1.0.0',
        'author'         => 'plugin_perfilmenus',
        'homepage'       => 'https://glpi-developer-documentation.readthedocs.io/',
        'license'        => 'GPLv2+',
        'requirements'   => [
            'glpi' => [
                'min' => '9.5.5',
                'max' => '9.5.x',
            ],
        ],
    ];
}

/**
 * Install routine
 */
function plugin_perfilmenus_install() {
    global $DB;

    $table = 'glpi_plugin_perfilmenus_profiles';

    if (!$DB->tableExists($table)) {
        $query = "CREATE TABLE `$table` (
            `id` int unsigned NOT NULL AUTO_INCREMENT,
            `profiles_id` int unsigned NOT NULL,
            `tickets_visible` tinyint(1) NOT NULL DEFAULT 1,
            `ticketcreate_visible` tinyint(1) NOT NULL DEFAULT 1,
            `date_mod` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `unicity` (`profiles_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

        $DB->queryOrDie($query, $table);
    }

    return true;
}

/**
 * Uninstall routine
 */
function plugin_perfilmenus_uninstall() {
    global $DB;

    $table = 'glpi_plugin_perfilmenus_profiles';

    if ($DB->tableExists($table)) {
        $DB->queryOrDie("DROP TABLE `$table`", $table);
    }

    return true;
}
