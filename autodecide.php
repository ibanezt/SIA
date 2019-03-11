<?php
	//phpinfo();
	ini_set('display_errors','On'); 
	//error_reporting(-1);
	//Written by Terrell Ibanez
	
	//echo 'Request Received: ' . $_GET['r'] . '<br>';
	//echo 'Request Received: ' . $_SERVER['QUERY_STRING'];
	//CITATION REMVOAL: SPOTIFY
	
	$InterroNouns = array("who", "what", "when", "where", "why", "how", "which");
	$Copulas = array("is", "was", "are");
	$Articles = array("the", "a", "an");
	$Prepositions = array("of", "for");
	$NoCapitalize = array("of", "for");
	
	
	$InfoCommands = array("tell", "describe");
	$ActionCommands = array("call", "send", "open");
	//$Query = $_GET['r'];
	//Classification ENUMs
	abstract class WordType {
		const InterrogativeNoun = 0;
		const Copula = 1;
		const Article = 2;
		const Subject = 3;
	}
	abstract class SpecialInterroType {
		const Location = 0;
		const NOTA = 1;
	}
	abstract class SpecialWordType {
		const Weather = 0;
		const NoCapitalize = 1;
		const NOTA = 2;
	}
	abstract class ClauseType {
		const AskInfo = 0;
		const Subject = 1;
		const Unknown = 2;
		const NOTA = 3;
	}
	
	abstract class ActionType {
		const Description = 0;
		const Command = 1;
		const Definition = 2;
		const Converse = 3;
		const Unknown = 4;
	}
	
	abstract class ConceptType {
		const Person = 0;
		const Place = 1;
		const Noun = 2;
	}
	//Utilities
	function CapitalizeFirst($Word) {
		global $NoCapitalize;
		$Low = strtolower($Word);
		for ($i = 0; $i < count($NoCapitalize); $i++) {
			if (strcasecmp($Low, $NoCapitalize[$i]) == 0) {
				return $Low;
			} 
		}
		$Upper = strtoupper($Word);
		$F = substr($Upper, 0, 1);
		$B = substr($Low, 1);
		return $F . $B;
	}
	
	function ClassifyWord($Word) {
		global $InterroNouns,$Copulas, $Articles;
		for ($i = 0; $i < count($InterroNouns); $i++) {
			if (strcasecmp($Word, $InterroNouns[$i]) == 0) {
				return WordType::InterrogativeNoun;
			} 
		}
		for ($i = 0; $i < count($Copulas); $i++) {
			if (strcasecmp($Word, $Copulas[$i]) == 0) {
				return WordType::Copula;
			} 
		}
		for ($i = 0; $i < count($Articles); $i++) {
			if (strcasecmp($Word, $Articles[$i]) == 0) {
				return WordType::Article;
			} 
		}
		return WordType::Subject;
	}
	
	function RetrieveInfo($R) {
		$ExtPat = "/^[^\.]+/";
		//$ExtPat = "/(((?<=[a-z0-9])[.?!])|(?<=[a-z0-9][.?!]\"))(\s|\r\n)(?=\"?[A-Z])/";
		//$ExtPat = "/(*)+(?<!\..)([\?\!\.]+)\s(?!.\.)/";
		//echo $R;
		$Query = $R;
		$XMLe = simplexml_load_file("https://en.wikipedia.org/w/api.php?action=query&prop=extracts&format=xml&exintro=&explaintext=&exsectionformat=plain&indexpageids=&exportnowrap=&iwurl=&titles=" . $Query . "&redirects=");
		$Str = strip_tags($XMLe->query->pages[0]->page->extract);
		//echo $Str;
		$Ret = "";
		/*
		if (preg_match($ExtPat, $Str, $matches) == 1) {
			$Ret = $matches[0];
		}
		//Check for REDIRECT
		if (preg_match("/(REDIRECT)|(redirect)/", $Str, $mat) == 1) {
			$Query = substr($Str, 9, -1);
			//echo $Query;
			//echo $mat[0];
			$Query = urlencode($Query);
			$XMLe = simplexml_load_file("https://en.wikipedia.org/w/api.php?action=query&prop=extracts&titles=" . $Query . "&format=xml");
			//https://en.wikipedia.org/w/api.php?action=query&titles=University%20of%20Central%20Florida&prop=revisions&rvprop=content
			// /w/api.php?action=query&prop=extracts&format=xml&exintro=&explaintext=&exsectionformat=plain&indexpageids=&exportnowrap=&iwurl=&titles=University_of_Central_Florida&redirects=
			//echo $XMLe;
			
			
			$Str = strip_tags($XMLe->query->pages[0]->page->extract);
			//echo $Str;
			if (preg_match($ExtPat, $Str, $tam) == 1) {
				$Ret = $tam[0];
			}
			//$Ret = preg_split("/(?<!\..)([\?\!\.]+)\s(?!.\.)/",$Str,-1, PREG_SPLIT_DELIM_CAPTURE);
			//$Ret = preg_split("/[:space:]{1}/",$Str,-1, PREG_SPLIT_DELIM_CAPTURE);
		}
		*/
		//$Ret = preg_split("/[\.]\s[A-Z]/", $Str, null, PREG_SPLIT_DELIM_CAPTURE);
		//NOTE CAPTURE REQUIRES PARENTHETICAL NOTATION
		$Ret = preg_split("/([^A-Z\.][\.])\s[A-Z]/", $Str, -1, PREG_SPLIT_DELIM_CAPTURE);
		if (count($Ret) > 1) {
			return $Ret[0] . $Ret[1];
		}
		else {
			return $Ret[0];
		}
	}
	
	//Classes
	class Word {
		private $Type;
		private $Value;
		function __construct($I) {
			$this->Type = ClassifyWord($I);
			//echo $this->Type; //DEBUG
			$this->Value = $I;
		}
		function getType() {
			return $this->Type;
		}
		function getValue() {
			return $this->Value;
		}
		function capitalize() {
			$this->Value = CapitalizeFirst($this->Value);
		}
	}
	
	class Clause {
		private $Type;
		private $Words;
		function __construct($T) {
			$this->Type = $T;
		}
		function add($A) {
			$this->Words[] = $A;
		}
		function setType($T) {
			$this->Type = $T;
		}
		function getType() {
			return $this->Type;
		}
		function getWords() {
			return $this->Words;
		}
		function getSubject() {
			$S = "";
			for ($i = 0; $i < count($this->Words); $i++) {
				if ($this->Words[$i]->getType() == WordType::Subject) {
					$S = $S . " " . $this->Words[$i]->getValue();
				}
			}
			//echo $S; //DEBUG
			return substr($S, 1); //Remove Leading Space
		}
	}
	
	function IdentifyClause($WordSet) {
		$Len = count($WordSet);
		switch($Len) {
			//AskClause?
			case 2:
				if ($WordSet[0]->getType() == WordType::InterrogativeNoun) {
					if ($WordSet[1]->getType() == WordType::Copula) {
						return ClauseType::AskInfo;
					}
					else {
						return ClauseType::NOTA;
					}
				}
				else {
					return ClauseType::NOTA;
				}
			break;
			
			default:
				return ClauseType::NOTA;
			break;
		}
	}
	
	$Query = $_GET['r'];
	//echo $Query; //DEBUG
	$Source = explode(" ", $Query);
	
	//$Classifications = array(count($Words));
	
	/*	$Classifications */
	//Classify Words
	for ($i = 0; $i < count($Source); $i++) {
		$Classifications[$i] = new Word($Source[$i]);
	}
	
	//var_dump($Classifications); //DEBUG
	
	//Identify Subject Clauses
	$ClauseMode = ClauseType::NOTA;
	/* $Clauses */
	$CurrentPos = -1;
	//$Clauses[0] = new Clause(ClauseClass::Unknown, $Words[i]);
	for ($i = 0; $i < count($Classifications); $i++) {
			//Article starts new SubjectClause
		if ($Classifications[$i]->getType() == WordType::Article) {
			$ClauseMode = ClauseType::Subject;
			$CurrentPos++;
			$Clauses[$CurrentPos] = new Clause(ClauseType::Subject);
			$Clauses[$CurrentPos]->add($Classifications[$i]);
		}
		elseif ($Classifications[$i]->getType() == WordType::Subject){
			$Classifications[$i]->capitalize(); //Wikipedia API Compatability
			if ($ClauseMode == ClauseType::Subject) {
				//Add Subject to Existing Subject Clause
				$Clauses[$CurrentPos]->add($Classifications[$i]);
			}
			else { //Start new Subject Clause
				$ClauseMode = ClauseType::Subject;
				$CurrentPos++;
				$Clauses[$CurrentPos] = new Clause(ClauseType::Subject);
				$Clauses[$CurrentPos]->add($Classifications[$i]);
			}
		}
		else { 
			if ($ClauseMode == ClauseType::Subject) {
				//End Clause
				$ClauseMode = ClauseType::Unknown;
				$Clauses[$CurrentPos] = new Clause(ClauseType::Unknown);
				$Clauses[$CurrentPos]->add($Classifications[$i]);
			}
			elseif ($ClauseMode == ClauseType::Unknown) {//Continue Unknown Clause
				$Clauses[$CurrentPos]->add($Classifications[$i]);
			}
			else {//Start Unknown Clause
				$ClauseMode = ClauseType::Unknown;
				$CurrentPos++;
				$Clauses[$CurrentPos] = new Clause(ClauseType::Unknown);
				$Clauses[$CurrentPos]->add($Classifications[$i]);
			}
			
		}
	}
	//Identify Remaining Clauses, Starting with Longest Possible
	/* $RequestClauses */
	for ($i = 0; $i < count($Clauses); $i++) {
		if ($Clauses[$i]->getType() == ClauseType::Unknown) {
			$Remain = true;
			while ($Remain) {
				$Clauses[$i]->setType(IdentifyClause($Clauses[$i]->getWords()));
				$RequestClauses[$i] = $Clauses[$i];
				$Remain = false;
			}
		}
		else {
			$RequestClauses[$i] = $Clauses[$i];
		}
	}
	//var_dump($RequestClauses); //DEBUG
	//Identify Action
	$DesiredAction = ActionType::Unknown;
	$Len = count($RequestClauses);
	switch($Len) {
		case 2:
			if ($RequestClauses[0]->getType() == ClauseType::AskInfo) {
				if ($RequestClauses[1]->getType() == ClauseType::Subject) {
					//echo "IDENTIFIED. "; //DEBUG
					$DesiredAction = ActionType::Description;
				}
			}
		break;
	}
	switch($DesiredAction) {
		case ActionType::Command:
			echo $Answer = "I'm sorry, but I don't know how to do that.";
		break;
		case ActionType::Description:
			$Answer = RetrieveInfo($RequestClauses[1]->getSubject());
			//vardump($RequestClauses);
			if (strcasecmp($Answer, "") != 0) {
				echo $Answer;
				break;
			}
		default:
			echo $Answer = "I'm sorry, but I don't know what you mean.";
	}
	//echo "END!";
?>