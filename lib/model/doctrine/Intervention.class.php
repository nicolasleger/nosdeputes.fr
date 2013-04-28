<?php

/**
 * This class has been auto-generated by the Doctrine ORM Framework
 */
class Intervention extends BaseIntervention
{

  public $perso = null;

  public function getLink() {
    sfProjectConfiguration::getActive()->loadHelpers(array('Url'));
    return url_for('@interventions_seance?seance='.$this->getSeance()->id).'#inter_'.$this->getMd5();
  }
  public function getLinkSource() {
  //  return preg_replace("/#[^#]*$/", "", $this->source);
    return $this->source;
  }
  public function getPersonne() {
    return $this->getNomAndFonction();
  }

  public function getFullDate() {
    $datetime = strtotime($this->date);
    $moment = $this->Seance->moment;
    $heuretime = "10:00";
    if (preg_match('/\d:\d/', $moment))
      $heuretime = $moment;
    else if (preg_match('/^(\d)/', $moment, $match))
      $heuretime = sprintf('%02d', 10+4*($match[1]-1)).':00';
    $datetime += strtotime($heuretime) - strtotime('today');
    $timestamp = $this->timestamp;
    $len = strlen($timestamp);
    if ($len > 6)
      $timestamp = substr($timestamp, $len-6, 6) + 0;
    $datetime += $timestamp;
    return date('Y-m-d H:i:s', $datetime);
  }

  public function __toString() {
    if (strlen($this->intervention) > 1024)
      return substr($this->intervention, 0, 512).'...';
    return $this->intervention;
  }

  public function getTitre() {
    if ($this->type === 'question')
      $titre = 'Question orale du ';
    else {
      if ($this->type === 'commission') {
        if ($orga = $this->getOrganisme())
          $titre = $orga." - Intervention";
        else $titre = 'Intervention en commission';
      } else $titre = 'Intervention en hémicycle';
      $titre .= ' le ';
    }
    $titre .= myTools::displayShortDate($this->date);
    if ($this->type != 'commission')      
      $titre .= ' : '.ucfirst($this->getDossier());
    return $titre;
  }

  public function getDossier() {
    if ($this->type === 'question' && $section = $this->Section)
      return $section->getTitre();
    if ($this->type === 'loi' && $section = $this->Section->Section)
      return $section->getTitreComplet();
    return "";
  }

  public function getOrganisme() {
    if ($this->type === 'commission' && $seance = $this->Seance)
      if ($orga = $seance->Organisme)
        return $orga->nom;
    return "";
  }

  public function setSeance($type, $date, $heure, $session, $commission = null) {
    $this->setType($type);
    $seance = Doctrine::getTable('Seance')->getOrCreateItFromSeanceArgs($type, $date, $heure, $session, $commission);
    $id = $this->_set('seance_id', $seance->id);
    $seance->free();
    return $id;
  }
  public function setPersonnaliteByNom($nom, $fonction = null) 
  {
    $this->setFonction($fonction);
    if (!preg_match('/ministre|secr[^t]+taire [^t]+tat|commissaire|garde des sceaux/i', $fonction)) { 
      $personne = Doctrine::getTable('Parlementaire')->findOneByNom($nom);
      if (!$personne)
	  $personne = Doctrine::getTable('Parlementaire')->findOneByNomDeFamille($nom);
      if (!$personne && preg_match("/^de /", $nom)) {
	  $personne = Doctrine::getTable('Parlementaire')->findOneByNomDeFamille(preg_replace("/^de /", "", $nom));
      } 
      if (!$personne && ($this->type != "commission" || $fonction == null || preg_match('/(rapporteur|présidente?$)/i', $fonction))) {
	$personne = Doctrine::getTable('Parlementaire')->similarToCheckPrenom($nom);
      }
      if ($personne) {
	return $this->setParlementaire($personne);
      }
    }
    $personne = Doctrine::getTable('Personnalite')->findOneByNom($nom);
    if (!$personne) {
      $personne = new Personnalite();
      $personne->setNom($nom);
      $personne->save();
    }
    if ($personne) {
      return $this->setPersonnalite($personne);
    }
  }
  public function setParlementaire($parlementaire, $from_db = null) {
    if (isset($parlementaire->id)) {
      $this->_set('parlementaire_id', $parlementaire->id);
      $this->_set('personnalite_id', null);
      if (!$from_db)
        $this->getSeance()->addPresence($parlementaire, 'intervention', $this->source);
      $parlementaire->free();
    }
  }
  public function setPersonnalite($personne) {
    if (isset($personne->id)) {
      $this->_set('parlementaire_id', null);
      $this->_set('personnalite_id', $personne->id);
    }
  }

