<?php
/*
Summary: Wav is a PHP library for viewing the content of or writing to an 8 or 16 bit mono uncompressed PCM WAV format file.
support for stereo is TBD
Author: Daniel Christo
Example Usage:
$wav = new SoundWave("test.wav");
$wav->printInfo();
*/
class Wav
{
 
  private $file_name;
  private $num_channels; 	/* 1 - mono, 2 - stereo */
  private $sample_frequency; 	/* 44100 hz, 8000 hz etc */
  private $bytes_per_sample; 	/* 1 byte, 2 byte etc */
  private $size_in_bytes; 	/* total byte size of the sound data portion of the file*/
  private $size_in_samples; 	/* total number of samples or points in the sound data */
  private $size_in_seconds;	/* total time of the sound data */
  private $header_offset=44;    /* header offset for PCM in bytes */
  private $pack_format;		/* format value for reading and writing sound binary data, could be unsigned char "C" (8 bit) or signed short "s" (16 bit) */

  // Constructor takes a filename and stores that along with interesting wav file header information. 
  // A plain mono PCM wav file is all that this handles at the moment.
  public function __construct($wav_filename)
  {
     $this->file_name = $wav_filename;
     // read binary contents of wav_filename into a variable, if one is given
     if ($wav_filename != "") {
     	$file_handle = fopen($wav_filename, "r");
     	// if the given filename can be opened then open it otherwise create it and write a default wav file
     	if ($file_handle) {
     		fread($file_handle,4); // ChunkID (RIFF)
     		fread($file_handle,4); // ChunkSize (36 + SubChunk2Size)
     		fread($file_handle,4); // Format (WAVE)
     		fread($file_handle,4); // Subchunk1ID (fmt)
     		fread($file_handle,4); // Subchunk1Size should be 16 for PCM
     		fread($file_handle,2); // AudioFormat should be 1 for non-compressed
     		$this->num_channels = current(unpack("v", fread($file_handle,2)));
     		$this->sample_frequency = current(unpack("V", fread($file_handle,4)));
     		fread($file_handle,4); // Byte Rate
     		$this->bytes_per_sample = current(unpack("v", fread($file_handle,2))); // Size of one sample in bytes
     		fread($file_handle,2); // Bits per sample; 
     		fread($file_handle,4); // Subchunk2ID (data)
     		$this->size_in_bytes = current(unpack("V", fread($file_handle,4))); // Total Size of the sound block in bytes
     		$this->size_in_samples = $this->size_in_bytes / $this->bytes_per_sample;  // Total number of samples in the sound block
     		$this->size_in_seconds = $this->size_in_samples / $this->sample_frequency;
 		fclose($file_handle);
 	
 		// change the pack format depending on 8 or 16 bit
 		if ($this->bytes_per_sample > 1) { $this->pack_format = "s";}
 		else { $this->pack_format = "C"; }
 		}
  	else {
	 	 	$this->num_channels = 1;
	 	 	$this->sample_frequency = 44100;
	 	 	$this->bytes_per_sample = 2;
	 	 	$this->size_in_seconds = 3;
	 	 	$this->size_in_samples = $this->size_in_seconds * $this->sample_frequency;
	 	 	$this->size_in_bytes = $this->size_in_samples * $this->bytes_per_sample;
	 	 	$file_handle = fopen($wav_filename, "w");
	 	 	$wav_header = 
	 	 		"RIFF" . 
	 	 		pack("V", $this->size_in_bytes + 36) . 
	 	 		"WAVE" . 
	 	 		"fmt " . 
	 	 		pack("V",16) . 
	 	 		pack("v", 1) . 
	 	 		pack("v", $this->num_channels) . 
	 	 		pack("V", $this->sample_frequency) . 
	 	 		pack("V", $this->sample_frequency * $this->bytes_per_sample * $this->num_channels) . 
	 	 		pack("v", $this->bytes_per_sample) . 
	 	 		pack("v",$this->bytes_per_sample * 8) . 
	 	 		"data" . 
	 	 		pack("V", $this->size_in_bytes);
	 	 	fwrite($file_handle, $wav_header);
	 	 	fclose($file_handle);
	 	 	$this->appendSineSoundWave($this->size_in_seconds, 0, 0);
     		} 
     		
     		
     		}
	}
	
  // Write a sound wave starting at a given $start_time in seconds that lasts a given $duration in seconds with a given $frequency in HZ and $amplitude between 1 and 0
  public function setSineSoundWave($start_time, $duration, $frequency, $amplitude) {
        	 $file_handle = fopen($this->file_name, "r+");
        	 $num_samples = floor($duration * $this->size_in_samples);
        	 fseek($file_handle, $this->header_offset + floor($start_time * $this->sample_frequency) * $this->bytes_per_sample);
	 	 for ($i = 0; $i <= $num_samples; $i++) {
			 $sound_data = $sound_data . pack("s", floor(((pow(2,15)-1) * sin( ($i/$this->sample_frequency)* 2 * pi() * $frequency )) * $amplitude ));
	 		}
	 	fwrite($file_handle, $sound_data);
	    	fclose($file_handle);	
  }
  
