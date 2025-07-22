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


if (empty($sel_ent) || $sel_ent == -1) {
    $entities = $_SESSION['glpiactiveentities'] ?? [0];
    $ent = implode(",", $entities);
    $entidade = "AND glpi_tickets.entities_id IN ($ent)";
} else {
    $entidade = "AND glpi_tickets.entities_id IN ($sel_ent)";
}

$sql = "
SELECT 
    glpi_tickets.id, 
    glpi_tickets.name AS ticket_name, 
    glpi_tickets.date_mod AS date, 
    GROUP_CONCAT(CONCAT(u.firstname, ' ', u.realname) SEPARATOR ', ') AS tecnicos,
    e.name AS entidade
FROM glpi_tickets
LEFT JOIN glpi_tickets_users tu ON tu.tickets_id = glpi_tickets.id AND tu.type = 2
LEFT JOIN glpi_users u ON tu.users_id = u.id
LEFT JOIN glpi_entities e ON glpi_tickets.entities_id = e.id
WHERE glpi_tickets.is_deleted = 0
AND glpi_tickets.status = 2
AND YEAR(date) = 2025
$entidade
GROUP BY glpi_tickets.id
ORDER BY glpi_tickets.date_mod DESC
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
<meta charset="UTF-8">
<h2 style="text-align:center; color: rgb(250, 83, 5);">Últimos Chamados Novos e Atribuídos</h2>


<div id="container-atribuido">
<?php for ($pagina = 0; $pagina < $total_paginas; $pagina++): ?>
    <div class="bloco-atribuido" style="<?= $pagina === 0 ? '' : 'display:none;' ?>">

        <table border="1" cellspacing="0" cellpadding="5" style="font-size: 16px; width: 100%; max-width: 800px; margin: auto; border-collapse: collapse; table-layout: fixed; word-wrap: break-word;">
            <thead style="background-color:rgb(250, 83, 5); color: #fff;">
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Tempo e órgão</th>
                    <th>Técnico</th>         
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
                <tr style="font-size: 14px; background-color: #333; color: #fff;">
                    <td><?= $row['id'] ?></td>
                    <td><?= htmlspecialchars($row['ticket_name']) ?></td>
                    <td>
                        Aberto há <?= $diferenca ?> dia<?= $diferenca != 1 ? 's' : '' ?><br>por <?= htmlspecialchars($row['entidade'] ?? 'Não informada') ?>
                    </td>
                    <td><?= htmlspecialchars($row['tecnicos'] ?? 'Sem técnico') ?></td>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
<?php endfor; ?>
</div> 

<div id="pagination-atribuido" style="text-align:center; margin-top: 10px;"></div>   

<script>
document.addEventListener("DOMContentLoaded", function () {
    const container = document.getElementById("container-atribuido");
    const pages = container.querySelectorAll(".bloco-atribuido");

    const pagination = document.getElementById("pagination-atribuido");
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
            html += `<button onclick="prevPage()" style="margin: 0 5px;">←</button>`;
            for (let i = 0; i < pages.length; i++) {
                if (i === currentPage) {
                    html += `<strong style="margin: 0 3px;">${i + 1}</strong>`;
                } else {
                    html += `<a href="#" onclick="showPage(${i}); return false;" style="margin: 0 3px;">${i + 1}</a>`;
                }
            }
            html += `<button onclick="nextPage()" style="margin: 0 5px;">→</button>`;
        }
        pagination.innerHTML = html;
    }

    window.showPage = showPage;
    window.prevPage = function () {
        let newIndex = (currentPage - 1 + pages.length) % pages.length;
        showPage(newIndex);
    };
    window.nextPage = function () {
        let newIndex = (currentPage + 1) % pages.length;
        showPage(newIndex);
    };

    showPage(0);
    setInterval(() => {
        nextPage();
    }, 15000); // 15 segundos

 
});
</script>
</body>
</html>
