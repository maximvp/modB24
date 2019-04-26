<?php
if (!isset($_REQUEST)) {
    return;
}

//Получаем и декодируем уведомление
$dataJson = file_get_contents("php://input");

$data = json_decode($dataJson);

//Строка для подтверждения адреса сервера из настроек Callback API
$confirmation_token = COption::GetOptionString("ithive.openlinesadditional",'field_confirmation');

//секретный ключ из ВК
$confOptions = COption::GetOptionString("ithive.openlinesadditional",'field_key_grup');

switch ($data->type) {
    case 'confirmation':
        echo $confirmation_token;
        break;

    case 'wall_reply_new':
        $user_id = $data->object->from_id;
        
        if($data->secret != $confOptions){
            echo('ok');
            break;
        }

        require_once(COption::GetOptionString("ithive.openlinesadditional",'full_path')."/lib/controller.php");

        break;
    default:
        echo('ok');
}