  public function hasIntervenant() {
    if ($this->parlementaire_id) {
      return true;
    }
    if ($this->personnalite_id) {
      return true;
    }
    return false;
  }

  public function getIntervenant(&$parlementaires = null, &$personnalites = null) {
    if (is_null($this->perso)) {
      if ($this->parlementaire_id) {
	if ($parlementaires) {
	  $this->perso = $parlementaires[$this->parlementaire_id];
	} else {
	  $this->perso = $this->getParlementaire();
	}
      }
      if ($this->personnalite_id) {
	if ($personnalites) {
	  $this->perso = $personnalites[$this->personnalite_id];
	} else {
	  $this->perso = $this->getPersonnalite();
	}
      }
    }
    return $this->perso;
  }

  public function getNomAndFonction() {
    $res = null;
    if ($this->hasIntervenant()) {
      $res = $this->getIntervenant()->getNom();
      if ($this->getFonction())
	$res .= ', '.$this->getFonction();
    }
    return $res;
  }

  public function setContexte($contexte, $date = null, $timestamp = null, $tlois = null, $debug = 0) {

    if ($date && preg_match("/^(\d{4}-\d\d-\d\d)/", $date, $annee)) {
      if (!preg_match("/^".$annee[1]."\d\d:\d\d$/", $date))
        $date = $annee[1]."00:00";
    } else print "WARNING : Intervention $this->id has incorrect date : $date";

    $tlois = preg_replace('/[^,\d]+/', '', $tlois);
    $tlois = preg_replace('/\s+,/', ',', $tlois);
    $tlois = preg_replace('/,\s+/', ',', $tlois);
    $lois = explode(',', $tlois);
    $loisstring = "";
    foreach($lois as $loi) if ($loi) {
      $tag = 'loi:numero='.$loi;
      $this->addTag($tag);
      if ($loisstring == "") $loisstring = "t.numero = $loi";
      else $loisstring .= " OR t.numero = $loi";
    }
    if ($lois[0]) {
      $urls = Doctrine_Query::create()
        ->select('distinct(t.id_dossier_an)')
        ->from('Texteloi t')
        ->where('t.type = ? OR t.type = ? OR t.type = ? OR t.type = ?', array("Proposition de loi", "Proposition de résolution", "Projet de loi", "Texte de la commission"))
        ->andWhere($loisstring)
        ->fetchArray();
      $ct = count($urls);
      if ($ct == 0) $urls = Doctrine_Query::create()
        ->select('distinct(t.id_dossier_an)')
        ->from('Texteloi t')
        ->where($loisstring)
        ->fetchArray();
      $ct = count($urls);
      if ($ct > 1) {
        $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt($contexte, $date, $timestamp));
        if ($debug) {
          print "WARNING : Intervention $this->id has tags lois corresponding to multiple id_dossier_ans : ";
          foreach ($urls as $url)
            print $url['distinct']." ; ";
          print " => Saving to section ".$this->Section."-".$this->Section->id."\n";
          $debug = 0;
        }
        return $debug;
      }
      if ($ct == 0) $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt($contexte, $date, $timestamp));
      else if ($ct == 1) {
        $section1 = Doctrine::getTable('Section')->findOneByContexte($contexte);
        $section2 = Doctrine::getTable('Section')->findOneByIdDossierAn($urls[0]['distinct']);
        if ($section2) {
          if (!$section1) 
            $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt(str_replace(trim(preg_replace('/^([^>]+)(>.*)?$/', '\\1', $contexte)), $section2->titre, $contexte), $date, $timestamp));
          else if ($section1->section_id == $section2->id)
            $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt($section1->titre_complet, $date, $timestamp));
          else {
            $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt($contexte, $date, $timestamp));
            if ($debug) {
              print "WARNING : Intervention $this->id has tags lois corresponding to another section $section2->id";
              print " => Saving to section ".$this->Section."-".$this->Section->id."\n";
              $debug = 0;
            }
            return $debug;
          }
        }
        else {
          $section1 = Doctrine::getTable('Section')->findOneByContexteOrCreateIt($contexte, $date, $timestamp);
          $this->setSection($section1);
          $section1->setIdDossierAn($urls[0]['distinct']);
          $section1->save();
        }
      }
      if ($this->section_id != 1) {
        $titre = $this->Section->Section->getTitre();
        if (!(preg_match('/(cloture|ouverture|question|ordre du jour|calendrier|élection.*nouveau|démission|reprise|examen simplifié|cessation.*mandat|proclamation|souhaits)/i', $titre))) {
          foreach($lois as $loi) {
            $tag = 'loi:numero='.$loi;
            $this->Section->addTag($tag);
            if ($this->Section->section_id && $this->Section->Section->id && $this->Section->section_id != $this->section_id)
              $this->Section->Section->addTag($tag);
          }
        }
      }
      return $debug;
    } else {
      $this->setSection(Doctrine::getTable('Section')->findOneByContexteOrCreateIt($contexte, $date, $timestamp));
      return $debug;
    }
  }

  public function setAmendements($tamendements) {
    $tamendements = preg_replace('/[^,\d]+/', '', $tamendements);
    $amends = preg_split('/\s*,\s*/', $tamendements);
    foreach($amends as $amend) {
      $tag = 'loi:amendement='.$amend;
      $this->addTag($tag);
    }
  }
  
  public function setIntervention($s) {
    $this->_set('nb_mots', str_word_count($s));
    return $this->_set('intervention', $s);
  }

  public function getIntervention($args = array()) {
    $inter = $this->_get('intervention');
    if ($this->type == 'loi' && isset($args['linkify_amendements']) && $linko = $args['linkify_amendements']) {
      $inter = preg_replace('/\(([^\)]+)\)/', '(<i>\\1</i>)', $inter);
      if (preg_match('/<i>n[°os\s]*([\d,\set]+)<\/i>/', $inter, $match)) {
        sfProjectConfiguration::getActive()->loadHelpers(array('Url'));
        foreach (explode(',', preg_replace('/\s+/', '', $match[1])) as $loi)
          $inter = preg_replace('/'.$loi.'/', '<a href="'.url_for('@document?id='.$loi).'">'.$loi.'</a>', $inter);
      }
      if (preg_match_all('/(amendements?[,\s]+(identiques?)?[,\s]*)((n[°os\s]*|\d+\s*|,\s*|à\s*|et\s*|rectifié\s*)+)/', $inter, $match)) {
	$lois = implode(',', $this->getTags(array('is_triple' => true,
						  'namespace' => 'loi',
						  'key' => 'numero',
						  'return' => 'value')));
	if ($lois) for ($i = 0 ; $i < count($match[0]) ; $i++) {
	  $match_protected = preg_replace('/(\s*)(\d[\d\s\à]*rectifiés?|\d[\d\s\à]*)(,\s*|\s*et\s*)*/', '\1%\2%\3', $match[3][$i]);
	  if (preg_match_all('/\s*%([^%]+)%(,\s*|\s*et\s*)*/', $match_protected, $amends)) {
	    $replace = $match_protected;
	    foreach($amends[1] as $amend) {
	      $am = preg_replace('/à+/', '-', $amend);
	      $am = preg_replace('/[^\d\-]+/', '',$am);
	      $link = str_replace('LLL', urlencode($lois), $linko);
	      $link = str_replace('AAA', urlencode($am), $link);
	      $replace = preg_replace('/%'.$amend.'%/', '<a name="amend_'.$am.'" href="'.$link.'">'.$amend.'</a> ', $replace);
	    }
	    $inter = preg_replace('/'.$match[1][$i].$match[3][$i].'/', $match[1][$i].$replace, $inter);
	  }
	}
      }
    }
    return $inter;
  }
}
