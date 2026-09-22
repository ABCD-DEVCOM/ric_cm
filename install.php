<?php
if (!class_exists('PluginBridge')) {
    header("HTTP/1.1 403 Forbidden");
    die("Direct access forbidden.");
}

$bridge = PluginBridge::getInstance();
$abcdPath = $bridge->get('abcd_path', realpath(__DIR__ . '/../../../central'));
$dbPath   = $bridge->get('db_path', realpath(__DIR__ . '/../../../bases'));

global $msgstr;

$config_path = realpath($abcdPath . '/config.php');
if (file_exists($config_path)) {
    require_once $config_path;
} else {
    $err_cfg = $msgstr["ric_config_error"] ?? "Fatal Error: config.php not found at resolved path: ";
    die("<div style='color:red; padding:20px;'>{$err_cfg} {$abcdPath}</div>");
}

global $mx_path;
$mx_exec_path = $mx_path;

if (stristr(PHP_OS, 'WIN') && preg_match('/^[\/\\\\]/', $mx_exec_path)) {
    $drive = substr(__DIR__, 0, 2);
    $mx_exec_path = $drive . $mx_exec_path;
}

$mx_realpath = realpath($mx_exec_path);
if (!$mx_realpath) {
    $err_mx = $msgstr["ric_mx_not_found"] ?? "Fatal Error: MX executable not found.";
    $err_path = $msgstr["ric_attempted_path"] ?? "Attempted path:";
    $err_chk = $msgstr["ric_check_cisis"] ?? "Please check CISIS configurations in config.php.";
    die("<div style='color:red; padding:20px; font-family:sans-serif;'>
            <b>{$err_mx}</b><br>{$err_path} <code>{$mx_exec_path}</code><br>{$err_chk}
         </div>");
}

$mx = '"' . $mx_realpath . '"';

$installDir = realpath(__DIR__ . '/install');
$basesToCopy = ['ric_cm', 'ric_msg'];
$errors = [];
$debug_log = [];

function smart_copy($src, $dst)
{
    $dir = opendir($src);
    @mkdir($dst, 0755, true);
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            $srcFile = $src . DIRECTORY_SEPARATOR . $file;
            $dstFile = $dst . DIRECTORY_SEPARATOR . $file;

            if (is_dir($srcFile)) {
                smart_copy($srcFile, $dstFile);
            } else {
                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                $skipExts = ['mst', 'xrf', 'cnt', 'n01', 'n02', 'l01', 'l02', 'ifp'];

                if (!in_array($ext, $skipExts)) {
                    copy($srcFile, $dstFile);
                }
            }
        }
    }
    closedir($dir);
}

$dbPath_norm = rtrim($dbPath, '/\\');
foreach ($basesToCopy as $base) {
    $sourceBase = $installDir . DIRECTORY_SEPARATOR . $base;
    $targetBase = $dbPath_norm . DIRECTORY_SEPARATOR . $base;
    if (is_dir($sourceBase)) {
        smart_copy($sourceBase, $targetBase);
    }
}

$ric_cm_data = $dbPath_norm . DIRECTORY_SEPARATOR . 'ric_cm' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
file_put_contents($ric_cm_data . 'control_number.cn', '0');

exec($mx . " seq=nul create=\"" . $ric_cm_data . "ric_cm\" -all now 2>&1", $out, $ret);
$debug_log[] = "MX ric_cm create: " . implode(" ", $out);

if (file_exists($ric_cm_data . 'ric_cm.fst')) {
    exec($mx . " \"" . $ric_cm_data . "ric_cm\" fst=@\"" . $ric_cm_data . "ric_cm.fst\" fullinv=\"" . $ric_cm_data . "ric_cm\" -all now 2>&1", $out2);
    $debug_log[] = "MX ric_cm fullinv: " . implode(" ", $out2);
} else {
    $errors[] = $msgstr["ric_error_fst_cm"] ?? "Error: ric_cm.fst not found in data folder.";
}

