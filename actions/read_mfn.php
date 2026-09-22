<?php
// Garante que o arquivo está sendo executado por dentro do ecossistema do ABCD
if (!class_exists('PluginBridge')) {
    header("HTTP/1.1 403 Forbidden");
    die("Acesso direto proibido.");
}

global $xWxis, $Wxis, $db_path, $msgstr;

$mfn = isset($_REQUEST['mfn']) ? intval($_REQUEST['mfn']) : 0;
if ($mfn <= 0) {
    die("<div style='padding:20px; font-family:sans-serif; color:red;'>Erro: MFN inválido ou não informado.</div>");
}

$base = 'ric_cm';

// Converte os caminhos lógicos do Apache/PHP para caminhos absolutos do SO
function wxis_path($path)
{
    $real = realpath($path);
    if ($real) return $real;

    // Fallback para Windows caso realpath falhe por causa da barra inicial
    if (stristr(PHP_OS, 'WIN') && preg_match('/^[\/\\\\]/', $path)) {
        $drive = substr(__DIR__, 0, 2);
        if (file_exists($drive . $path)) {
            return $drive . $path;
        }
    }
    return $path;
}

$cipar = wxis_path($dbPath . "par/ric_cm.par");

// Para o imprime.xis, é OBRIGATÓRIO o uso da extensão .pft e do prefixo '@'
$formatoPath = $dbPath . "ric_cm/pfts/" . $lang . "/ric_cm.pft";
if (!file_exists(wxis_path($formatoPath))) {
    $formatoPath = $dbPath . "ric_cm/pfts/en/ric_cm.pft"; // Fallback de segurança
}
$formato = "@" . wxis_path($formatoPath);

// Usamos o script padrão nativo universal do ABCD
$IsisScript = wxis_path($xWxis . "imprime.xis");

// Montagem dos parâmetros exigidos pelo imprime.xis (Opcion=rango, from, to)
$query  = "&base=" . $base;
$query .= "&cipar=" . $cipar;
$query .= "&from=" . $mfn;
$query .= "&to=" . $mfn;
$query .= "&Formato=" . $formato;
$query .= "&Opcion=rango";
$query .= "&path_db=" . wxis_path($dbPath);

// Dispara a leitura via CGI
$_GET['IsisScript'] = $IsisScript;
require $abcdPath . '/common/wxis_llamar.php';

// Renderização e processamento do output do imprime.xis
if (isset($contenido) && is_array($contenido)) {
    $html_output = "";

    foreach ($contenido as $value) {
        $value = trim($value);
        if ($value != "") {
            // Remove as marcações de controle injetadas pelo imprime.xis
            if (substr($value, 0, 9) == "[RECORD:]") {
                continue;
            } elseif (substr($value, 0, 8) == "[TOTAL:]") {
                continue;
            } else {
                $html_output .= $value . "\n";
            }
        }
    }

    if (isset($unicode) && $unicode == '0') {
        $html_output = mb_convert_encoding($html_output, 'UTF-8', 'ISO-8859-1');
    }

    if (strpos($html_output, 'WXIS|file error') !== false) {
        echo "<div style='padding:20px; font-family:monospace; color:red;'><strong>Erro do WXIS detectado:</strong><br>{$html_output}</div>";
    } else {
        echo $html_output;
    }
} else {
    echo "<div style='padding:20px; font-family:sans-serif;'>Erro interno ao processar o registro. WXIS não retornou dados.</div>";
}
