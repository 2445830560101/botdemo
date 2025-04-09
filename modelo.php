<?php
require __DIR__ . "/vendor/autoload.php";

use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WordTokenizer;
use Phpml\FeatureExtraction\TfIdfTransformer;
use Phpml\Classification\NaiveBayes;

function loadData($folder) {
    $samples = [];
    $labels = [];
    $files = ['saludos', 'pedidos', 'horarios', 'despedidas'];
    
    foreach ($files as $label) {
        $lines = file("$folder/{$label}.txt", FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            $samples[] = $line;
            $labels[] = $label;
        }
    }
    return [$samples, $labels];
}

[$samples, $labels] = loadData('train');

$vectorizer = new TokenCountVectorizer(new WordTokenizer());
$vectorizer->fit($samples);
$vectorizer->transform($samples);

$tfIdf = new TfIdfTransformer();
$tfIdf->fit($samples);
$tfIdf->transform($samples);

$classifier = new NaiveBayes();
$classifier->train($samples, $labels); 

file_put_contents('model.dat', serialize($classifier));
file_put_contents('vectorizer.dat', serialize($vectorizer));
file_put_contents('tfidf.dat', serialize($tfIdf));


echo "modelo entrenado y Guardado ".uniqid();
?>



