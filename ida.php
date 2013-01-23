<?php
/* Infinity Disassembler */

/* Main entry point */

define("IDA_VERSION","0.1");
error_reporting(E_ALL);
/* I doubt that this is sufficient, at least for the initial passes */
ini_set('memory_limit', '2G');

require("functions.php");

log_msg("Current mem usage: %s",convert(memory_get_usage(true)));

//check if we got enough arguments
if($argc<3)
	usage($argc,$argv);

$pass=(int)$argv[1];
$file=$argv[2];
if(!is_file($file))
	err_out("Invalid file %s",$file);


//Load IDA components
//Base classes
log_msg("Loading base classes");
$files=array();
foreach(glob("classes/*.php") as $classfile) {
	$pi=pathinfo($classfile);
	$files[]=$pi["filename"];
	require($classfile);
}
log_msg("Loaded %d classfiles: %s",sizeof($files),implode(" ",$files));

//File types
log_msg("Loading binary file types");
$files=array();
foreach(glob("ftypes/*.php") as $classfile) {
	$pi=pathinfo($classfile);
	$files[]=$pi["filename"];
	require($classfile);
}
log_msg("Loaded %d classfiles: %s",sizeof($files),implode(" ",$files));

//Instructions
log_msg("Loading instructions");
$files=array();
foreach(glob("arch/*/*.php") as $classfile) {
	$pi=pathinfo($classfile);
	$files[]=$pi["dirname"]."/".$pi["filename"];
	require($classfile);
}
log_msg("Loaded %d classfiles: %s",sizeof($files),implode(" ",$files));

$workdir=dirname($file);
//add slash or backslash at workdir end
if(substr($workdir,-1,1)!=DIRECTORY_SEPARATOR)
	$workdir.=DIRECTORY_SEPARATOR;

$finfo=pathinfo($file);
log_msg("Running pass %d on file %s, workdir is %s",$pass,$file,$workdir);

switch($pass) {
	case 0:
		log_msg("MD5 is %s",md5_file($file));
	break;
	case 1:
		log_msg("Generating DB file from source");
		require("pass_1.php");
	break;
	case 2:
		log_msg("Determining file type");
		require("pass_2.php");
	break;
	case 97:
		$a=array(0,1,2,3,4,5,6,7,8);
		$b=array(11,12,13,14);
		print_r(array_insert($a,4,$b));
	break;
	case 98:
		$w=DWORD::fromString("ABCD",1);
		log_msg("Return string LE: %s",str_sanitize($w->toRawString(0)));
		log_msg("Return string BE: %s",str_sanitize($w->toRawString(1)));
		log_msg("Return int LE: %10s %8x",$w->toInt(0),$w->toInt(0));
		log_msg("Return int BE: %10s %8x",$w->toInt(1),$w->toInt(1));
		log_msg("Return oct LE: %s",$w->toOct(0));
		log_msg("Return oct BE: %s",$w->toOct(1));
		log_msg("Return hex LE: %s",$w->toHex(0));
		log_msg("Return hex BE: %s",$w->toHex(1));
		log_msg("Return bin LE: %s",$w->toBin(0));
		log_msg("Return bin BE: %s",$w->toBin(1));
	break;
	case 99:
		log_msg("Testmode");
		$b=BYTE::fromString("s");
		log_msg($b->toString());
		log_msg($b->toOctal());
		log_msg($b->toHex());
		log_msg($b->toBin());
		$b=BYTE::fromOctal("77o");
		log_msg($b->toString());
		log_msg($b->toOctal());
		log_msg($b->toHex());
		log_msg($b->toBin());
		$b=BYTE::fromHex("62h");
		log_msg($b->toString());
		log_msg($b->toOctal());
		log_msg($b->toHex());
		log_msg($b->toBin());
		$b=BYTE::fromBin("0111001b");
		log_msg($b->toString());
		log_msg($b->toOctal());
		log_msg($b->toHex());
		log_msg($b->toBin());
	break;
	default:
		err_out("Invalid pass %d",$pass);
}

log_msg("Peak mem usage: %s",convert(memory_get_peak_usage(true)));
log_msg("Current mem usage: %s",convert(memory_get_usage(true)));
?>