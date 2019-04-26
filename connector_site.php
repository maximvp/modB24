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
        
        if($data->secret != $confOptions){

            break;
        }
        require_once(COption::GetOptionString("ithive.openlinesadditional",'full_path')."/lib/controller_site.php");

        break;
    default:
        echo('ok');
}

