<?php
/* Infinity Disassembler */

/* Instruction places a comment */
class Instr_generic_comment extends Instruction {
	//the message
	public $msg;
	
	function __construct($msg) {
		//more than 1 arg? => printf-string
		if(func_num_args()>1) {
			$args=func_get_args();
			$args[0]=$msg;
			$msg=call_user_func_array("sprintf",$args);
		}
		$this->msg=$msg;
	}
	public function getASM() {
		return ";".$this->msg;
	}
	public static function fromASM($line) {
		return new self(substr($line,1));
	}
	//Override Instruction's toString
	public function toString() {
		return ";".$this->msg;
	}
}
Instruction::registerInstruction(";","generic","Instr_generic_comment");