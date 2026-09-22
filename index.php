<?php
/*
 * Script: Roteador e Assistente do Plugin RiC-CM
 * Architecture: ABCD v4 Plugin Ecosystem
 */
session_start();
$central_path = "../../../central/";

if (!isset($_SESSION["permiso"])) {
    header("Location: {$central_path}common/error_page.php");
    die;
}

require_once("{$central_path}config_inc_check.php");
require_once("{$central_path}config.php");
include("{$central_path}common/get_post.php");

if (!class_exists('PluginBridge')) {
    die("<div style='color:red; padding:20px;'>Critical Error: PluginBridge class not found in ABCD core. Please update your system.</div>");
}

$bridge = PluginBridge::getInstance();
$dbPath = $bridge->get('db_path');
$abcdPath = $bridge->get('abcd_path', realpath(__DIR__ . '/../../../central'));
$pluginPath = realpath(__DIR__);
$lang = $bridge->get('lang', 'en');

global $msgstr, $langManager;
if (!isset($langManager)) {
    require_once $abcdPath . '/common/LanguageManager.php';
    $langManager = new \ABCD\Common\LanguageManager($abcdPath, $abcdPath . '/../content');
}

$plugin_name = basename(__DIR__);
$current_lang = $_SESSION['lang'] ?? $lang ?? 'en';
$plugin_msgs = $langManager->loadPluginTranslations($pluginPath, $plugin_name, 'ric.tab', $current_lang);
$msgstr = array_merge($msgstr ?? [], $plugin_msgs);

$mst_file = rtrim($dbPath, '/\\') . DIRECTORY_SEPARATOR . 'ric_msg' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'ric_msg.mst';
$action = $_REQUEST['action'] ?? 'browse';

if (!file_exists($mst_file) && $action !== 'install') {
    $action = 'wizard';
}

switch ($action) {
    case 'wizard':
        include($abcdPath . "/common/header.php");
?>
        <div style="max-width: 650px; margin: 50px auto; background: #fff; padding: 40px; border-radius: 8px; box-shadow: 0 10px 25px rgba(15,23,42,0.1); font-family: 'Segoe UI', sans-serif;">
            <div style="text-align: center; color: #0f172a;">
                <i class="fa-solid fa-sitemap" style="font-size: 3.5em; color: #0284c7; margin-bottom: 20px;"></i>
                <h2 style="margin-bottom: 10px;"><?php echo $msgstr["ric_wizard_title"] ?? "Welcome to Records in Contexts (RiC-CM)"; ?></h2>
                <p style="color: #64748b; font-size: 1.1em; margin-bottom: 30px; line-height: 1.6;">
                    <?php echo $msgstr["ric_wizard_desc"] ?? "We detected that the archival databases (ric_cm and ric_msg) have not been initialized on this server yet."; ?>
                </p>
            </div>
            <div style="background: #f8fafc; padding: 20px; border-left: 4px solid #f59e0b; margin-bottom: 30px; border-radius: 4px;">
                <strong style="color: #92400e;"><i class="fa-solid fa-triangle-exclamation"></i> <?php echo $msgstr["ric_wizard_warn"] ?? "Initialization Required:"; ?></strong>
                <p style="margin-top: 8px; color: #334155; font-size: 0.95em;">
                    <?php echo $msgstr["ric_wizard_warn_desc"] ?? "The wizard will natively compile the master files (MST/XRF) adjusting line breaks and preparing the structures for your server architecture. This prevents data corruption."; ?>
                </p>
            </div>
            <div style="text-align: center;">
                <a href="?action=install" style="display: inline-flex; align-items: center; gap: 8px; background: #10b981; color: white; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-size: 1.1em; font-weight: bold; transition: background 0.3s;">
                    <i class="fa-solid fa-rocket"></i> <?php echo $msgstr["ric_wizard_btn"] ?? "Install and Initialize Databases"; ?>
                </a>
            </div>
        </div>
<?php
        include($abcdPath . "/common/footer.php");
        break;

    case 'install':
        include($abcdPath . "/common/header.php");
        echo "<div style='max-width: 800px; margin: 30px auto; background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1);'>";
        echo "<h3><i class='fa-solid fa-microchip'></i> " . ($msgstr["ric_installing"] ?? "Processing Installation...") . "</h3>";

        require_once __DIR__ . '/install.php';

        echo "<div style='margin-top: 20px; text-align: center;'>
                <a href='?action=browse' style='padding: 10px 20px; background: #0284c7; color: #fff; text-decoration: none; border-radius: 4px; font-weight: bold;'>" . ($msgstr["ric_access_catalog"] ?? "Access RiC-CM Catalog") . "</a>
              </div>";
        echo "</div>";
        include($abcdPath . "/common/footer.php");
        break;

    case 'read_mfn':
        require_once __DIR__ . '/actions/read_mfn.php';
        break;

    case 'browse':
    default:
        require_once __DIR__ . '/browse.php';
        break;
}
?>