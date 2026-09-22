<?php
if (!class_exists('PluginBridge')) {
    header("HTTP/1.1 403 Forbidden");
    die("Direct access forbidden.");
}

$bridge = PluginBridge::getInstance();
$abcdPath = $bridge->get('abcd_path', realpath(__DIR__ . '/../../../central'));

require_once $abcdPath . '/config.php';
include $abcdPath . '/common/get_post.php';

global $db_path, $xWxis, $Wxis, $msgstr, $langManager;
$ABCD_lang = $_SESSION["lang"] ?? 'en';

if (isset($langManager)) {
    $plugin_lang = $langManager->loadPluginTranslations(__DIR__, 'ric_cm', 'ric.tab', $ABCD_lang);
    if (is_array($msgstr) && is_array($plugin_lang)) {
        $msgstr = array_merge($msgstr, $plugin_lang);
    }
}

$base = 'ric_cm';
$cipar = $db_path . "par/" . $base . ".par";

$pftDir = $db_path . $base . "/pfts/" . $ABCD_lang;
@mkdir($pftDir, 0755, true);
$pftFile = $pftDir . "/list.pft";

if (!file_exists($pftFile)) {
    file_put_contents($pftFile, "mfn,'|',v1,'|',v128/\n");
}

// --- CONFIGURAÇÃO DE BUSCA E PAGINAÇÃO ---
$expresion = trim($_REQUEST['Expresion'] ?? '');
$page      = max(1, (int)($_REQUEST['page'] ?? 1));
$limit     = 10; // Registros por página
$from      = ($page - 1) * $limit + 1;
$to        = $from + $limit - 1;

$query  = "&base=" . $base;
$query .= "&cipar=" . $cipar;
$query .= "&path_db=" . $db_path;
$query .= "&Formato=@" . $pftFile;
$query .= "&from=" . $from . "&to=" . $to;

if ($expresion !== '') {
    // Modo de Pesquisa
    $query .= "&Opcion=buscar";
    $query .= "&Expresion=" . urlencode($expresion);
} else {
    // Modo de Listagem Completa
    $query .= "&Opcion=rango";
}

$IsisScript = $xWxis . "imprime.xis";
$_GET['IsisScript'] = $IsisScript;

include $abcdPath . '/common/wxis_llamar.php';

$registros = [];
$total_records = 0;

if (isset($contenido) && is_array($contenido)) {
    foreach ($contenido as $line) {
        $line = trim($line);

        // Captura o total de registros retornados pela pesquisa ou base
        if (strpos($line, '[TOTAL:]') !== false) {
            $total_records = (int)str_replace('[TOTAL:]', '', $line);
            continue;
        }

        if ($line == "" || strpos($line, '[RECORD:]') !== false) {
            continue;
        }

        $parts = explode('|', $line);
        if (count($parts) >= 2) {
            $mfn = (int)$parts[0];
            if ($mfn > 0) {
                $registros[] = [
                    'mfn'  => $mfn,
                    'type' => htmlspecialchars(trim($parts[1] ?? '')),
                    'name' => htmlspecialchars(trim($parts[2] ?? ''))
                ];
            }
        }
    }
}

// Fallback do Total para listagem s/ pesquisa
if ($expresion === '' && $total_records === 0) {
    $cn_file = $db_path . $base . "/data/control_number.cn";
    $total_records = file_exists($cn_file) ? (int)file_get_contents($cn_file) : 0;
}

$total_pages = $total_records > 0 ? ceil($total_records / $limit) : 1;

// --- LEITURA DO TYPEOFRECORD.TAB ---
$typeofrecords = [];
$tor_path = $db_path . $base . "/def/" . $ABCD_lang . "/typeofrecord.tab";

if (!file_exists($tor_path)) {
    $tor_path = $db_path . $base . "/def/en/typeofrecord.tab";
}

if (file_exists($tor_path)) {
    $lines = file($tor_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $parts = explode('|', $line);
        if (count($parts) >= 4) {
            $label = trim($parts[3]);
            $worksheet = trim($parts[1]);
            if (!empty($worksheet)) {
                $typeofrecords[$worksheet] = $label;
            }
        }
    }
}
// --------------------------------------------------------------

include $abcdPath . '/common/header.php';
include $abcdPath . '/common/institutional_info.php';
?>

<div class="sectionInfo">
    <div class="breadcrumb"><?php echo $msgstr['ric_module_title'] ?? 'Records in Contexts (RiC-CM)'; ?></div>
    <div class="actions"></div>
    <div class="spacer">&#160;</div>
</div>

