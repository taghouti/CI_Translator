<?php

$from_language = isset($argv[1]) ? $argv[1] : usage($argv, "missing from_language");
$to_language = isset($argv[2]) ? $argv[2] : usage($argv, "missing to_language");
$source = isset($argv[3]) ? $argv[3] : usage($argv, "missing source_file_path");
$output = isset($argv[4]) ? $argv[4] : usage($argv, "missing output_file_path");

if(!is_readable($source)) usage($argv, "reading source_file_path problem");
if(!is_writable(dirname($output))) usage($argv, "output folder not writable");

include $source;

if(!isset($lang) || !is_array($lang)) usage($argv, "parsing $lang array problem");

save(translate($lang, $from_language, $to_language), $output);

function translate($lines, $source, $target) {
    $parsed = array();
    $count = count($lines);
    $counter = 0;
    echo "\n\n[*] Translating $count words from ".strtoupper($source)." to ".strtoupper($target)."\n\n";
    foreach ($lines as $lang_key => $value) {
        $counter++;
        $word = str_replace("_", " ", $value);
        $translated_word = googleTranslate($source, $target, $word);
        $translated_word = ucfirst($translated_word);
        echo "[+] ($counter/$count) $word -> $translated_word\n";
        $key = strtolower($lang_key);
        $parsed[$key] = $translated_word;
    }
    return $parsed;
}

function save($array, $path) {
    $string = "<?php\n";
    foreach ($array as $key => $value) {
        $string .= '$lang["'.$key.'"] = "'.$value.'";'."\n";
    }
    file_put_contents($path, $string);
}

function googleTranslate($source, $target, $text)
{
    $response = requestTranslation($source, $target, $text);
    $translation = getSentencesFromJSON($response);
    return $translation;
}

function getSentencesFromJSON($json)
{
    $sentencesArray = json_decode($json, true);
    $sentences = "";
    foreach ($sentencesArray["sentences"] as $s) {
        $sentences .= isset($s["trans"]) ? $s["trans"] : '';
    }
    return $sentences;
} 

function requestTranslation($source, $target, $text)
{
    $url = "https://translate.google.com/translate_a/single?client=at&dt=t&dt=ld&dt=qca&dt=rm&dt=bd&dj=1&hl=es-ES&ie=UTF-8&oe=UTF-8&inputm=2&otf=2&iid=1dd3b944-fa62-4b55-b330-74909a99969e";
    $fields = array(
        'sl' => urlencode($source),
        'tl' => urlencode($target),
        'q' => urlencode($text)
        );

    if(strlen($fields['q'])>=5000)
        throw new \Exception("Maximum number of characters exceeded: 5000");
    $fields_string = "";
    foreach ($fields as $key => $value) {
        $fields_string .= $key . '=' . $value . '&';
    }
    rtrim($fields_string, '&');
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, count($fields));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_ENCODING, 'UTF-8');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'AndroidTranslate/5.3.0.RC02.130475354-53000263 5.1 phone TRANSLATE_OPM5_TEST_1');
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}

function usage($argv, $message = "") {
    echo "\n [!] Error : ".$message."\n\n";
    echo " Translate a codeigniter language file, the array in the file should have 'lang' as name.\n\n";
    echo " Arguments : \n\n";
    echo "\tfrom_language\t: language of the file (fr, en, ar ...)\n";
    echo "\tto_language\t: language of the translation (fr, en, ar ...)\n";
    echo "\tsource_file_path\t: codeigniter language file path\n";
    echo "\toutput_file_path\t: output file path\n\n";
    echo "\tUsage : ".$argv[0]." from_language to_language source_file_path output_file_path\n\n";
    echo "\tExample : ".$argv[0]." fr en /home/user/french/countries_lang.php /home/user/english/countries_lang.php\n\n";
    die();
} 