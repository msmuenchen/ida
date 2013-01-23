<?
/* Infinity Disassembler */

/* Flat binary file type */
class FileType_FLAT extends FileType {
	//Always return true. FLAT is the generic fallback.
	public static function test($asm) {
		return true;
	}
	public function readSections() {
	}
	public function populateASM() {
	}
}
FileType::registerFileType("XXX_FLAT","FileType_FLAT");