<style>
    .ric-wrapper {
        padding: 20px;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    .ric-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        border-bottom: 2px solid #cbd5e1;
        padding-bottom: 15px;
        flex-wrap: wrap;
        gap: 15px;
    }

    .ric-header h2 {
        color: #0f172a;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 1.4rem;
    }

    .ric-controls {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }

    .ric-search-form {
        display: flex;
        gap: 5px;
    }

    .ric-input {
        padding: 8px 12px;
        border: 1px solid var(--ric-border);
        border-radius: 4px;
        font-size: 13.5px;
        outline: none;
        width: 250px;
    }

    .ric-input:focus {
        border-color: var(--cyan-accent);
    }

    .ric-btn-secondary {
        background: #e2e8f0;
        color: #334155;
        border: none;
        padding: 8px 14px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
        transition: background 0.2s;
    }

    .ric-btn-secondary:hover {
        background: #cbd5e1;
    }

    .ric-btn-primary {
        background: #0284c7;
        color: #fff;
        border: none;
        padding: 8px 16px;
        border-radius: 4px;
        cursor: pointer;
        font-weight: bold;
    }

    .ric-btn-primary:hover {
        background: #0369a1;
    }

    .ric-btn-view {
        background: #10b981;
        color: #fff;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
        margin-right: 5px;
    }

    .ric-btn-edit {
        background: #f59e0b;
        color: #fff;
        border: none;
        padding: 5px 10px;
        border-radius: 4px;
        cursor: pointer;
    }

    .ric-select {
        padding: 8px 12px;
        border: 1px solid var(--ric-border);
        border-radius: 4px;
        background-color: #fff;
        color: #334155;
        font-family: inherit;
        font-size: 13.5px;
        outline: none;
        font-weight: 500;
        cursor: pointer;
    }

    .ric-select:focus {
        border-color: #0284c7;
        box-shadow: 0 0 0 2px #e0f2fe;
    }

    .ric-pagination {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 15px;
        margin-top: 25px;
        padding-top: 15px;
        border-top: 1px solid #cbd5e1;
    }

    .ric-page-btn {
        background: #0284c7;
        color: #fff;
        padding: 6px 12px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: bold;
        font-size: 13px;
    }

    .ric-page-btn:hover {
        background: #0369a1;
        color: #fff;
    }

    .ric-page-info {
        font-size: 13.5px;
        color: #475569;
        font-weight: 500;
    }

    /* Modal CSS ocultado por brevidade, idêntico ao original */
    .ric-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(15, 23, 42, 0.75);
        z-index: 9999;
        backdrop-filter: blur(3px);
    }

    .ric-modal-box {
        position: absolute;
        top: 5%;
        left: 10%;
        width: 80%;
        height: 90%;
        background: #f1f5f9;
        border-radius: 8px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        display: flex;
        flex-direction: column;
        overflow: hidden;
    }

    .ric-modal-header {
        padding: 12px 20px;
        background: #1e293b;
        color: #fff;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .ric-modal-close {
        background: none;
        border: none;
        color: #fff;
        font-size: 24px;
        cursor: pointer;
        opacity: 0.8;
    }

    #ric-iframe {
        width: 100%;
        height: 100%;
        border: none;
        background: #fff;
    }
</style>

<div class="ric-wrapper">
    <div class="ric-header">
        <h2><i class="fa-solid fa-boxes-packing"></i> <?php echo $msgstr["ric_module_title"] ?? 'Catálogo Arquivístico'; ?></h2>

        <div class="ric-controls">
            <!-- Formulário de Busca -->
            <form method="GET" action="/content/plugins/ric_cm/index.php" class="ric-search-form">
                <input type="hidden" name="action" value="browse">
                <input type="text" name="Expresion" class="ric-input" placeholder="<?php echo $msgstr["ric_search_ph"] ?? 'Buscar expressões ou termos...'; ?>" value="<?php echo htmlspecialchars($expresion); ?>">
                <button type="submit" class="ric-btn-secondary" title="<?php echo $msgstr["ric_search"] ?? 'Pesquisar'; ?>"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if ($expresion !== ''): ?>
                    <a href="/content/plugins/ric_cm/index.php?action=browse" class="ric-btn-secondary" title="<?php echo $msgstr["ric_clear"] ?? 'Limpar Busca'; ?>"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </form>

            <!-- Menu Nova Entidade -->
            <div style="display: flex; gap: 8px; align-items: center; border-left: 2px solid #e2e8f0; padding-left: 10px;">
                <?php if (!empty($typeofrecords)): ?>
                    <select id="ric_record_type" class="ric-select">
                        <?php foreach ($typeofrecords as $fmt => $label): ?>
                            <option value="<?php echo htmlspecialchars($fmt); ?>"><?php echo htmlspecialchars($label); ?></option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
                <button class="ric-btn-primary" onclick="openRicModal('NEW')">
                    <i class="fa-solid fa-plus"></i> <?php echo $msgstr["ric_new_entity"] ?? 'Nova Entidade'; ?>
                </button>
            </div>
        </div>
    </div>

    <table class="table striped">
        <thead>
            <tr>
                <th width="5%">MFN</th>
                <th width="30%"><?php echo $msgstr["ric_entity_type"] ?? 'Tipo de Entidade'; ?></th>
                <th width="45%"><?php echo $msgstr["ric_name_id"] ?? 'Nome / Identificador'; ?></th>
                <th width="20%"><?php echo $msgstr["ric_actions"] ?? 'Ações'; ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($registros)): ?>
                <tr>
                    <td colspan="4" style="text-align:center; padding:20px; color:#64748b;">
                        <?php echo $msgstr["ric_no_entities"] ?? 'Nenhuma entidade localizada.'; ?>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($registros as $reg): ?>
                    <tr>
                        <td><?php echo $reg['mfn']; ?></td>
                        <td><strong><?php echo $reg['type']; ?></strong></td>
                        <td><?php echo $reg['name']; ?></td>
                        <td>
                            <button class="ric-btn-view" onclick="openRicModal('VIEW', <?php echo $reg['mfn']; ?>)"><i class="fa-solid fa-eye"></i> <?php echo $msgstr["ric_view"] ?? 'Ver'; ?></button>
                            <button class="ric-btn-edit" onclick="openRicModal('EDIT', <?php echo $reg['mfn']; ?>)"><i class="fa-solid fa-pen"></i> <?php echo $msgstr["ric_edit"] ?? 'Editar'; ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Navegação / Paginação -->
    <?php if ($total_pages > 1 || $page > 1): ?>
        <div class="ric-pagination">
            <?php if ($page > 1): ?>
                <a href="?action=browse&page=<?php echo $page - 1; ?>&Expresion=<?php echo urlencode($expresion); ?>" class="ric-page-btn">
                    <i class="fa-solid fa-chevron-left"></i> <?php echo $msgstr["ric_prev"] ?? 'Anterior'; ?>
                </a>
            <?php endif; ?>

            <span class="ric-page-info">
                <?php echo ($msgstr["ric_page"] ?? 'Página') . " {$page} " . ($msgstr["ric_of"] ?? 'de') . " {$total_pages} (Total: {$total_records})"; ?>
            </span>

            <?php if ($page < $total_pages): ?>
                <a href="?action=browse&page=<?php echo $page + 1; ?>&Expresion=<?php echo urlencode($expresion); ?>" class="ric-page-btn">
                    <?php echo $msgstr["ric_next"] ?? 'Próxima'; ?> <i class="fa-solid fa-chevron-right"></i>
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</div>

