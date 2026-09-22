<?php

$mfn_valeur = isset($_GET['mfn']) ? intval($_GET['mfn']) : 1;

// Récupération sécurisée du MFN depuis la requête (par défaut 1 si non spécifié)
$mfn_valeur = 10;

$data = [
    'IsisScript' => 'c:/xampp/cgi-bin/read_mfn.xis',
    'database'   => 'ric_cm',
    'cipar'      => 'c:/xampp/htdocs/ABCD/www/bases/par/ric_cm.par',
    'Formato'    => 'c:/xampp/htdocs/ABCD/www/bases/ric_cm/pfts/fr/ric_cm',
    'path_db'    => 'c:/xampp/htdocs/ABCD/www/bases/',
    'mfn'        => $mfn_valeur // Correction : on passe un entier et non la chaîne 'mfn'
];

$ch = curl_init('http://localhost/cgi-bin/wxis.exe');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POSTFIELDS     => http_build_query($data)
]);

$response = curl_exec($ch);

if(curl_errno($ch)) {
    echo 'Erreur cURL : ' . curl_error($ch);
    exit;
}

curl_close($ch);

// Conversion ISO-8859-1 -> UTF-8
$response = mb_convert_encoding($response, "UTF-8", "ISO-8859-1");

echo $response;


?>