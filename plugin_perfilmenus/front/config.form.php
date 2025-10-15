<?php
/** @var GlpiPlugin $PLUGIN */

require_once '../../../inc/includes.php';
require_once __DIR__ . '/../inc/config.class.php';

Session::checkRight('config', READ);

if (isset($_POST['save'])) {
    PluginPerfilmenusConfig::saveFromForm($_POST);
    Html::back();
}

Html::header(
    PluginPerfilmenusConfig::getTypeName(1),
    '',
    'config',
    'plugins'
);

PluginPerfilmenusConfig::showConfigForm();

Html::footer();
