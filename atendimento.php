<?php
//  ini_set('display_errors', 1);
//  ini_set('display_startup_errors', 0);
 //error_reporting(E_ALL);
/*
** Author: Savio Rodrigues  
** Email : saviodevweb@gmail.com / savio.rodrigues@emgetis.se.gov.br
** EMGETIS Empresa Sergipana de Tecnologia da Informação  2025 / Z-doc
*/


include ("../../../../inc/includes.php");
include ("../../../../inc/config.php");
$status = "('5','6')"	;	
//--------------------------------------------------------------------------------
function traduzStatus($status) {
    $statusMap = [
        1 => 'Novo',
        2 => 'Em andamento',
        3 => 'Aguardando',
        4 => 'Planejado',
        5 => 'Resolvido',
        6 => 'Fechado'
    ];
    return $statusMap[$status] ?? 'Desconhecido';
}
//-----------------------------------------------------------------------------------



//-----------------------------------------------------------------------------------

$sql = "SELECT COUNT( * ) AS total
FROM glpi_tickets
WHERE glpi_tickets.status
NOT IN ".$status."
AND glpi_tickets.is_deleted = 0" ;

$result = $DB->query($sql);
$data = $DB->fetchAssoc($result);


$abertos = $data['total']; 
# entity
$sql_e = "SELECT value FROM glpi_plugin_dashboard_config WHERE name = 'entity' AND users_id = ".$_SESSION['glpiID']."";
$result_e = $DB->query($sql_e);
$sel_ent = $DB->result($result_e,0,'value');

//select entity
if($sel_ent == '' || $sel_ent == -1) {
	
	//$entities = $_SESSION['glpiactiveentities'];
	$entities = Profile_User::getUserEntitiesForRight($_SESSION['glpiID'],Ticket::$rightname,Ticket::READALL);								
	$ent = implode(",",$entities);
	
	$entidade = "WHERE entities_id IN (".$ent.") OR is_recursive = 1 ";

}
else {
	$entidade = "WHERE entities_id IN (".$sel_ent.") OR is_recursive = 1 ";
}




$sql_grp = "
SELECT id AS id , name AS name
FROM `glpi_groups`
".$entidade."
ORDER BY `name` ASC";



$result_grp = $DB->query($sql_grp);
$ent = $DB->fetchAssoc($result_grp);

$res_grp = $DB->query($sql_grp);
$arr_grp = array();
$arr_grp[0] = "-- ". __('Select a group', 'dashboard') . " --" ;

$DB->dataSeek($result_grp, 0) ;

while ($row_result = $DB->fetchAssoc($result_grp))		
	{ 
	$v_row_result = $row_result['id'];
	$arr_grp[$v_row_result] = $row_result['name'] ;			
	} 
	
$name = 'sel_grp';
$options = $arr_grp;
$selected = "0";



function dropdown( $name, array $options, $selected=null )
{
    /*** begin the select ***/
    $dropdown = '<select class="chosen-select" tabindex="-1" style="width: 300px; height: 27px;" autofocus onChange="javascript: document.form1.submit.focus()" name="'.$name.'" id="'.$name.'">'."\n";

    $selected = $selected;
    /*** loop over the options ***/
    foreach( $options as $key=>$option )
    {
        /*** assign a selected value ***/
        $select = $selected==$key ? ' selected' : null;

        /*** add each option to the dropdown ***/
        $dropdown .= '<option value="'.$key.'"'.$select.'>'.$option.'</option>'."\n";
    }

    /*** close the select ***/
    $dropdown .= '</select>'."\n";

    /*** and return the completed dropdown ***/
    return $dropdown;
}

if(isset($_REQUEST['ent'])) {

   $entities = Profile_User::getUserEntitiesForRight($_SESSION['glpiID'],Ticket::$rightname,Ticket::READALL);	 	
	
	if(in_array($_REQUEST['ent'], $entities)){
		$id_ent = $_REQUEST['ent'];
		$indexw = "indexw.php?ent=".$id_ent;
		$indexb = "index.php?ent=".$id_ent;
		include "metrics_ent.inc.php";
	} else {
		header("Location: select_ent.php"); 
	}	
}
	
elseif(isset($_REQUEST['grp'])) {
	$id_grp = $_REQUEST['grp'];
	$indexw = "indexw.php?grp=".$id_grp;
	$indexb = "index.php?grp=".$id_grp;
	include "metrics_grp.inc.php";
}

