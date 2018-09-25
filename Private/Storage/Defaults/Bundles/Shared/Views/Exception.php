<?php 
use Polyfony as pf; 
use Polyfony\Locales as Loc;
use Polyfony\Format as Fmt;
use Polyfony\Request as Req;
use Polyfony\Config as Cfg;
use Polyfony\Security as Sec;
?>
<style type="text/css">
	.row {
		padding-top: 2em;
	}
	.card {
		margin-top: 20px; 
		display: none;
		line-height: 24px;
	}
	strong {
		font-weight: bold;
	}
</style>
<div class="container-fluid">
	<div class="row justify-content-center" >
		<div class="col-xs-12 col-sm-12 col-md-12 col-lg-8">	
			<h1>
				<span class="<?= $this->icon; ?>"></span> 
				<?= Loc::get($this->Exception->getMessage()); ?>
			</h1>
			<div class="">
				<a href="#" class="btn btn-warning" onclick="reportIncident()">
					<span class="fa fa-send"></span> 
					<?= Loc::get('Envoyer cet incident à l\'équipe technique'); ?> 
				</a> 
				<a href="#" class="btn btn-outline-secondary" onclick="document.getElementById('trace').style.display='block';">
					<?= Loc::get('Détails techniques'); ?> 
					<span class="fa fa-caret-down"></span>
				</a>
			</div>
			<div class="card" id="trace">
				<?= Polyfony\Exception::convertTraceToHtml($this->Exception->getTrace()); ?>
			</div>
<textarea id="dump" style="display:none;">
Error : <?= $this->Exception->getMessage(); ?>

Code : <?= $this->Exception->getCode(); ?>

Date : <?= date('r'); ?> 
URL : <?= Fmt::htmlSafe(Req::getUrl()); ?>

Method : <?= Req::isPost() ? 'POST' : 'GET'; ?>

REFERER : <?= Fmt::htmlSafe(Req::server('HTTP_REFERER', 'None')); ?>

Domain : <?= Cfg::get('router','domain'); ?>

Agent : <?= Fmt::htmlSafe(Req::server('HTTP_USER_AGENT')); ?>

IP : <?= Fmt::htmlSafe(Req::server('REMOTE_ADDR')); ?>

Protocol : <?= Req::getProtocol(); ?>

User : <?= Sec::get('id'); ?>

Trace : 
<?= $this->Exception->getTraceAsString(); ?>
</textarea>
<script type="text/javascript">
function reportIncident() {

	var link = "mailto:"+'<?= Cfg::get('mail','tech_support_mail'); ?>'
	+ "?subject=" + escape("Exception (<?= Cfg::get('router','domain'); ?>) - <?= $this->Exception->getMessage(); ?>")
	+ "&body=" + escape(document.getElementById('dump').value);

	window.location.href = link;

}
</script>
		</div>
	</div>
</div>
