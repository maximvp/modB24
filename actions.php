<?php

namespace ITHive\OpenLinesAdditional;

use \Bitrix\Main\Loader,
    \Bitrix\Main\Config\Option,
    \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Actions
{
    /*
     * установленные параметры  модуля ithive.openlinesadditional
    */
    public static function getParammModule()
    {
        $params = [
            'confirmation' => Option::get("ithive.openlinesadditional", 'field_confirmation'),
            'grup_id' => Option::get("ithive.openlinesadditional", 'field_id_grup'),
            'key_grup' => Option::get("ithive.openlinesadditional", 'field_key_grup'),
            'access_token' => Option::get("ithive.openlinesadditional", 'field_access_token'),
            'access_token_standalon' => Option::get("ithive.openlinesadditional", 'access_token_standalon'),
            'id_admin' => Option::get("ithive.openlinesadditional", 'field_id_admin'),
            'vk_api' => Option::get("ithive.openlinesadditional", 'field_vk_api'),
            'id_user_bot' => Option::get("ithive.openlinesadditional", 'id_user_bot'),
            'full_path' => Option::get("ithive.openlinesadditional", 'full_path'),
            'module_path' => Option::get("ithive.openlinesadditional", 'module_path'),
            'site_token' => Option::get("ithive.openlinesadditional", 'field_site_token'),
            'site_url' => Option::get("ithive.openlinesadditional", 'field_site_url'),
            'site_login' => Option::get("ithive.openlinesadditional", 'field_site_login'),
            'site_password' => Option::get("ithive.openlinesadditional", 'field_site_password'),
        ];
        return $params;
    }

    /*
     * тип систем для отправки сообщений
    */
    private static $systemTypes = array(
        "site" => "IZBENKA",
        "vk" => "VK"
    );
    /*
     * адреса систем для отправки сообщений
    */
    public static $systemUrls = array(
        "site" => "https://",
        "vk" => "https://api.vk.com/method/"
    );

    /*
     * данные для авторизации во внешних системах
    */
    private static $systemAuth = array(
        "site" => array(
            "login" => "",
            "pass" => "",
        ),
        "vk" => array(
            "login" => "",
            "pass" => "",
        ),
    );

    /*
     *  id открытой линии
    */
    private static $olId;
    /*
     *  xml_id открытой линии
    */
    private static $olXmlId = "writers";

    /*
     *  получение id чата
     * @param (int) $arFilter - фильтр для выбора чата
     * @param (int) $arSelect - список выбираемых полей
     * @return (int) $arChat - первый элемент, удовлетворяющий условиям фильтра
    */
    public static function getChatInfo(
        $arFilter = [],
        $arSelect = ["ID","CHAT_ID", "POST_ID","OL_CHAT_ID", 'USER_ID', "COMMENT_ID", "SYSTEM_TYPE"]
    ) {
        $arChat = array();
        $dbElements = OlTable::GetList([[], 'filter' => $arFilter, 'select' => $arSelect]);
        if ($arElement = $dbElements->Fetch()) {
            $arChat = $arElement;
        }
        return $arChat;
    }

    /*
     *  получение id чата
     * @param (int) $userId - ID пользователя
     * @param (int) $postId - ID поста
     * @param (int) $chatId - id чата
     * @param (string) $type - тип внешней системы
     * @param (int) $commentId - ID комментария
     * @return (int) $chatId - id записи
    */
    public static function saveChatLink($userId, $postId, $chatId, $type, $commentId = false)
    {
        $chatFilter = [
            //"USER_ID" => $userId,
            //"POST_ID" => $postId,
            "CHAT_ID" => $chatId,
            "SYSTEM_TYPE" => $type,
        ];
        $existChat = self::getChatInfo($chatFilter);

        if (!$existChat["ID"]) {
            $arPostFields = array(
                "USER_ID" => $userId,
                "POST_ID" => $postId,
                "CHAT_ID" => $chatId,
                "SYSTEM_TYPE" => $type,
                "CREATED" => new \Bitrix\Main\Type\DateTime()
            );
            if (intval($commentId)) {
                $arPostFields["COMMENT_ID"] = $commentId;
            }
            $tblRowRes = OlTable::Add($arPostFields);
        } else {
            $arPostFields = array(
                "USER_ID" => $userId,
                "POST_ID" => $postId,
                "CHAT_ID" => $chatId,
                "OL_CHAT_ID" => $existChat["OL_CHAT_ID"],
                "SYSTEM_TYPE" => $type,
                "CREATED" => new \Bitrix\Main\Type\DateTime()
            );
            if (intval($commentId)) {
                $arPostFields["COMMENT_ID"] = $commentId;
            }
            $tblRowRes = OlTable::Update($existChat["ID"], $arPostFields);
        }

        return $tblRowRes;
    }

    /*
     * установка ID открытой линии
     */
    private static function setOlId()
    {
        if (!Loader::includeModule('imopenlines')) {
            return false;
        }
        $olFilter = array("filter" => array("XML_ID" => self::$olXmlId));
        $arOl = \Bitrix\ImOpenLines\Config::getList($olFilter);

        if (is_array($arOl) && !empty($arOl)) {
            return self::$olId = current($arOl)["ID"];
        } else {
            return false;
        }
    }

    /*
     * получение ID открытой линии
     */
    public static function getOlId()
    {
        if (self::$olId) {
            return self::$olId;
        } else {
            return self::setOlId();
        }
    }

    public static function preparSendToSite($message){

        $comment = new \stdClass();  // Создаем объект comment
        $comment->nid = $message['post_id']; // nid ноды к которой нужно добавить комментарий
        $comment->pid = $message['reply_to_comment']; // id родительского комментария
        $comment->subject = $message['message'];

        $result = self::sendToSite($comment);
        return $result;
    }

    /*
     * Отправка сообщения на сайт
     * @param (string) $message - сообщение для отправки
     * @return (array) $sendStatus - результат отправки сообщения
    */
    public static function sendToSite($message)
    {
        $options = self::getParammModule();
        $url = $options['site_url'];
        $login = $options['site_login'];
        $pass = $options['site_password'];

        $data_string = json_encode ($message);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);

        curl_setopt($ch, CURLOPT_USERPWD, $login . ":" . $pass);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");

        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);

        curl_setopt ($ch, CURLOPT_HTTPHEADER, array (
                'Content-Type: application/json',
                'Content-Length:'. StrLen ($data_string))
        );

        $sendStatus = curl_exec($ch);

        curl_close($ch);

        $result = json_decode($sendStatus);

        return $result;
    }

    /*
 * подготовка входных данных из drupal для создания чата/отправки сообщения
 * @param (array) $data - данные из drupal
 * @return (array) $message_data - сформированный массив для создния чата/отправки сообщения
*/
    public static function dataPrepareSite($data)
    {

        $options = self::getParammModule();

        //UID - id пользователя создавшего комментарий
        $uid = $data->object->uid;
        //NID - ID ноды, на которую были оставлены комментарии CHAT_ID
        $nid = $data->object->nid;
        //CID - уникальный ID комментария POST_ID
        $cid = $data->object->cid;
        //PID - ID комментария на который был оставлен комментарий
        $replyToComment = $data->object->pid;
        //текс комментария
        $messenge = $data->object->subject;
        //дата время создания комментария
        $dateTimestamp = $data->object->created;

        $userPhoto = $data->object->photo;

        //$messengeFiles = self::prepareVKAttach($data);

        $userName = $data->object->first_name;
        $userLastName = $data->object->last_name;
        $userEmail = $data->object->email;

        $chatUrl = 'https://..../node/' . $nid . '#comment-' . $cid;
        $chatName = 'chat_' . $nid . '_vkusvill_site';

        $message_data = [
            //Массив описания пользователя
            'user' => array(
                'id' => $uid,
                'last_name' => $userLastName,
                'name' => $userName,
                'picture' =>
                    array(
                        'url' => $userPhoto
                    ),
                'url',
                'sex',
            ),
            //Массив описания сообщения
            'message' => array(
                'id' => $cid,
                'date' => $dateTimestamp,
                'text' => $messenge,
                //'files' => $messengeFiles['attach']
            ),
            //Массив описания чата
            'chat' => array(
                'id' => $nid,
                'name' => $chatName,
                'url' => $chatUrl,
            ),
        ];


        $params = array(
            "userId" => $uid,
            "postId" => $cid,
            "chatId" => $nid,
            "commentId" => $replyToComment,
        );

        //file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/local/log/userInfoReply.txt", "PARAMS: " . var_export([$params,$message_data], true) . PHP_EOL, FILE_APPEND);


        $result = ['messenge' => $message_data, 'params' => $params];
        return $result;
    }
    /*
     * подготовка входных данных из VK для создания чата/отправки сообщения
     * @param (array) $data - данные из VK
     * @return (array) $params - сформированный массив для создния чата/отправки сообщения
    */
    public static function dataPrepare($data)
    {

        $options = self::getParammModule();

        $messengeId = $data->object->id;
        $messenge = $data->object->text;
        $dateTimestamp = $data->object->date;
        $replyToComment = $data->object->reply_to_comment;
        $reply_to_user = $data->object->reply_to_user;
        $userId = ($data->object->user_id) ? $data->object->user_id : $data->object->from_id;
        $chatId = $data->object->post_id;

        $messengeFiles = self::prepareVKAttach($data);

        if (!empty($messengeFiles['video'])) {
            $messenge = $messenge . '[BR][B]' . Loc::getMessage("ATTACHE_VIDEO_URL") . '[/B] ' . $messengeFiles['video'];
        }


        $userInfo = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$userId}&fields=photo_100,domain&access_token=" . $options['access_token'] . "&v=" . $options['vk_api']));
        $userName = $userInfo->response[0]->first_name;
        $userLastName = $userInfo->response[0]->last_name;
        $userPhoto = $userInfo->response[0]->photo_100;
        $userDomen = 'https://vk.com/' . $userInfo->response[0]->domain;

        $chatUrl = 'https://vk.com/izbenka?w=wall' . $options['grup_id'] . '_' . $chatId;
        $chatName = 'chat_' . $userId . '_vkusvill';

        $message_data = [
            //Массив описания пользователя
            'user' => array(
                'id' => $userId,
                'last_name' => $userLastName,
                'name' => $userName,
                'picture' =>
                    array(
                        'url' => $userPhoto
                    ),
                'url' => $userDomen,
                'sex',
            ),
            //Массив описания сообщения
            'message' => array(
                'id' => $messengeId,
                'date' => $dateTimestamp,
                'text' => $messenge,
                'files' => $messengeFiles['attach']
            ),
            //Массив описания чата
            'chat' => array(
                'id' => $chatId,
                'name' => $chatName,
                'url' => $chatUrl,
            ),
        ];

        if (!empty($replyToComment)){

            if($reply_to_user == $options['grup_id']){
                $userInfoReply = json_decode(file_get_contents("https://api.vk.com/method/groups.getById?group_id={$reply_to_user}&fields=photo_100,site&access_token=" . $options['access_token'] . "&v=" . $options['vk_api']));
                $userNameReply = $userInfoReply->response[0]->name;
                $userDomenReply = 'https://vk.com/' . $userInfoReply->response[0]->site;
                $textReply = '[URL='.$userDomenReply.']'.$userNameReply.'[/URL]: ';
            }else{
                $userInfoReply = json_decode(file_get_contents("https://api.vk.com/method/users.get?user_ids={$reply_to_user}&fields=photo_100,domain&access_token=" . $options['access_token'] . "&v=" . $options['vk_api']));
                $userNameReply = $userInfoReply->response[0]->first_name;
                $userLastNameReply = $userInfoReply->response[0]->last_name;
                $userDomenReply = 'https://vk.com/' . $userInfoReply->response[0]->domain;
                $textReply = '[URL='.$userDomenReply.']'.$userNameReply.' '.$userLastNameReply.'[/URL]: ';
            }

            $userPhotoReply = $userInfoReply->response[0]->photo_100;
            $photoText = '[URL='.$userDomenReply.']'.$userPhotoReply.'[/URL]';
            $newTextArray = explode(',', $messenge);
            $newTextArray[0] = $textReply;
            $message_data['message']['text'] = implode($newTextArray);
        }

        $params = array(
            "userId" => $userId,
            "postId" => $messengeId,
            "chatId" => $chatId,
            "commentId" => $replyToComment,
        );

        //file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/local/log/userInfoReply.txt", "PARAMS: " . var_export([$userInfoReply,$message_data['message']['text']], true) . PHP_EOL, FILE_APPEND);

        $result = ['messenge' => $message_data, 'params' => $params];
        return $result;
    }

    /*
     * обработка вложений из ВК
     * @param (array) $data - данные из портала
     * @return (array) $attach - сформированный массив вложений
    */
    private static function prepareVKAttach($data)
    {
        $attach = array();
        $attachments = $data->object->attachments;
        if (is_array($attachments) && !empty($attachments)) {
            foreach ($attachments as $arAttach) {
                switch ($arAttach->type) {
                    case "photo":
                        $arPhotoTmb = $arAttach->photo->sizes;
                        if (is_array($arPhotoTmb) && !empty($arPhotoTmb)) {
                            $lastPhotoKey = count($arPhotoTmb) - 1;
                            $attach[] = array(
                                "url" => $arPhotoTmb[$lastPhotoKey]->url,
                            );
                        }
                        break;
                    case "doc":
                        $docUrl[] = $arAttach->doc->url;
                        $attach[] = array(
                            "url" => $arAttach->doc->url,
                        );
                        break;
                    case "video":
                        $arVideo = $arAttach->video;
                        $videoId = $arVideo->id;
                        $videoOwnerId = $arVideo->owner_id;
                        $paramVideo = [
                            'owner_id' => $videoOwnerId,
                            'videos' => $videoOwnerId . '_' . $videoId
                        ];
                        $resVideoGet = Vkattachment::videoGet($paramVideo);
                        $urlVideo = $resVideoGet['items'][0]['player'];
                        $attach[] = array(
                            "url" => $arVideo->photo_320,
                        );
                        $videoUrlText = '[URL=' . $urlVideo . ']' . $arVideo->title . '[/URL]';
                        break;
                    case "sticker":
                        $arSticker = $arAttach->sticker->images;
                        if (is_array($arSticker) && !empty($arSticker)) {
                            $attach[] = array(
                                "url" => $arSticker[0]->url,
                            );
                        }
                        break;
                }
            }
        }

        $result = ['attach' => $attach, 'video' => $videoUrlText];
        return $result;
    }

    /*
     * Уникальное имя файла
     */
    public static function unicetName($name)
    {

        $filename = uniqid() . '_' . $name;

        return $filename;
    }

    /*
     * получене данных об зарегестриванном файле
     * $objectId - id файла или объекта,
     * что указываем id файл или obj объект в  $where
     */
    public static function fileObject($objectId, $where = false)
    {

        $connection = \Bitrix\Main\Application::getConnection();
        $sqlHelper = $connection->getSqlHelper();
        switch ($where) {
            case 'id':
                $sql = "SELECT ID,FILE_ID,PARENT_ID FROM b_disk_object WHERE FILE_ID = '" . $sqlHelper->forSql($objectId,
                        50) . "' ";
                break;
            case 'obj':
                $sql = "SELECT ID,FILE_ID,PARENT_ID FROM b_disk_object WHERE ID = '" . $sqlHelper->forSql($objectId,
                        50) . "' ";
                break;
            default:
                $sql = "SELECT ID,FILE_ID,PARENT_ID FROM b_disk_object WHERE FILE_ID = '" . $sqlHelper->forSql($objectId,
                        50) . "' ";
                break;
        }

        $recordset = $connection->query($sql);
        while ($record = $recordset->fetch()) {
            $file['ID'] = $record['ID'];
            $file['FILE_ID'] = $record['FILE_ID'];
            $file['PARENT_ID'] = $record['PARENT_ID'];
            $file['ALL'] = $record;
        }

        if (!empty($file['ID'])) {
            $fileModel = \Bitrix\Disk\File::loadById($file['ID']);
            $file['PATCHE'] = \Bitrix\Disk\Driver::getInstance()->getUrlManager()->getPathFileDetail($fileModel);
            return $file;
        }

        if (empty($file['ID'])) {
            return false;
        }
    }
}
