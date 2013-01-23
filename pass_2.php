<?php
/* Infinity Disassembler */

/* Pass 2: Determine file type, out of this build the section info, entry point and... well everything that is inside the file type */
$asmfile=$workdir.$finfo["filename"].".asm";
log_msg("Using ASM file %s",$asmfile);

$asm=ASM::createFromFile($asmfile);
$ftype=FileType::getFromASM($asm);
log_msg("File type of %s is %s",$asmfile,$ftype);
$asm->setFileType($ftype);
$asm->getFtypeInfo();
//$asm->write();
$asm->destroy();
unset($asm);