<div id="ricModal" class="ric-modal-overlay">
    <div class="ric-modal-box">
        <div class="ric-modal-header">
            <strong id="ricModalTitle"><?php echo $msgstr["ric_entity_manager"] ?? 'Gerenciador de Entidades'; ?></strong>
            <button class="ric-modal-close" onclick="closeRicModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <iframe id="ric-iframe" src=""></iframe>
    </div>
</div>

<script>
    function openRicModal(action, mfn = '') {
        const modal = document.getElementById('ricModal');
        const iframe = document.getElementById('ric-iframe');
        const title = document.getElementById('ricModalTitle');
        const lang = '<?php echo $ABCD_lang; ?>';

        const lblNew = '<?php echo $msgstr["ric_new_entity"] ?? "Nova Entidade"; ?>';
        const lblEdit = '<?php echo $msgstr["ric_edit_mfn"] ?? "Editando MFN: "; ?>';
        const lblView = '<?php echo $msgstr["ric_view_mfn"] ?? "Visualizando MFN: "; ?>';

        let url = '';
        if (action === 'NEW') {
            title.innerHTML = '<i class="fa-solid fa-file-circle-plus"></i> ' + lblNew;

            let formatParam = '';
            const typeSelect = document.getElementById('ric_record_type');
            if (typeSelect && typeSelect.value) {
                formatParam = '&formato=' + typeSelect.value;
            }

            url = '/central/dataentry/fmt.php?base=ric_cm&cipar=ric_cm.par&Opcion=nuevo&Mfn=New&lang=' + lang + formatParam;
        } else if (action === 'EDIT') {
            title.innerHTML = '<i class="fa-solid fa-pen-to-square"></i> ' + lblEdit + mfn;
            url = '/central/dataentry/fmt.php?base=ric_cm&cipar=ric_cm.par&Opcion=editar&Mfn=' + mfn + '&lang=' + lang;
        } else if (action === 'VIEW') {
            title.innerHTML = '<i class="fa-solid fa-eye"></i> ' + lblView + mfn;
            url = '/content/plugins/ric_cm/?action=read_mfn&mfn=' + mfn;
        }

        iframe.src = url;
        modal.style.display = 'block';
    }

    function closeRicModal() {
        document.getElementById('ricModal').style.display = 'none';
        document.getElementById('ric-iframe').src = '';
        window.location.reload();
    }

    document.getElementById('ric-iframe').onload = function() {
        try {
            const iframeDoc = this.contentDocument || this.contentWindow.document;
            if (iframeDoc.location.href.indexOf('dataentry/fmt.php') !== -1) {
                const style = iframeDoc.createElement('style');
                style.innerHTML = '.headerWindow, .toolbar-dataentry, .helper, .topbar { display: none !important; } body { padding: 15px !important; background: #fff !important; } .middle.form { margin: 0 !important; width: 100% !important; border: none !important; box-shadow: none !important; } td[width="5%"], td.nav-buttons { display: none !important; } .button_browse.show { display: none !important; }';
                iframeDoc.head.appendChild(style);
            }
        } catch (e) {}
    };
</script>

<?php include $abcdPath . '/common/footer.php'; ?>