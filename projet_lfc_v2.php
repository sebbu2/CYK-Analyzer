<html>
<head>
<title>projet lfc</title>
<style>
textarea[readonly] {
	<!--background-color: #FF0000;-->
	background-color: #CCCCCC;
}
textarea[disabled] {
	background-color: #CCCCCC;
}
textarea {
	background-color: #FFFFFF;
}
TD {
	vertical-align: top;
}
</style>
</head>
<body onload="onload();">

<?php

function grammar_read(&$buffer, array& $grammaireG, array& $grammaireD) {
	$line_buffer='';
	while( ($line_buffer=trim(fgets($buffer,4096)))!==false && strlen($line_buffer)>0 ) {
		grammar_read_parse($line_buffer,$grammaireG,$grammaireD);
	}
}

function grammar_read_parse(&$line_buffer, array& $grammaireG, array& $grammaireD) {
	$expression=('#^([^, \t\r\n]+)\s*(?:\\->| |>|\t)\s*([^, \t\r\n]+)$#');
	$what=array();
	//echo 'vous avez saisi : '.$line_buffer.'<br/>'."\r\n";
	// v�rification syntaxe ( correct ou pas )
	if(preg_match($expression, $line_buffer, $what)) {
		if(array_key_exists('debug',$_REQUEST)) echo 'syntaxe correcte<br/>'."\r\n";
		$grammaireG[]=$what[1];
		$grammaireD[]=$what[2];
	}
	else {
		if(array_key_exists('debug',$_REQUEST)) echo 'syntaxe incorrecte<br/>'."\r\n";
	}
	return;
}

function grammar_read_predecessor($mot_terminal, array& $grammaireG, array& $grammaireD) {
	$res = "";
	for($i=0; $i<count($grammaireG); $i++) {	
		if($mot_terminal==$grammaireD[$i]) {
			if (strlen($res)<=0) {
				$res .= $grammaireG[$i];
			}
			else {
				$res .= (",".$grammaireG[$i]);
			}
		}
	}
	return $res;
}

function nettoyer($s) {
	$res = "";
	$mot = "";
	$posDebut = 0;
	$posVirgule = -1;
	$al=array();
	do {
		// Recherche d'un mot
		$posVirgule = strpos($s,',',$posDebut);
		if($posVirgule!==false)
			$mot = substr($s,$posDebut,$posVirgule-$posDebut);
		else
			$mot = substr($s,$posDebut);
		$posDebut = ($posVirgule!==false)?$posVirgule+1:0;
		// Ajout � la liste des couples
		if (strlen($mot)>0 && !in_array($mot,$al))
			$al[]=$mot;	
	}
	while($posVirgule!==false);
	foreach($al as $value) {
		if (strlen($res)<=0)
			$res .= $value;
		else
			$res .= (",".$value);
	}
	return $res;
}

// On a deux chaines A,B,C (partG) et X,Y,Z (partD), on veut r�pertorier 
// tous les couples (AX AY AZ BX BY ... CZ) dans l'vector r�sultat
function strings2vector($partG, $partD) {
	// On a deux chaines A,B,C et X,Y,Z, on veut r�pertorier tous les 
	// couples (AX AY AZ BX BY ... CZ) dans le vector 'src', ...
	$src=array();
	$motG='';
	$motD='';
	$posDebutG = 0;	$posDebutD = 0;
	$posVirguleG = -1; $posVirguleD = -1;
	do {
		// Recherche d'un mot
		$posVirguleG = strpos($partG,',',$posDebutG);
		if($posVirguleG!==false)
			$motG = substr($partG,$posDebutG,$posVirguleG-$posDebutG);
		else
			$motG = substr($partG,$posDebutG);
		$posDebutG = ($posVirguleG!==false)?$posVirguleG+1:0;
		do {
			// Recherche d'un mot
			$posVirguleD = strpos($partD,',',$posDebutD);
			if($posVirguleD!==false)
				$motD = substr($partD,$posDebutD,$posVirguleD-$posDebutD);
			else
				$motD = substr($partD,$posDebutD);
			$posDebutD = ($posVirguleD!==false)?$posVirguleD+1:0;
			// Ajout � la liste des couples
			if (strlen($motG)>0 || strlen($motD)>0) {
				$src[]=$motG.$motD;
			}
		}
		while($posVirguleD!==false);
	}
	while($posVirguleG!==false);
	return $src;
}

