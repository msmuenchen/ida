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
	case 4:
		log_msg("Testing read->write identity");
		$asmfile=$workdir.$finfo["filename"].".asm";
		log_msg("Using ASM file %s",$asmfile);
		$asm=ASM::createFromFile($asmfile);
		$asm->write();
		$asm->destroy();
		unset($asm);
	break;
	case 5:
		log_msg("Compile");
		$origfile=$workdir.$finfo["filename"].".exe";
		$asmfile=$workdir.$finfo["filename"].".asm";
		$outfile=$workdir.$finfo["filename"]."_compiled.bin";
		$nasmfile=$workdir.$finfo["filename"]."_nasm.bin";
		log_msg("Using ASM file %s, building into IDA %s, NASM %s and original %s",$asmfile,$outfile,$nasmfile,$origfile);
		$asm=ASM::createFromFile($asmfile);
		$asm->compile($outfile);
		$asm->destroy();
		unset($asm);
		
		//MD5 verification
		$nasmcmd="nasm -f bin -o $nasmfile $asmfile";
		log_msg("Running %s",$nasmcmd);
		exec($nasmcmd);
		$md5_outfile=md5_file($outfile);
		$md5_nasmfile=md5_file($nasmfile);
		$md5_orig=md5_file($origfile);
		log_msg("MD5s:\nOrig\t\t%s\nIDA\t\t%s\nNASM\t\t%s",$md5_orig,$md5_outfile,$md5_nasmfile);
	break;
	default:
		err_out("Invalid pass %d",$pass);
}

log_msg("Peak mem usage: %s",convert(memory_get_peak_usage(true)));
log_msg("Current mem usage: %s",convert(memory_get_usage(true)));
?>