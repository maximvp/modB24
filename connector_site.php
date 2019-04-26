<?php
if (!isset($_REQUEST)) {
    return;
}

//Получаем и декодируем уведомление
$dataJson = file_get_contents("php://input");

$data = json_decode($dataJson);

//секретный ключ из drupal
$confOptions = COption::GetOptionString("ithive.openlinesadditional",'field_site_token');

switch ($data->type) {
    case 'site_drupal':
        $user_id = $data->object->uid;
        file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/local/log/connector_site.txt", "AR_CHAT: " . var_export($data, true) . PHP_EOL, FILE_APPEND);

        if($data->secret != $confOptions){

            break;
        }
        require_once(COption::GetOptionString("ithive.openlinesadditional",'full_path')."/lib/controller_site.php");

        break;
    default:
        echo('ok');
}

