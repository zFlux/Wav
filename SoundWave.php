/*
Summary: SoundWave is a PHP object for viewing the content of a 16 bit mono uncompressed WAV format file.
Modification of the file object and support for variations of the wav format are TBD
Author: Daniel Christo
Example Usage:
$wav = new SoundWave("../test.wav");
$wav->printInfo();
*/

<?php

class SoundWave
{
 
  private $file_name;
  private $num_channels; 	/* 1 - mono, 2 - stereo */
  private $sample_frequency; 	/* 44100 hz, 8000 hz etc */
  private $bytes_per_sample; 	/* 1 byte, 2 byte etc */
  private $size_in_bytes; 	/* total byte size of the sound data portion of the file*/
  private $size_in_samples; 	/* total number of samples or points in the sound data */
  private $header_offset=44;    /* header offset for PCM in bytes */

  // Constructor takes a filename and stores that along with interesting wav file header information. 
  // A plain mono PCM wav file is all that this handles at the moment.
  public function __construct($wav_filename)
  {
     // read binary contents of wav_filename into a variable, if one is given
     if ($wav_filename != "") {
        $this->file_name = $wav_filename;
     	$file_handle = fopen($wav_filename, "r");
     	fread($file_handle,4); // ChunkID (RIFF)
     	fread($file_handle,4); // ChunkSize (36 + SubChunk2Size)
     	fread($file_handle,4); // Format (Wave)
     	fread($file_handle,4); // Subchunk1ID (fmt)
     	fread($file_handle,4); // Subchunk1Size should be 16 for PCM
     	fread($file_handle,2); // AudioFormat should be 1 for non-compressed
     	$this->num_channels = current(unpack("v", fread($file_handle,2)));
     	$this->sample_frequency = current(unpack("v", fread($file_handle,4)));
     	fread($file_handle,4); // Byte Rate
     	$this->bytes_per_sample = current(unpack("v", fread($file_handle,2))); // Size of one sample in bytes
     	fread($file_handle,2);  // Bits per sample; 
     	fread($file_handle,4); // Subchunk2ID (data)
     	$this->size_in_bytes = current(unpack("V", fread($file_handle,4))); // Total Size of the sound block in bytes
     	$this->size_in_samples = $this->size_in_bytes / $this->bytes_per_sample;  // Total number of samples in the sound block
 	fclose($file_handle);
     }
  }
  
  public function __destruct() {
  }
  
  // Reads sound data chunk and converts into an array of integers 
  public function getSoundArray() {
    	$file_handle = fopen($this->file_name, "r");
    	fread($file_handle,$this->header_offset); // read past the header info
 	return unpack("s" . $this->size_in_samples, fread($file_handle,$this->size_in_bytes)); // unpack sound data into integers and store in a JSON string
 	fclose($file_handle);
  }
 
  // Reads sound data chunk, converts it into an array of integers and encodes it as a JSON object  
  public function getJSON() {
 	return json_encode($this->getSoundArray()); // unpack sound data into integers and store in a JSON string
  }
   
  /* Input: i - The sample offset of a given sample
     Output: The integer value of the sample at i */
  public function getSampleI($i)
  {
      $file_handle = fopen($this->file_name, "r");
      fread($file_handle,$this->header_offset + (($i - 1) * $this->bytes_per_sample)); // read to the point just before the desired sample
      return current(unpack("s", fread($file_handle, $this->bytes_per_sample)));
      fclose($file_handle);
  }
  
  // Public access methods to the wav header information
  public function fileName() { return $this->file_name;}
  public function numChannels() { return $this->num_channels;}
  public function frequency() { return $this->sample_frequency;}
  public function bytesPerSample() { return $this->bytes_per_sample;}
  public function size() { return $this->size_in_samples;}
  
  // Prints wav file header info in a webpage format 
  public function printInfo() {
  	echo "<html><head><title>";
  	echo $this->fileName();
  	echo "</title></head><body><h1>";
  	echo $this->fileName();
  	echo "</h1><p>";
  	echo "Number of Channels: " . $this->numChannels() . "</p><p>";
  	echo "Frequency: " . $this->frequency() . "</p><p>";
  	echo "Bytes Per Sample: " . $this->bytesPerSample() . "</p><p>";
  	echo "Total Number of Samples: " . $this->size() . "</p><p>";
  	echo "</body></html>";
  }
  
}

?>