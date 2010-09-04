<?php

class SolrConnector extends sfLogger
{
  private $solr = NULL;
  private $_options = NULL;

  protected function doLog($message, $priority)
  {
    error_log(sprintf('%s (%s)', $message, sfLogger::getPriorityName($priority)));
  }

  public function __construct( $listener_options = NULL)
  {
    $host = sfConfig::get('app_solr_host', 'localhost');
    $port = sfConfig::get('app_solr_port', '8983');
    $url = sfConfig::get('app_solr_url', '/solr');
    $this->solr = new Apache_Solr_Service($host, $port, $url);
    
    if(!$this->solr->ping()) {
      throw new Exception('Search is not available right now.');
    }
    
    $this->_options = $listener_options;

    return $this->solr;
  }
  

  public function updateFromCommands() {
    $file = SolrCommands::getCommandContent();
    foreach(file($file) as $line) {
      if (preg_match('/(UPDATE|DELETE) : (.+)/', $line, $matches)) {
	$obj = json_decode($matches[2]);
	if ($matches[1] == 'UPDATE') {
	  $this->updateLuceneRecord($obj);
	}else{
	  $this->deleteLuceneRecord($obj->id);
	}
      }
    }
    SolrCommands::releaseCommandContent();
  }


  public function deleteLuceneRecord($solr_id)
  {
    if($this->solr->deleteById($solr_id) ) {
      return $this->solr->commit();
    }
    return false;
  }

  public function updateLuceneRecord($obj)
  {
     $document = new Apache_Solr_Document(); 
     $document->addField('id', $obj->id); 
     $document->addField('object_id', $obj->object_id); 
     $document->addField('object_name', $obj->object_name); 
     if (isset($obj->wordcount))
       $document->addField('wordcount', $obj->wordcount); 
     if (isset($obj->title))
       $document->addField('title', $obj->title->content, $obj->title->weight); 
     if (isset($obj->description))
       $document->addField('description', $obj->description->content, $obj->description->weight); 
     if (isset($obj->date))
       $document->addField('date', $obj->date->content, $obj->date->weight); 
     $this->solr->addDocument($document);
     $this->solr->commit();
  }

  public function deleteAll() {
    $this->solr->deleteByQuery('*:*');
    $this->solr->commit();
  }

  public function search($queryString, $params = array(), $offset = 0, $maxHits = 0) {
    if($maxHits == 0)
        $maxHits = sfConfig::get('app_solr_max_hits', 256);
    $response = $this->solr->search($queryString, $offset, $maxHits, $params);
    return unserialize($response->getRawResponse());
  }
  
}
