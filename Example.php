<?php 
include 'SoundWave.php';
echo "<html>";
echo "<body>";
echo "<p>Creating test.wav</p>" ;
$wav = new SoundWave("../audio/test.wav");
echo "<p>Created</p>" ;
echo "<p>Adding sound wave with the following attributes:</p>" ;
echo "<p>Start second: 0 </p>";
echo "<p>Duration: 3 seconds </p>";
echo "<p>Frequency: 440 hz or A4</p>";
echo "<p>Volume: .1 or 10%</p>";
$wav->addSineSoundWave(0, 3, 440, .1);
echo "<p>Done</p>" ;
echo "<p>Play Sound:</p>" ;
echo "<audio controls>";
echo "<source src=\"../audio/test.wav\">";
echo "</audio>";
?>