function questCeQuiDonneD(&$partG, &$partD, array& $grammaireG, array& $grammaireD) {
	$res = '';
	//var_dump($partG,$partD,$grammaireG,$grammaireD);die();
	// On a deux chaines A,B,C et X,Y,Z, on veut r�pertorier tous les couples (AX AY AZ BX BY ... CZ) dans le vector 'src', ...
	$src = strings2vector($partG,$partD);
	// ... pour ensuite savoir si des r�gles de grammaire donnent ces couples
	for($i=0; $i<count($grammaireG); $i++) {
		for($j=0; $j<count($src); $j++) {
			if($src[$j]==$grammaireD[$i]) {
				if (strlen($res)<=0)
					$res .= $grammaireG[$i];
				else
					$res .= (",".$grammaireG[$i]);
			}
		}
	}
	return $res;
}

	// Surligne les cases de fa�on � donner une id�e de l'arbre
	// /!\ Travaille directement avec les donn�es globales /!\
	// Fonction r�cusrive : � chaque fois qu'un couple a �t� trouv�, on 
	// continue � partir de ses deux cases
	function surlignerElmtsArbre ($ligneAnalyse, $celluleAnalyse, $top, &$nbFeuilles,array& $pyramide_cky,
		array& $pyramide_arbre, array& $tabGrammaireG, array& $tabGrammaireD) {
		$trouve = false;
		for($cpt=0; $cpt<$nbFeuilles-$ligneAnalyse-1 && !$trouve; $cpt++) {
			$asc_ligne=$nbFeuilles-1-$cpt;
			$dsc_ligne=$ligneAnalyse+1+$cpt;
			$asc_ind=$celluleAnalyse;
			$dsc_ind=$celluleAnalyse+1+$cpt;
			$listeCouplesPossibilites = strings2vector(
				$pyramide_cky[$asc_ligne][$asc_ind],
				$pyramide_cky[$dsc_ligne][$dsc_ind]
			);
			$listeTops = strings2vector($top,'');//(pyramide[ligneAnalyse][celluleAnalyse], "");
			for($l=0; $l<count($listeTops) && !$trouve; $l++) {
				for($k=0; $k<count($listeCouplesPossibilites) && !$trouve; $k++) {
					for($m=0; $m<count($tabGrammaireG) && !$trouve; $m++) {
					if($tabGrammaireG[$m]==$listeTops[$l]) {
							if($listeCouplesPossibilites[$k]==$tabGrammaireD[$m]) {
								$trouve = true;
								$pyramide_arbre[$asc_ligne][$asc_ind] = substr($listeCouplesPossibilites[$k],$l,1);
								$pyramide_arbre[$dsc_ligne][$dsc_ind] = substr($listeCouplesPossibilites[$k],$l+1);
								surlignerElmtsArbre($asc_ligne,$asc_ind,$pyramide_arbre[$asc_ligne][$asc_ind],$nbFeuilles,$pyramide_cky,$pyramide_arbre,$tabGrammaireG,$tabGrammaireD);
								surlignerElmtsArbre($dsc_ligne,$dsc_ind,$pyramide_arbre[$dsc_ligne][$dsc_ind],$nbFeuilles,$pyramide_cky,$pyramide_arbre,$tabGrammaireG,$tabGrammaireD);
							}
						}
					}
				}  
			}
		}
        }

function glob_ext($ext) {
	if(!is_string($ext)) return NULL;
	$files_ar=array();
	if($rContents = opendir('./')) {
		while($sNode = readdir($rContents)) {
			if(!is_dir($sNode )) {
				if( strtolower(substr($sNode,-1-strlen($ext))) == ('.'.strtolower($ext)) ) {
					$files_ar[]=$sNode;
				}
			}
		}
	}
	return $files_ar;
}

