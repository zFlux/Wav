Wav
=========
Wav.php is a PHP library for reading and writing to an 8 or 16 bit mono uncompressed PCM WAV format file. 

$wav = new Wav("../audio/test.wav");
$wav->addSineSoundWave(0, 3, 440, .1);

This work is based on information on the wav specification I found here: 
https://ccrma.stanford.edu/courses/422/projects/WaveFormat/ 
and Python's equivalent wav utilities https://docs.python.org/2/library/wave.html
