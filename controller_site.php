<?php

use \Bitrix\Main\Loader;

Loader::includeModule('ithive.openlinesadditional');
Loader::includeModule("imconnector");
Loader::includeModule("imopenlines");

if (!empty($user_id)) {
    // обработка входящих данных(на выходе подготовленные данные для сообщения)
    $chatParams = \ITHive\OpenLinesAdditional\Actions::dataPrepareSite($data);

    /*
     * проверка пользователя на принадлежность к админам группы
     */
//    $isAdminVk = \ITHive\OpenLinesAdditional\Vkattachment::groupManagementVk($user_id);
    /*
     * 'notMessage' - проверка что сообщение не отправлено с портала
     */

//    if (empty($isAdminVk)) {

        $idOpenLine = \ITHive\OpenLinesAdditional\Actions::getOlId();

        $chatRowAdded = \ITHive\OpenLinesAdditional\Actions::saveChatLink($chatParams['params']["userId"],
            $chatParams['params']["postId"], $chatParams['params']["chatId"], "ST", $chatParams['params']["commentId"]);

        $openLineMess = \Bitrix\ImConnector\CustomConnectors::sendMessages('ithive_connect', $idOpenLine,
            [$chatParams['messenge']]);

        if (\Bitrix\Main\Loader::includeModule('pull')) {
            \Bitrix\Pull\Event::send();
        }
//    }

}