$ric_msg_data = $dbPath_norm . DIRECTORY_SEPARATOR . 'ric_msg' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR;
$isoFile = $installDir . DIRECTORY_SEPARATOR . 'ric_msg' . DIRECTORY_SEPARATOR . 'ric_msg.iso';

if (file_exists($isoFile)) {
    $isoContent = file_get_contents($isoFile);
    $isoContent = str_replace("\r\n", "\n", $isoContent);
    if (stristr(PHP_OS, 'WIN')) {
        $isoContent = str_replace("\n", "\r\n", $isoContent);
    }
    file_put_contents($isoFile, $isoContent);

    exec($mx . " iso=\"" . $isoFile . "\" create=\"" . $ric_msg_data . "ric_msg\" -all now 2>&1", $out3);
    $debug_log[] = "MX ric_msg import: " . implode(" ", $out3);

    if (file_exists($ric_msg_data . 'ric_msg.fst')) {
        exec($mx . " \"" . $ric_msg_data . "ric_msg\" fst=@\"" . $ric_msg_data . "ric_msg.fst\" fullinv=\"" . $ric_msg_data . "ric_msg\" -all now 2>&1", $out4);
        $debug_log[] = "MX ric_msg fullinv: " . implode(" ", $out4);
    }

    $output = [];
    exec($mx . " \"" . $ric_msg_data . "ric_msg\" \"pft=mfn/\" now 2>&1", $output);
    $valid_mfns = array_filter(array_map('intval', $output));
    $maxMfn = !empty($valid_mfns) ? max($valid_mfns) : 0;
    file_put_contents($ric_msg_data . 'control_number.cn', $maxMfn);
} else {
    $errors[] = $msgstr["ric_warn_iso_msg"] ?? "Warning: ric_msg.iso not found. Vocabulary not imported.";
    file_put_contents($ric_msg_data . 'control_number.cn', '0');
    exec($mx . " seq=nul create=\"" . $ric_msg_data . "ric_msg\" -all now");
}

$sourceParDir = $installDir . DIRECTORY_SEPARATOR . 'par';
$targetParDir = $dbPath_norm . DIRECTORY_SEPARATOR . 'par';

if (is_dir($sourceParDir)) {
    @mkdir($targetParDir, 0755, true);
    $parFiles = glob($sourceParDir . DIRECTORY_SEPARATOR . '*.par');
    foreach ($parFiles as $parFile) {
        $fileName = basename($parFile);
        copy($parFile, $targetParDir . DIRECTORY_SEPARATOR . $fileName);
    }
}

$basesDatPath = $dbPath_norm . DIRECTORY_SEPARATOR . 'bases.dat';
$basesDatContent = file_get_contents($basesDatPath);
if (strpos($basesDatContent, 'ric_cm') === false) {
    $newEntry = "\nric_cm|Records in Contexts (RiC-CM)\nric_msg|RiC Vocabularies\n";
    file_put_contents($basesDatPath, $newEntry, FILE_APPEND | LOCK_EX);
}

if (empty($errors)) {
    $msg_succ = $msgstr["ric_success_install"] ?? "Success! Databases compiled natively, indexes generated and RiC-CM installed.";
    $msg_log = $msgstr["ric_view_mx_log"] ?? "View MX Compilation Log";

    echo "<div style='color:green; padding:15px; font-family:sans-serif;'>
            <strong><i class='fa-solid fa-check-circle'></i> {$msg_succ}</strong>
          </div>";

    echo "<details style='margin:15px; font-family:monospace; background:#f1f5f9; padding:10px; border-radius:4px;'>
            <summary style='cursor:pointer; color:#0284c7;'><strong>{$msg_log}</strong></summary>
            <div style='margin-top:10px; font-size:12px; color:#475569;'>
                " . implode("<br>", $debug_log) . "
            </div>
          </details>";
} else {
    $msg_warn = $msgstr["ric_install_warnings"] ?? "Installation completed with warnings:";
    echo "<div style='color:orange; padding:15px; font-family:sans-serif;'>{$msg_warn}<br>" . implode("<br>", $errors) . "</div>";
}
