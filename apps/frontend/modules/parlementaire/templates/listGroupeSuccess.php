<?php $nResults = $pager->getNbResults(); 
$title = "Composition du groupe ".$orga->getNom()." (".$orga->getSmallNomGroupe().")"; ?>
<h1><?php echo $title." ".$orga->getCouleur(); $sf_response->setTitle($title); ?></h1>
<h2><?php echo $nResults; ?> député<?php if ($nResults > 1) echo 's'; ?>&nbsp;:</h2>
<ul>
<?php foreach($pager->getResults() as $parlementaire) : ?>
<li><?php echo $parlementaire->getPOrganisme($orga->getNom())->getFonction(); ?> : <?php echo link_to($parlementaire->nom, 'parlementaire/show?slug='.$parlementaire->slug); ?> (<?php echo link_to($parlementaire->nom_circo, '@list_parlementaires_circo?search='.$parlementaire->nom_circo); ?>)</li>
<?php endforeach ; ?>
</ul>
<?php if ($pager->haveToPaginate()) :

$uri = $sf_request->getUri();
$uri = preg_replace('/page=\d+\&?/', '', $uri);

if (!preg_match('/[\&\?]$/', $uri)) {
  if (preg_match('/\?/', $uri)) {
    $uri .= '&';
  }else{
    $uri .= '?';
  }
}
include_partial('parlementaire/paginate', array('pager'=>$pager, 'link'=>$uri));
endif;
