@echo off
echo Assembling...
nasm -f bin -o input\ver.exe input\%1.asm
echo Done
md5sums input\%1.exe input\ver.exe