    // Add a sound wave to the existing sound wave starting at a given $start_time in seconds that lasts a given $duration in seconds with a given $frequency in HZ and $amplitude between 1 and 0
  public function addSineSoundWave($start_time, $duration, $frequency, $amplitude) {
        	 
        	 $file_handle = fopen($this->file_name, "r+");
        	 $num_samples = floor($duration * $this->size_in_samples );
        	 fseek($file_handle, $this->header_offset + floor($start_time * $this->sample_frequency) * $this->bytes_per_sample, SEEK_SET);
	 	 for ($i = 0; $i <= $num_samples; $i++) {
			 $y = floor((pow(2,15)-1) * sin( ($i/$this->sample_frequency)* 2 * pi() * $frequency ) * $amplitude);
			 $r = current(unpack($this->pack_format, fread($file_handle, $this->bytes_per_sample)));
	 	 	 $sound_data = $sound_data . pack("s", $r + $y );
	 		}
	 	fseek($file_handle, $this->header_offset + floor($start_time * $this->sample_frequency) * $this->bytes_per_sample, SEEK_SET);
	 	fwrite($file_handle, $sound_data);
	    	fclose($file_handle);	

  }
  
    // Append a sound wave starting at the end of the file that lasts a given $duration in seconds with a given $frequency in HZ and $amplitude between 1 and 0
  public function appendSineSoundWave($duration, $frequency, $amplitude) {
        	 $file_handle = fopen($this->file_name, "a+");
        	 $num_samples = floor($duration * $this->size_in_samples );
	 	 for ($i = 0; $i <= $num_samples; $i++) {
			 $y = floor((pow(2,15)-1) * sin( ($i/$this->sample_frequency)* 2 * pi() * $frequency ) * $amplitude);
	 	 	 fwrite($file_handle, pack("s", $y ) ); 
	 	 	
	 		}
	    	fclose($file_handle);	
  }	

  
  
  public function __destruct() {
  }
  
  // Reads sound data chunk and converts into an array of integers 
  public function getSoundArray() {
    	$file_handle = fopen($this->file_name, "r");
    	fread($file_handle,$this->header_offset); // read past the header info
 	return unpack($this->pack_format . $this->size_in_samples, fread($file_handle,$this->size_in_bytes)); // unpack sound data into integers 
 	fclose($file_handle);
  }
 
  // Reads sound data chunk, converts it into an array of integers and encodes it as a JSON object  
  public function getJSON() {
 	return json_encode($this->getSoundArray()); // unpack sound data into integers and store in a JSON string
  }
   
  /* Input: i - The sample offset of a given sample
     Output: The integer value of the sample at i */
  public function getSampleI($i) {
	$file_handle = fopen($this->file_name, "r");
	fseek($file_handle,$this->header_offset + (($i - 1) * $this->bytes_per_sample)); // put the pointer at the desired sample
	return current(unpack($this->pack_format, fread($file_handle, $this->bytes_per_sample)));
	fclose($file_handle);
  }
  
  public function setSampleI($i, $value) {
  	$file_handle = fopen($this->file_name, "r+");
  	fseek($file_handle,$this->header_offset + (($i-1) * $this->bytes_per_sample)); // put the pointer at the desired sample
  	fwrite($file_handle, pack($this->pack_format,$value) );
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
  
  // Returns an image resource with the object file's visual representation 
  // where the sound height is scaled to the height parameter but each sample is one pixel in width 
  public function getImage($width, $height) {
	$soundData = $this->getSoundArray(); // get the image data
	$my_img =  imagecreatetruecolor( $width, $height);
	$white = imagecolorallocate($my_img, 255, 255, 255);
	$black = imagecolorallocate($my_img, 0, 0, 0);
	imagefill($my_img, 0, 0, $white); // white out the background
	while($i <= $this->size()) {
		// Depending on whether it's unsigned 8 bit or signed 16 bit, offset the image to compensate for the negative values 
		if($this->bytes_per_sample > 1) {
			$x2 = $i;
 			$y2 = (($soundData[$i]/ (pow(2, $this->bytesPerSample()*8))) * $height) + ($height/2);
 		}
 		else {
    			$x2 = $i;
 			$y2 = (($soundData[$i]/ (pow(2, $this->bytesPerSample()*8))) * $height);
		}
		// draw a circle for each sample 2 pixels in diameter
		ImageArc($my_img, $x2, $y2, 1, 1, 0, 360, $black);
		// draw a line between this sample and the previous
		imageline($my_img , $x1 , $y1 , $x2 , $y2 , $black);
		$x1 = $x2;
		$y1 = $y2;
  		$i++;
	} 
	
	return $my_img;
  }
  
}

?>
