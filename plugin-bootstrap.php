<?php

declare(strict_types=1);

if (!class_exists('PluginBridge')) {
    return;
}

$bridge = PluginBridge::getInstance();

abcd_add_hook('abcd_topbar_modules', function (array $modules) use ($bridge): array {
    global $msgstr, $langManager;

    $userLang = $_SESSION["lang"] ?? $_REQUEST["lang"] ?? 'en';

    if (isset($langManager)) {
        $plugin_lang = $langManager->loadPluginTranslations(__DIR__, 'ric_cm', 'ric.tab', $userLang);
        if (is_array($msgstr) && is_array($plugin_lang)) {
            $msgstr = array_merge($msgstr, $plugin_lang);
        }
    }

    $titulo = $msgstr["ric_module_title"] ?? 'Arquivos (RiC-CM)';

    // Verifica permissões (Ajustado para administradores ou permissão global)
    $hasPermission = false;
    if (isset($_SESSION["permiso"])) {
        if (isset($_SESSION["permiso"]["CENTRAL_ALL"]) || isset($_SESSION["permiso"]["RIC_ALL"]) || ($_SESSION["profile"] ?? '') === 'adm') {
            $hasPermission = true;
        }
    }

    // Injeta o RiC-CM no array de módulos da barra superior
    $modules['ric_cm'] = [
        'title'  => $titulo,
        'icon'   => 'fa-solid fa-boxes-packing',
        'action' => '/content/plugins/ric_cm/index.php',
        'params' => [
            'action' => 'browse',
            'lang'   => $userLang
        ],
        'perm'   => $hasPermission,
        'active' => (isset($_SESSION["MODULO"]) && $_SESSION["MODULO"] === "ric_cm")
    ];

    return $modules;
});