function main() {
	ini_set('max_execution_time',0);
	$PHP_SELF=$_SERVER['PHP_SELF'];
	$cmd = !array_key_exists('HTTP_HOST',$_SERVER);
	//$cmd=true;
	//$_REQUEST['debug']=true;
	if(array_key_exists('HTTP_HOST',$_SERVER)) {
		if( !array_key_exists('mot',$_REQUEST) && !array_key_exists('grammaire_file',$_REQUEST) && !array_key_exists('grammaire',$_REQUEST) ) {
			//formulaire
			echo '<script type="text/javascript"><!--'."\r\n";
			echo <<<EOF

function onload() {
	/*if(document.forms['formulaire'].elements['grammaire'].value.length>0) {
		setCheckedValue(document.forms['formulaire'].elements['grammaire_file'],'saisie');
		document.getElementById('grammaire').disabled=false;
		document.getElementById('grammaire2').value='';
	}
	else {
		setCheckedValue(document.forms['formulaire'].elements['grammaire_file'],'math.gra');
		grammaire_get('math.gra');
	}*/
	var grammaire='';
	var radio=document.forms['formulaire'].elements['grammaire_file'];
	if(radio.length == undefined) {
		if(radio.checked) grammaire=radio.value;
	}
	for(var i = 0; i < radio.length; i++) {
		if(radio[i].checked) grammaire=radio[i].value;
	}
	//alert(grammaire);
	if(grammaire.length>0) {
		setCheckedValue(document.forms['formulaire'].elements['grammaire_file'],grammaire);
		if(grammaire=='saisie') {
			document.getElementById('grammaire').disabled=false;
			document.getElementById('grammaire2').value='';
		}
		else {
			document.getElementById('grammaire').disabled=true;
			grammaire_get(grammaire);
		}
	}
}
function setCheckedValue(radioObj, newValue) {
	if(!radioObj)
		return;
	var radioLength = radioObj.length;
	if(radioLength == undefined) {
		radioObj.checked = (radioObj.value == newValue);
		return;
	}
	for(var i = 0; i < radioLength; i++) {
		radioObj[i].checked = false;
		if(radioObj[i].value == newValue) {
			radioObj[i].checked = true;
		}
	}
}

function grammaire_get(fichier) {
	var xhr_object = null;
	if(window.XMLHttpRequest) // Firefox
		xhr_object = new XMLHttpRequest();
	else if(window.ActiveXObject) // Internet Explorer
		xhr_object = new ActiveXObject("Microsoft.XMLHTTP");
	else { // XMLHttpRequest non support� par le navigateur
		alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");
		return;
	}
	var url = "http://ayumifr.free.fr/projet_lfc/"+fichier;
	//alert(url);return;
	xhr_object.open("GET", fichier, true);
	
	xhr_object.onreadystatechange = function() { 
		if(xhr_object.readyState == 4) {
			document.getElementById('grammaire2').value=xhr_object.responseText;
		}
	}
	xhr_object.send(null);
}
EOF;
			echo '//--></script>';
			echo '<table border="1">'."\r\n";
			echo '	<tr>'."\r\n";
			echo '		<td width="50%">'."\r\n";
			echo '<form action="'.$PHP_SELF.'" name="formulaire" method="post">'."\r\n";
			echo 'Veuillez saisir le mot et choisir la grammaire<br/>'."\r\n";
			echo '<input type="text" name="mot" size="53"/><br/>'."\r\n";
			foreach(glob_ext('gra') as $file) {
				echo '<input type="radio" name="grammaire_file" value="'.$file.'" onclick="document.getElementById(\'grammaire2\').disabled=false;document.getElementById(\'grammaire\').disabled=true;grammaire_get(\''.$file.'\');document.getElementById(\'grammaire\').value=\'\'"';
				if($file=='math.gra') echo ' checked="checked"';
				echo '/>'.$file.' &nbsp; '."\r\n";
			}
			echo '<input type="radio" name="grammaire_file" value="saisie" onclick="document.getElementById(\'grammaire2\').disabled=true;document.getElementById(\'grammaire\').disabled=false;document.getElementById(\'grammaire2\').value=\'\';"/>saisie'."\r\n";
			echo '<br/>'."\r\n";
			echo '<textarea cols="40" rows="30" id="grammaire" name="grammaire" disabled="disabled"></textarea><br/>'."\r\n";
			echo '<input type="submit" value="Valider"/><input type="reset" value="Reset"><br/>'."\r\n";
			echo '<form>'."\r\n";
			echo '</td>'."\r\n";
			echo '		<td width="50%"><br/><br/>Contenu de la grammaire :<br/><textarea cols="40" rows="30" id="grammaire2" readonly="readonly"></textarea></td>'."\r\n";
			echo '	</tr>'."\r\n";
			echo '</table>'.'<br/>'."\r\n";
			die();
		}
		else {
			if(array_key_exists('mot',$_REQUEST)) {
				$_REQUEST['mot']=trim($_REQUEST['mot']);
				print('mot &nbsp;&nbsp;d�fini => <pre>'.$_REQUEST['mot'].'</pre><br/>'."\r\n");
			}
			else {
				$_REQUEST['mot']=0;
				print('mot ind�fini => 0<br/>'."\r\n");
			}
			if(array_key_exists('grammaire_file',$_REQUEST)) {
				print('grammaire &nbsp;&nbsp;d�finie => <pre>'.(($_REQUEST['grammaire_file']!='saisie')?$_REQUEST['grammaire_file']:$_REQUEST['grammaire']).'</pre><br/>'."\r\n");
			}
			else {
				print('grammaire ind�finie => S>0<br/>'."\r\n");
			}
		}
	}
	/*if( argc==2 && ( strcmp(argv[1],"--help")==0 || strcmp(argv[1],"-h")==0 ) ) {
		printf("Usage : %s\n",argv[0]);
		printf("Tell if a word belong to a language.\n");
		return EXIT_SUCCESS;
	}*/
	// d�claration variables
	// 1� niveau : position, 2� niveau = case, $= lettres possibles, du haut vers le bas
	$pyramide_cky=array();
	$pyramide_arbre=array(); //*/
	// forme voulue pour la grammaire : qqch_G -> qqch_D
	$grammaireG=array();
	$grammaireD=array();
	// saisie donn�es
	if( file_exists('language.gra') ) {
		if( array_key_exists('HTTP_HOST',$_SERVER) && !array_key_exists('grammaire',$_REQUEST) /*&& array_key_exists('mot',$_REQUEST)/**/ )
			$file=fopen("language.gra",'r');
		else $file=false;
	}
	else $file=false;
	if($cmd) $cin=fopen('php://stdin','r');
	if( !$file ) {
		if($cmd) echo 'fichier grammaire inexistant, saisie manuelle :<br/>'."\r\n";
		$file=&$cin;
		if(array_key_exists('HTTP_HOST',$_SERVER)) {
			if(array_key_exists('grammaire_file',$_REQUEST)) {
				$grammaire_file=$_REQUEST['grammaire_file'];
				if(file_exists($grammaire_file)&&in_array($grammaire_file,glob_ext('gra'))) $grammaire=file_get_contents($grammaire_file);
				elseif(array_key_exists('grammaire',$_REQUEST)) $grammaire=$_REQUEST['grammaire'];
				else $grammaire='S>0';
			}
			else $grammaire='S>0';
			foreach(preg_split("/[\f\v]*[\r\n]+[\f\v]*/", $grammaire) as $line) {
				grammar_read_parse($line,$grammaireG,$grammaireD);
			}
		}
		else grammar_read($file,$grammaireG,$grammaireD);
	}
	else {
		if($cmd) echo 'utilisation du fichier grammaire :<br/>'."\r\n";
		grammar_read($file,$grammaireG,$grammaireD);
		fclose($file);
	}
	// v�rification grammaire ( langage type 0,1,2,3 ou pas langage )
	// grammaire v�rifi�
	// saisie du mot � v�rifier
	$mot='';
	if(array_key_exists('HTTP_HOST',$_SERVER)) {
		if(array_key_exists('mot',$_REQUEST)) $mot=$_REQUEST['mot'];
		else $mot='0';$taille=1;
	}
	if(!$cmd) {
		$taille=strlen($mot);
		$mot2='';
	}
	else do {
		echo 'Veuillez saisir le mot � v�rifier<br/>'."\r\n";
		$mot2=fgets($cin,4096);
		if(strlen($mot)>0) $taille=strlen($mot);
	}
	while($taille==0||!array_key_exists('HTTP_HOST',$_SERVER));
	if(strlen($mot2)>0) $mot=$mot2;
	for($i=0;$i<$taille;$i++) {
		$pyramide_cky[$i]=array();
		$pyramide_arbre[$i]=array();
		for($j=0;$j<$i+1;$j++) {
			$pyramide_cky[$i][$j]='';
			$pyramide_arbre[$i][$j]='';
		}
	}
	// traitement ligne un
	for($i=0;$i<$taille;$i++) {
		$pyramide_cky[$taille-1][$i]=grammar_read_predecessor(substr($mot,$i,1),$grammaireG,$grammaireD);
		$pyramide_arbre[$taille-1][$i]='';
	}
	// boucle pour chaque ligne, on descend � gauche et on monte � droite
	for($ligne=($taille-2); $ligne>=0; $ligne--) { 
			// Toutes les cellules des lignes (autant de cellules que le num�ro de la ligne)
			for($cellule=0; $cellule<=$ligne;$cellule++) {
				// Initialisation � vide
				$pyramide_cky[$ligne][$cellule] = "";
				$pyramide_arbre[$ligne][$cellule] = "";
				// Suite de l'algo
				for($cpt=0;$cpt<$taille-$ligne-1;$cpt++) {
						$trouve = questCeQuiDonneD($pyramide_cky[$taille-1-$cpt][$cellule], $pyramide_cky[$ligne+1+$cpt][$cellule+1+$cpt],
							$grammaireG,	$grammaireD);
						// Premier ajout
						if(strlen($trouve)>0) {
							$pyramide_cky[$ligne][$cellule] .= ",".$trouve;
						}
						if(strlen($pyramide_cky[$ligne][$cellule])>0 && $pyramide_cky[$ligne][$cellule][0]==',')
							$pyramide_cky[$ligne][$cellule] = substr($pyramide_cky[$ligne][$cellule],1);
				}
				$pyramide_cky[$ligne][$cellule] = nettoyer($pyramide_cky[$ligne][$cellule]);
			}
		}
	// copie de l'arbre
	// r�solution de l'arbre ( 1� solution, plus si possible et si temps dispo
	//bool motOK = contientS(pyramide_cky[0][0]);
	//bool motOK = regex_match(pyramide_cky[0][0],regex("(^|,)S(,|$)"));
	//echo 'string('.strlen($pyramide_cky[0][0]).')="'.$pyramide_cky[0][0].'"<br/>'."\r\n";
	$motOK = (strpos($pyramide_cky[0][0],'S')!==false);
	if($motOK) {
		if($cmd) echo 'Le mot est v�rifi�.<br/>'."\r\n";
		// Si OK, alors on surligne les cases pour donner une id�e de l'arbre
		$top = $pyramide_cky[0][0];
		$pyramide_cky[0][0] = 'S';
		$pyramide_arbre[0][0] = $pyramide_cky[0][0];
		surlignerElmtsArbre(0,0,'S',$taille,$pyramide_cky,$pyramide_arbre,$grammaireG,$grammaireD);
		$pyramide_cky[0][0] = $top;
	}
	else {
		echo 'Le mot n\'est pas v�rifi�.<br/>'."\r\n";
		echo 'L\'expression n\'est pas v�rifi�e (ne contient pas "S" au somment de la pyramide). ECHEC<br/>'."\r\n";
		die();
	}
	// param�tre affichage pyramide
	$col=20;
	// affichage pyramide cky
	echo 'pyramide cky :<br/>'."\r\n";
	echo '<table>'."\r\n";
	for($i=0;$i<$taille;$i++) {
		echo '	<tr>'."\r\n";
		for($j=0;$j<($taille-$i-1);$j++) echo '		<td width="'.$col.'" border="0">&nbsp;</td>'."\r\n";
		for($j=0;$j<$i+1;$j++) {
			//echo 'cellule ('.($i+1).','.($j+1).') = '.$pyramide_cky[$i][$j].'<br/>'."\r\n";
			echo '		<td width="'.(2*$col).'" style="border-width:1px;border-style:solid;text-align:center;" colspan="2">'.((strlen($pyramide_cky[$i][$j])>0)?$pyramide_cky[$i][$j]:'&nbsp;').'</td>'."\r\n";
		}
		for($j=0;$j<($taille-$i-1);$j++) echo '		<td width="'.$col.'" border="0">&nbsp;</td>'."\r\n";
		echo '	</tr>'."\r\n";
	}
	echo '</table>'."\r\n";
	echo '<br/>'."\r\n";
	// affichage pyramide arbre
	echo 'pyramide arbre :<br/>'."\r\n";
	echo '<table>'."\r\n";
	for($i=0;$i<$taille;$i++) {
		echo '	<tr>'."\r\n";
		for($j=0;$j<($taille-$i-1);$j++) echo '		<td width="'.$col.'" border="0">&nbsp;</td>'."\r\n";
		for($j=0;$j<$i+1;$j++) {
			//echo 'cellule ('.($i+1).','.($j+1).') = '.$pyramide_arbre[$i][$j].'<br/>'."\r\n";
			echo '		<td width="'.(2*$col).'" style="border-width:1px;border-style:solid;text-align:center;" colspan="2">'.((strlen($pyramide_arbre[$i][$j])>0)?$pyramide_arbre[$i][$j]:'&nbsp;').'</td>'."\r\n";
		}
		for($j=0;$j<($taille-$i-1);$j++) echo '		<td width="'.$col.'" border="0">&nbsp;</td>'."\r\n";
		echo '	</tr>'."\r\n";
	}
	echo '</table>'."\r\n";
	// fin affichage
	echo '<br/>'."\r\n";
	echo 'Programme fini.<br/>'."\r\n";
	if($cmd) fflush($cin);
	//return EXIT_SUCCESS;
}

main();
//
?>

</body>
</html>