else {
	$id_grp = "";
	$indexw = "indexw.php";
	$indexb = "index.php";
	include "metrics.inc.php";
}
//=============================================
$sql_total_pendente = "SELECT COUNT(*) AS total_geral_pendente FROM glpi_tickets WHERE glpi_tickets.is_deleted = 0 AND glpi_tickets.status = 4 AND YEAR(date) = 2025 $entidade";
$result = $DB->query($sql_total_pendente);
$data = $DB->fetchAssoc($result);
$total_geral_pendente = $data['total_geral_pendente'];
//-------------------------------------------------------------------------------
$sql_total_novos = "SELECT COUNT(*) AS total_geral_novos FROM glpi_tickets WHERE status IN (1, 2) AND YEAR(date) = 2025 $entidade";
$result = $DB->query($sql_total_novos);
$data = $DB->fetchAssoc($result);
$total_geral_novos = $data['total_geral_novos'];
//-------------------------------------------
$sql_total_aprov = "SELECT COUNT(*) AS total_geral_aprov FROM glpi_tickets WHERE status = 5 AND YEAR(date) = 2025 $entidade";
$result = $DB->query($sql_total_aprov);
$data = $DB->fetchAssoc($result);
$total_geral_aprov = $data['total_geral_aprov'];
// Total de em andamento 2025------------------------------------------------------------------
$sql_total_andamento = "SELECT COUNT(*) AS total_geral_andamento FROM glpi_tickets WHERE glpi_tickets.is_deleted = 0 AND glpi_tickets.status IN (1,2) AND YEAR(date) = 2025 $entidade";
$result = $DB->query($sql_total_andamento);
$data = $DB->fetchAssoc($result);
$total_todos_andamento = $data['total_geral_andamento'];
//--------------------------------------------------------------------------------------------------------------------------------
// Total de todos 2025
$sql_total_todos = "SELECT COUNT(*) AS total_geral FROM glpi_tickets WHERE YEAR(date) = 2025 $entidade";
$result = $DB->query($sql_total_todos);
$data = $DB->fetchAssoc($result);
$total_todos = $data['total_geral'];
$resultado = "<h2>Chamados de " . date('Y') . "</h2><ul>";
//-------------------------------------------------------------------
// Consulta para contar o total de chamados fechados (status = 6) de acordo com a entidade
$query_fechados = "
SELECT COUNT(*) AS total_fechados
FROM glpi_tickets
WHERE glpi_tickets.status = 6
AND glpi_tickets.is_deleted = 0
AND YEAR(date) = 2025
$entidade
";

$result_fechados = $DB->query($query_fechados) or die('Erro na query');

$row_fechados = $DB->fetchAssoc($result_fechados);

$total_fechados = $row_fechados['total_fechados'];
//------------------------------------------------------------------------------------------------------------
?>
<!DOCTYPE html>
<html lang="en"><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <title>Dashboard Atendimento</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="../css/bootstrap.css" rel="stylesheet">
    <link href="controlfrog.css" rel="stylesheet" media="screen">   
	<link rel="icon" href="../img/dash.ico" type="image/x-icon" />
   <link rel="shortcut icon" href="../img/dash.ico" type="image/x-icon" />
	<script src="../js/jquery.js"></script>    
	<script src="moment.js"></script>	<!--- TEMPO E DATA---> 
	<script src="jquery.easypiechart.js"></script> <!--- Total de Chamados/chamados hoje--->
	<script src="gauge.js"></script>	<!-- ???--->
	<script src="chart.js"></script>
    <script src="jquery-sparkline.js"></script>		<!--- ???--->	
    <script src="../js/bootstrap.min.js"></script>   <!-- linha do total de chamados e prazo--->
    <script src="controlfrog-plugins.js"></script> <!-- Requisição ou incidente -->
	<link href="../css/font-awesome.css" type="text/css" rel="stylesheet" /> 
	<script src="../js/highcharts.js" type="text/javascript" ></script>
	<!--<script src="../js/highcharts-3d.js" type="text/javascript" ></script>-->
	<script src="../js/themes/dark-unica.js" type="text/javascript" ></script>
	<script src="../js/modules/no-data-to-display.js" type="text/javascript" ></script>
	<script src="reload.js"></script>	
	<script src="reload_param.js"></script>	
	<script>
		var themeColour = 'black';
	</script>
   <script src="controlfrog.js"></script>
<style type="text/css">.jqstooltip { position: absolute;left: 0px;top: 0px;visibility: hidden;background: rgb(0, 0, 0) transparent;background-color: rgba(0,0,0,0.6);filter:progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000);-ms-filter: "progid:DXImageTransform.Microsoft.gradient(startColorstr=#99000000, endColorstr=#99000000)";color: white;font: 10px arial, san serif;text-align: left;white-space: nowrap;padding: 5px;border: 1px solid white;z-index: 10000;}.jqsfield { color: white;font: 10px arial, san serif;text-align: left;}</style></head>

