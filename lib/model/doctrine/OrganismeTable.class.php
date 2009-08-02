<?php
/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class OrganismeTable extends Doctrine_Table
{
  public function findOneByNomOrCreateIt($nom, $type) {
    $nom = strtolower($nom);
    $nom = preg_replace('/(&#8217;|\')/', '’', $nom);
    $nom = preg_replace('/\W+$/', '', $nom);
    $nom = preg_replace('/\s+/', ' ', $nom);
    $nom = preg_replace('/assemblée nationale/', "bureau de l'assemblée nationale", $nom);
    $org = $this->findOneByNom($nom);
    if (!$org) {
      $orgs = doctrine::getTable('Organisme')->createQuery('o')->where('type = ?', $type)->execute();
      foreach($orgs as $o) {
	$res = similar_text($o->nom, $nom, $pc);
	if ($pc > 90) {
	  //	  echo "$nom $pc\n".$o->nom."\n";
	  $org = $o;
	  break;
	}
      }
    }
    if (!$org) {
      $org = new Organisme();
      $org->type = $type;
      $org->nom = $nom;
      $org->save();
    }
    return $org;
  }
}