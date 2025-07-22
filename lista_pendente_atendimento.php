<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include ("../../../../inc/includes.php");
include ("../../../../inc/config.php");

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

$glpiID = $_SESSION['glpiID'] ?? 0;
$entidade_param = $_GET['ent'] ?? null;

if (!empty($entidade_param)) {
    $sel_ent = intval($entidade_param);
    $entidade = "AND glpi_tickets.entities_id IN ($sel_ent)";
} else {
    $sql_e = "SELECT value FROM glpi_plugin_dashboard_config WHERE name = 'entity' AND users_id = $glpiID";
    $result_e = $DB->query($sql_e);
    $sel_ent = $DB->result($result_e, 0, 'value');

    if (empty($sel_ent) || $sel_ent == -1) {
        $entities = $_SESSION['glpiactiveentities'] ?? [0];
        $ent = implode(",", $entities);
        $entidade = "AND glpi_tickets.entities_id IN ($ent)";
    } else {
        $entidade = "AND glpi_tickets.entities_id IN ($sel_ent)";
    }
}

// Consulta dos chamados pendentes
$sql = "
SELECT 
    glpi_tickets.id, 
    glpi_tickets.name AS ticket_name, 
    glpi_tickets.date, 
    glpi_users.realname, 
    glpi_users.firstname,
    GROUP_CONCAT(DISTINCT glpi_groups.name ORDER BY glpi_groups.name SEPARATOR ', ') AS orgao
FROM glpi_tickets
LEFT JOIN glpi_users ON glpi_tickets.users_id_recipient = glpi_users.id
LEFT JOIN glpi_groups_users ON glpi_users.id = glpi_groups_users.users_id
LEFT JOIN glpi_groups ON glpi_groups_users.groups_id = glpi_groups.id
WHERE glpi_tickets.is_deleted = 0
AND glpi_tickets.status = 4
AND YEAR(date) = 2025
$entidade
GROUP BY glpi_tickets.id, glpi_tickets.name, glpi_tickets.date, glpi_users.realname, glpi_users.firstname
ORDER BY glpi_tickets.date DESC
";

$query = $DB->query($sql);

// Paginação
$chamados = [];
while ($row = $DB->fetchAssoc($query)) {
    $chamados[] = $row;
}
$total_por_pagina = 10;
$total_paginas = ceil(count($chamados) / $total_por_pagina);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Últimos Chamados Pendentes</title>
    <style>
        body {
            background-color: #111;
            color: white;
            font-family: Arial, sans-serif;
        }
        select.form-control {
            height: 30px;
            padding: 2px 8px;
        }
    </style>
</head>
<body>

<h2 style="text-align:center; color: #44A9A8;">Últimos Chamados Pendentes</h2>


<!-- Tabelas com paginação -->
<?php for ($pagina = 0; $pagina < $total_paginas; $pagina++): ?>
    <div class="bloco-pendente" style="<?= $pagina === 0 ? '' : 'display:none;' ?>">
        <table border="1" cellspacing="0" cellpadding="5" style="font-size: 14px; width: 100%; max-width: 800px; margin: auto; border-collapse: collapse; table-layout: fixed; word-wrap: break-word;">
            <thead style="background-color: #44A9A8; color: #fff;">
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Órgão</th>
                    <th>Data e Solicitante</th>    
                </tr>
            </thead>
            <tbody>
                <?php
                $inicio = $pagina * $total_por_pagina;
                $fim = min($inicio + $total_por_pagina, count($chamados));
                for ($i = $inicio; $i < $fim; $i++):
                    $row = $chamados[$i];
                    $abertura = new DateTime($row['date']);
                    $hoje = new DateTime();
                    $diferenca = $abertura->diff($hoje)->days;
                ?>
                <tr style="font-size: 12px; background-color: #333; color: #fff;">
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['ticket_name']) ?></td>
                    <td><?= htmlspecialchars($row['orgao'] ?? 'Não informado') ?></td>
                    <td>
                        Aberto há <?= $diferenca ?> dia<?= $diferenca != 1 ? 's' : '' ?><br>
                        por <?= htmlspecialchars($row['firstname'] ?? 'Não informado') ?>
                    </td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
<?php endfor; ?>

<!-- Paginação manual -->
<div id="pagination-pendente" style="text-align:center; margin-top: 10px;"></div>

<script>
(function () {
    const pages = document.querySelectorAll(".bloco-pendente");
    const pagination = document.getElementById("pagination-pendente");
    let currentPage = 0;

    function showPage(index) {
        pages[currentPage].style.display = "none";
        currentPage = index;
        pages[currentPage].style.display = "block";
        updatePagination();
    }

    function updatePagination() {
        let html = '';
        if (pages.length > 1) {
            html += `<button onclick="prevPagePendente()" style="margin: 0 5px;">←</button>`;
            for (let i = 0; i < pages.length; i++) {
                if (i === currentPage) {
                    html += `<strong style="margin: 0 3px;">${i + 1}</strong>`;
                } else {
                    html += `<a href="#" onclick="showPagePendente(${i}); return false;" style="margin: 0 3px;">${i + 1}</a>`;
                }
            }
            html += `<button onclick="nextPagePendente()" style="margin: 0 5px;">→</button>`;
        }
        pagination.innerHTML = html;
    }

    // Expõe as funções com nomes únicos
    window.showPagePendente = showPage;
    window.prevPagePendente = function () {
        let newIndex = (currentPage - 1 + pages.length) % pages.length;
        showPage(newIndex);
    };
    window.nextPagePendente = function () {
        let newIndex = (currentPage + 1) % pages.length;
        showPage(newIndex);
    };

    showPage(0);
    setInterval(() => {
        nextPagePendente();
    }, 15000);
})();
</script>

</body>
</html>