<body class="black" onload="reloadPage(); initSpark('<?php echo $quantm2; ?>'); initSparkDay('<?php echo $quantd2; ?>'); initGauge('0','100','<?php echo $gauge_val; ?>'); initPie('<?php echo $res_days; ?>'); initFunnel('<?php echo $sta_values; ?>','<?php echo $sta_labels; ?>'); initRag('<?php echo $types; ?>','<?php echo $rag_labels; ?>'); initSingle1('<?php echo $satisf; ?>');">

<div class="container-fluid">	

<div class="row-fluid" style="margin-top: 25px;">
<div class="col-lg-12" role="main">
<div style="display: flex; justify-content: center; align-items: center; ;">
<div><h2><i class="fa-solid fa-triangle-exclamation"></i>
<?php include '/select_grupo.php';?> </h2>
<form id="form1" name="form1" class="form_rel" method="post" action="select_grupo.php?sel=1">
		 <div>
		<button class="btn btn-primary btn-sm" type="button" name="Limpar" value="Limpar" onclick="location.href='http://helpdesk.emgetis.se.gov.br/front/ticket.php'" ><?php echo __('Procurar chamado'); ?> </button>
		<div class="col-xs-3 col-sm-4 col-md-4 col-lg-1 form-group pull-right" style="float: right; width:125px;">
			<i class="glyphicon glyphicon-refresh"></i><text id="countDownTimer"></text>
			<select id="reload_selecter" class="form-control pull-right">
				<option value="3600">1 hr</option>
				<option value="1800">30 min</option>
				<option value="360">6 min</option>
				<option value="60">1 min</option>
			</select>
		</div>	
	</div>
</form> 
<div class="row-status d-flex justify-content-between flex-wrap">
    
<div class="row-status d-flex" style="font-size: 6px; display: flex; flex-wrap: nowrap; width: 100%;">

<div class="cf-item-status tickets new" style="min-height: 100px; width: 20%;">
	<header>
		<p><span></span><?php echo "Chamados 2025";?></p>
	</header>
	<div class="content">
		<div class="metric5"><?php echo $total_todos;?></div>
		<div class="metric-small5"></div>
	</div>
</div>

<div class="cf-item-status tickets new" style="min-height: 100px; width: 20%;">
	<header>
		<p>
			<a style="text-decoration: none; color: inherit;" >
				<span></span><?php echo 'Atribuídos';?>
			</a>
		</p>
	</header>
	<div class="content">
		<div class="metric5"><?php echo $total_todos_andamento;?></div>
		<div class="metric-small5"></div>
	</div>
</div>
<div class="cf-item-status tickets new" style="min-height: 100px; width: 20%;">
	<header>
		<p>
			<a style="text-decoration: none; color: inherit;" >
				<span></span><?php echo 'Pendentes';?>
			</a>
		</p>
	</header>
	<div class="content">
		<div class="metric5"><?php echo $total_geral_pendente;?></div>
		<div class="metric-small5"></div>
	</div>
</div>

<div class="cf-item-status tickets new" style="min-height: 100px; width: 20%;">
	<header>
		<p>
			<a style="text-decoration: none; color: inherit;" >
				<span></span><?php echo 'Solucionados';?>
			</a>
		</p>
	</header>
	<div class="content">
		<div class="metric5"><?php echo $total_geral_aprov;?></div>
		<div class="metric-small5"></div>
	</div>
</div>
<div class="cf-item-status tickets new" style="min-height: 100px; width: 20%;">
	<header>
		<p><span></span><?php echo 'Fechados'; ?></p>
	</header>
	<div class="content">
		<div class="metric5"><?php echo $total_fechados; ?></div>
		<?php 
		if ($count_soluc < 5) {
			echo "<div class='metric-small5'></div>";
		}
		?>
	</div>
</div>
</div>
	<!----------------------------------------------------- fim da row1 ------------------------------------------------------------>

	<div class="row row-fluid" style="margin-top:40px;">														
			<div style="" class="col-lg-6 cf-item">
					<div id="cf-funnel-" class="cf-funnelx" style="margin-top: 2px;">
						<?php include ("lista_atribuido_atendimento.php");  ?>
					</div>
			</div> 	
			<div style="" class="col-lg-6 cf-item">
					<div id="cf-funnel-" class="cf-funnelx" style="margin-top: 2px;">
						<?php include ("lista_pendente_atendimento.php");  ?>
					</div>
			</div> 	<!-- //end cf-item -->		
				</div> <!-- //end row -->
</div> <!-- //end main -->  	
</div> <!-- //end row -->				
</div> <!-- //end container -->
</div>
</script>
</body>
</html>


