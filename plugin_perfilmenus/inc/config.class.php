<?php
/**
 * Configuration class for plugin_perfilmenus
 */

class PluginPerfilmenusConfig
{
    /**
     * Return translated type name
     */
    public static function getTypeName($nb = 0)
    {
        return _n('Menu de perfil', 'Menus de perfil', $nb, 'perfilmenus');
    }

    /**
     * Retrieve available features that can be toggled per profile.
     *
     * @return array
     */
    public static function getAvailableFeatures()
    {
        return [
            'tickets'     => _n('Ticket', 'Tickets', 2),
            'ticket_new'  => __('New ticket'),
        ];
    }

    /**
     * Default configuration for a profile.
     *
     * @return array
     */
    protected static function getDefaultConfig()
    {
        return [
            'tickets'    => 1,
            'ticket_new' => 1,
        ];
    }

    /**
     * Retrieve stored configuration for a profile.
     *
     * @param int $profiles_id
     *
     * @return array
     */
    public static function getProfileConfig($profiles_id)
    {
        global $DB;

        $config = self::getDefaultConfig();

        if ((int)$profiles_id <= 0 || !$DB->tableExists('glpi_plugin_perfilmenus_profiles')) {
            return $config;
        }

        $iterator = $DB->request([
            'SELECT' => ['tickets_visible', 'ticketcreate_visible'],
            'FROM'   => 'glpi_plugin_perfilmenus_profiles',
            'WHERE'  => ['profiles_id' => (int)$profiles_id],
            'LIMIT'  => 1,
        ]);

        foreach ($iterator as $row) {
            $config['tickets']    = (int)$row['tickets_visible'];
            $config['ticket_new'] = (int)$row['ticketcreate_visible'];
        }

        return $config;
    }

    /**
     * Persist profile configuration
     *
     * @param int   $profiles_id
     * @param array $visibility
     */
    protected static function saveProfileConfig($profiles_id, array $visibility)
    {
        global $DB;

        $values = [
            'tickets_visible'       => (int)($visibility['tickets'] ?? 0),
            'ticketcreate_visible'  => (int)($visibility['ticket_new'] ?? 0),
        ];

        $iterator = $DB->request([
            'SELECT' => ['id'],
            'FROM'   => 'glpi_plugin_perfilmenus_profiles',
            'WHERE'  => ['profiles_id' => (int)$profiles_id],
            'LIMIT'  => 1,
        ]);

        $exists = false;
        foreach ($iterator as $row) {
            $exists = true;
            break;
        }

        if ($exists) {
            $DB->update(
                'glpi_plugin_perfilmenus_profiles',
                $values,
                ['profiles_id' => (int)$profiles_id]
            );
        } else {
            $values['profiles_id'] = (int)$profiles_id;
            $DB->insert('glpi_plugin_perfilmenus_profiles', $values);
        }
    }

    /**
     * Handle form submission
     */
    public static function saveFromForm(array $values)
    {
        Session::checkRight('config', UPDATE);

        $features = self::getAvailableFeatures();
        $entries  = $values['visibility'] ?? [];

        foreach ($entries as $profileId => $featureValues) {
            $profileVisibility = [];
            foreach ($features as $featureKey => $label) {
                $profileVisibility[$featureKey] = !empty($featureValues[$featureKey]) ? 1 : 0;
            }
            self::saveProfileConfig((int)$profileId, $profileVisibility);
        }

        Session::addMessageAfterRedirect(__('Configuration saved', 'perfilmenus'));
    }

