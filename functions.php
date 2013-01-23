<?
/* Infinity Disassembler */

/* Helper functions */

//strip all non-printable characters
function str_sanitize($string) {
	return preg_replace_callback("/[^\x20-\x7E]/",create_function('$matches','return sprintf("\\x%02X",ord($matches[0]));'),$string);
}

// Print usage information
function usage($argc,$argv) {
	echo "Usage: ".$argv[0]." pass file\n";
	echo "Infinity Disassembler v. ".IDA_VERSION."\n";
	exit(1);
}

//Exit with (formatted) error message
function err_out($msg) {
	$args=func_get_args();
	$args[0]="Error: $msg\n";
	call_user_func_array("log_msg",$args);
	exit(1);
}

//Log a (formatted) message (print it to stdout)
function log_msg($msg) {
	//more than 1 arg? => printf-string
	if(func_num_args()>1) {
		$args=func_get_args();
		$args[0]=$msg;
		$msg=call_user_func_array("sprintf",$args);
	}
	echo $msg."\n";
}

//Turn multi-byte Little Endian integer into one huge "normal" integer
function le_to_int($i1, $i2, $i3 = 0, $i4 = 0) {
	$s = 0;
	$s += $i4 * (pow(2,24));
	$s += $i3 * (pow(2,16));
	$s += $i2 * (pow(2,8));
	$s += $i1;
	return $s;
}
//Turn multi-byte Big Endian integer into one huge "normal" integer
function be_to_int($i1, $i2, $i3 = 0, $i4 = 0) {
	$s = 0;
	$s += $i1 * (pow(2,24));
	$s += $i2 * (pow(2,16));
	$s += $i3 * (pow(2,8));
	$s += $i4;
	return $s;
}

function convert($size)
 {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
 }

 //insert element(s) $d at given position $i in array $a
 function array_insert($a,$i,$d) {
	$a1=array_slice($a,0,$i);
	$a2=array_slice($a,$i);
	return array_merge($a1,$d,$a2);
 }