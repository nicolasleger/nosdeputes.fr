#!/usr/bin/perl

use WWW::Mechanize;
use HTML::TokeParser;

@url = (
    "http://www.assemblee-nationale.fr/13/cri/2009-2010/",
);

$a = WWW::Mechanize->new();

foreach $url (@url) {
    $a->get($url);
    $content = $a->content;
    $p = HTML::TokeParser->new(\$content);
    $cpt = 0; 
    while ($t = $p->get_tag('a')) {
	$txt = $p->get_text('/a');
	if ($txt =~ /(\d+[\Serm]+\s+\S+ance|S\S+ance uniq)/i) {
	    $a->get($t->[1]{href});
	    $file = $a->uri();
	    $file =~ s/\//_/gi;
	    $file =~ s/\#.*//;
	    #on ne peut pas quitter dès le premier, seulement au bout de 
	    #trois fois on est sur qu'il n'y a pas de nouveaux fichiers
	    if (-s "html/$file") {
		$cpt++;
		exit if ($cpt > 3);
		break;
	    }
	    $cpt = 0;
	    print "$file\n";
	    open FILE, ">:utf8", "html/$file";
	    print FILE $a->content;
	    close FILE;
	    $a->back();
	}
    }
}