    /**
     * Display configuration form
     */
    public static function showConfigForm()
    {
        Session::checkRight('config', READ);

        $target    = Plugin::getWebDir('perfilmenus') . '/front/config.form.php';
        $profiles  = self::getAllProfiles();
        $features  = self::getAvailableFeatures();

        echo "<form method='post' action='" . Html::clean($target) . "' id='plugin_perfilmenus_config' name='plugin_perfilmenus_config'>";
        echo Html::hidden('_glpi_csrf_token', Session::getNewCSRFToken());

        echo "<table class='tab_cadre_fixehov'>";
        echo '<tr>';
        echo '<th>' . __('Profile') . '</th>';
        foreach ($features as $label) {
            echo '<th>' . $label . '</th>';
        }
        echo '</tr>';

        foreach ($profiles as $profile) {
            $config = self::getProfileConfig((int)$profile['id']);
            echo '<tr>';
            echo '<td>' . Html::clean($profile['name']) . '</td>';
            foreach ($features as $featureKey => $label) {
                $checked = $config[$featureKey] ? 'checked' : '';
                echo "<td class='center'>";
                echo sprintf(
                    "<input type='checkbox' name='visibility[%d][%s]' value='1' %s>",
                    (int)$profile['id'],
                    Html::entities_deep($featureKey),
                    $checked
                );
                echo '</td>';
            }
            echo '</tr>';
        }

        echo '</table>';

        if (Session::haveRight('config', UPDATE)) {
            echo "<div class='center'>";
            echo Html::submit(_sx('button', 'Save'), ['name' => 'save', 'class' => 'btn btn-primary']);
            echo '</div>';
        }

        echo '</form>';
    }

    /**
     * Retrieve available GLPI profiles
     *
     * @return array
     */
    protected static function getAllProfiles()
    {
        global $DB;

        $profiles = [];

        $iterator = $DB->request([
            'SELECT'  => ['id', 'name'],
            'FROM'    => 'glpi_profiles',
            'ORDERBY' => 'name',
        ]);

        foreach ($iterator as $row) {
            $profiles[] = $row;
        }

        return $profiles;
    }

    /**
     * Apply menu configuration to GLPI menus
     *
     * @param array $menus
     *
     * @return array
     */
    public static function redefineMenus(array $menus)
    {
        $profiles_id = $_SESSION['glpiactiveprofile']['id'] ?? 0;
        $config      = self::getProfileConfig((int)$profiles_id);

        $menus = self::removeManagedEntries($menus);

        if (!empty($config['tickets'])) {
            $menus['plugin_perfilmenus_tickets'] = self::buildMenuEntry('tickets');
        }

        if (!empty($config['ticket_new'])) {
            $menus['plugin_perfilmenus_ticketnew'] = self::buildMenuEntry('ticket_new');
        }

        return $menus;
    }

    /**
     * Remove default ticket entries to avoid duplicates
     */
    protected static function removeManagedEntries(array $menus)
    {
        foreach ($menus as $key => &$entry) {
            if (self::isFeatureEntry($entry, 'tickets')) {
                unset($menus[$key]);
                continue;
            }

            if (self::isFeatureEntry($entry, 'ticket_new')) {
                unset($menus[$key]);
                continue;
            }

            if (isset($entry['children']) && is_array($entry['children'])) {
                $entry['children'] = self::removeManagedEntries($entry['children']);
            }
        }

        return $menus;
    }

    /**
     * Determine if a menu entry matches one of our features
     */
    protected static function isFeatureEntry(array $entry, $feature)
    {
        if (!isset($entry['url'])) {
            return false;
        }

        $url = $entry['url'];

        if ($feature === 'tickets' && strpos($url, 'ticket.php') !== false) {
            return true;
        }

        if ($feature === 'ticket_new' && strpos($url, 'ticket.form.php') !== false) {
            return true;
        }

        if (isset($entry['itemtype']) && $entry['itemtype'] === 'Ticket') {
            if ($feature === 'tickets' && empty($entry['is_create'])) {
                return true;
            }
            if ($feature === 'ticket_new' && !empty($entry['is_create'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build menu entry definition for a feature
     */
    protected static function buildMenuEntry($feature)
    {
        $entry = [
            'parent' => 'root',
            'id'     => 'plugin_perfilmenus_' . $feature,
        ];

        switch ($feature) {
            case 'tickets':
                $entry['title'] = _n('Ticket', 'Tickets', 2);
                $entry['url']   = Ticket::getSearchURL(false);
                $entry['icon']  = 'ti ti-ticket';
                break;

            case 'ticket_new':
                $entry['title'] = __('New ticket');
                $entry['url']   = Ticket::getFormURL(false);
                $entry['icon']  = 'ti ti-plus';
                break;
        }

        return $entry;
    }
}
