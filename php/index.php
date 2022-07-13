<?php
include "AmoAPI.php";

$email = $_POST['email'];
$name = $_POST['name'];
$phone = $_POST['phone'];
$city = $_POST['city'];
$service = $_POST['service'];
$msg = $_POST['msg'];

$iHaveAccessToken = true;

if ($iHaveAccessToken) {
    $api = new AmoAPI(
        '', // client_id
        '', // client_secret
   	'',
        '', // subdomain
        '',
        // access_token
        '86400', // expiren_in
        'NaN' // Date
    );
    $api->refreshToken();
} else {
    $api = new AmoAPI(
        '', // client_id
        '', // client_secret
        'NaN', // refresh_token
        '', // Subdomain
        '',
        // Auth_code
        'NaN', // expiren_in
        'NaN'
    );

    // СОздаём токен
    $api->getAccessToken();

    die();
}

// Возвращается ответ true/false
$checkContact = $api->checkContact($phone);

$adminId = $api->getAccount();

if ($checkContact['status'])
{
    // В случае совпадения поставить задачу с повторной заявкой и сроком - 15 минут
    $api->createTask(true, $adminId, $checkContact['id']);
} else
{
    // Создание контакта
    $contactId = $api->createContact($name, $phone, $email, $city, $adminId);
    // Создание сделки
    switch ($service) {
        case 'Диагностика':
            $price = 100;
            break;
        case 'Ремонт':
            $price = 500;
            break;
    }
    $api->createDeal($service, $price, $msg, $contactId, $adminId);
    $api->createTask(false, $adminId, $contactId);
}
?>
