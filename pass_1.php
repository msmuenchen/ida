<?
/* Infinity Disassembler */

/* Pass 1: Generate 1:1 ASM file using only "db" command */

$infile=$file;
$insize=filesize($infile);
$outfile=$workdir.$finfo["filename"].".asm";
log_msg("Output file %s",$outfile);

$in=fopen($infile,"rb");
if($in===false)
	err_out("Can't open input file");

$asm=ASM::createInFile($outfile);
$asm->setMeta($infile,md5_file($infile),filesize($infile));

//Don't use feof, it adds an extra nullbyte at the end?!
for($pos=0;$pos<$insize;$pos++) {
	$byte=ord(fgetc($in));
	//create DB instruction
	$inst=new Instr_generic_db($byte);
	$inst->setFpos($pos);
	$asm->appendInstruction($inst);
}

$asm->write();
unset($asm);
