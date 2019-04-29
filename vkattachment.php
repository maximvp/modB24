<?

namespace ITHive\OpenLinesAdditional;

use \Bitrix\Main\Config\Option;

/**
 * Class Vkattachment
 * @package ITHive\OpenLinesAdditional
 */
class Vkattachment extends Actions
{
    /**
     * отправка сообщения в чат ВК
     * @param array $params
     * @return mixed
     * @throws \Exception
     */
    public static function messagesSend($params = array())
    {
        return self::_call('wall.createComment', $params);
    }

    /**
     * получение параметров для загрузки фотографии
     * @param $peer_id
     * @return mixed
     * @throws \Exception
     */
    public static function photosGetWallUploadServer($group_id)
    {
        return self::_call('photos.getWallUploadServer', array(
            'group_id' => $group_id,
        ), 1);
    }

    /**
     * сохранение загруженной фотографии в ВК сервер
     * @param $photo
     * @param $server
     * @param $hash
     * @return mixed
     * @throws \Exception
     */
    public static function photosSaveWallPhoto($photo, $server, $hash)
    {
        return self::_call('photos.saveWallPhoto', array(
            'photo' => $photo,
            'server' => $server,
            'hash' => $hash,
            'group_id' => abs(Option::get("ithive.openlinesadditional", 'field_id_grup'))
        ), 1);
    }

    /**
     * получение параметров для сохранения документа в ВК
     * @param $peer_id - id администратора в ВК
     * @param $type - тип документа
     * @return mixed
     * @throws \Exception
     */
    public static function docsGetMessagesUploadServer($peer_id, $type)
    {
        return self::_call('docs.getMessagesUploadServer', array(
            'peer_id' => $peer_id,
            'type' => $type,
        ));
    }

    public static function videoSave($params)
    {
        return self::_call('video.save', $params, 1);
    }

    /**
     * сохранение документа в ВК
     * @param $file
     * @param $title
     * @return mixed
     * @throws \Exception
     */
    public static function docsSave($file, $title)
    {
        return self::_call('docs.save', array(
            'file' => $file,
            'title' => $title,
        ));
    }

    /**
     * запрос на сервер ВК
     * @param $method - метод
     * @param null $params -параметры
     * @return mixed
     * @throws \Exception
     */
    public static function _call($method, $params = null, $access_token_standalon = null)
    {
        $options = parent::getParammModule();
        if ($access_token_standalon == null) {
            $params['access_token'] = $options['access_token'];
        } else {
            $params['access_token'] = $options['access_token_standalon'];
        }

        $params['v'] = $options['vk_api'];
        $query = http_build_query($params);
        $url = parent::$systemUrls['vk'] . $method . '?' . $query;
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $json = curl_exec($curl);
        $error = curl_error($curl);
        if ($error) {
            throw new \Exception("Failed {$method} request");
        }
        curl_close($curl);
        $response = json_decode($json, true);

        if (!$response || !isset($response['response'])) {
            //file_put_contents($_SERVER['DOCUMENT_ROOT'] . "/local/log/error_call.txt",
                //'[' . date('Y-m-d H:i:s') . ']' . var_export($response, true) . PHP_EOL, FILE_APPEND);
            //throw new \Exception("Invalid response for {$method} request");
        }

        return $response['response'];
    }

    /**
     * Загрузка файла на сервер ВК
     * @param $url - ссылка на загрузку файла в ВК
     * @param $file_patch - ссылка на файл
     * @return mixed
     * @throws \Exception
     */
    public static function _upload($url, $file_patch)
    {
        if (!file_exists($file_patch)) {
            throw new \Exception('File not found: ' . $file_patch);
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, array('file' => new \CURLfile($file_patch)));
        $json = curl_exec($curl);
        $error = curl_error($curl);
        if ($error) {
            throw new \Exception("Failed {$url} request");
        }
        curl_close($curl);
        $response = json_decode($json, true);

        if (!$response) {
            throw new \Exception("Invalid response for {$url} request");
        }
        return $response;
    }

    /**
     * создание копии загруженного в чат Б24 файла и его отправка в ВК чат
     * @param $idObject id объекта файла
     * @return array
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function prepareAttache($idObject)
    {

        $user_id = Option::get("ithive.openlinesadditional", 'field_id_admin');

        $grup_id = abs(Option::get("ithive.openlinesadditional", 'field_id_grup'));

        $idFile = Actions::fileObject($idObject, 'obj');

        $fileArray = \CFile::GetFileArray($idFile['FILE_ID']);

        $newFilePath = $fileArray['SUBDIR'] . '/' . $fileArray['ORIGINAL_NAME'];

        $fileCopy = \CFile::CopyFile($idFile['FILE_ID'], true, $newFilePath);

        //новый файл с оригинальным именем
        $file_patch = $_SERVER['DOCUMENT_ROOT'] . '/upload/' . $newFilePath;

        $type = explode('/', $fileArray['CONTENT_TYPE']);

        switch ($type[0]) {
            case "image":

                $upload_server_response = self::photosGetWallUploadServer($grup_id);

                $upload_response = self::_upload($upload_server_response['upload_url'], $file_patch);

                $photo = $upload_response['photo'];
                $server = $upload_response['server'];
                $hash = $upload_response['hash'];

                $save_response = self::photosSaveWallPhoto($photo, $server, $hash);

                $photo = array_pop($save_response);

                $attachments = 'photo' . $photo['owner_id'] . '_' . $photo['id'];

                break;
            case 'application':

                $upload_server_response = Vkattachment::docsGetMessagesUploadServer($user_id, 'doc');

                $upload_response = Vkattachment::_upload($upload_server_response['upload_url'], $file_patch);

                $file = $upload_response['file'];

                $save_response = Vkattachment::docsSave($file, $fileArray['ORIGINAL_NAME']);

                $doc = array_pop($save_response);

                $attachments = 'doc' . $doc['owner_id'] . '_' . $doc['id'];
                break;
            case 'video':
                $paramVideo = [
                    'name' => $fileArray['ORIGINAL_NAME'],
                    'description' => $fileArray['ORIGINAL_NAME'],
                    'group_id' => $grup_id, // ID группы
                    'no_comments' => 0 // разрешаем комментирование
                ];

                $upload_server_response = Vkattachment::videoSave($paramVideo);

                $upload_response = Vkattachment::_upload($upload_server_response['upload_url'], $file_patch);

                $attachments = 'video' . $upload_server_response['owner_id'] . '_' . $upload_server_response['video_id'];

                break;

            case 'text':

                $upload_server_response = Vkattachment::docsGetMessagesUploadServer($user_id, 'doc');

                $upload_response = Vkattachment::_upload($upload_server_response['upload_url'], $file_patch);

                $file = $upload_response['file'];

                $save_response = Vkattachment::docsSave($file, $fileArray['ORIGINAL_NAME']);

                $doc = array_pop($save_response);

                $attachments = 'doc' . $doc['owner_id'] . '_' . $doc['id'];
                break;
        }

        if (!empty($attachments)) {
            return ['attachments' => $attachments, 'fileCopy' => $fileCopy];
        } else {
            throw new \Exception("Invalid attachments!");
        }
    }

    /**
     * Получение id руководителей группы ВК проверка на создание чата
     * @throws \Bitrix\Main\ArgumentNullException
     * @throws \Bitrix\Main\ArgumentOutOfRangeException
     */
    public static function groupManagementVk($id = null)
    {
        $params = [
            'group_id' => abs(Option::get("ithive.openlinesadditional", 'field_id_grup')),
            'filter' => 'managers',
            'sort' => 'id_asc'
        ];
        $users = self::_call('groups.getMembers', $params);
        foreach ($users['items'] as $key => $value) {
            $usersId[] = $value['id'];
        }

        if ($id) {
            if (in_array($id, $usersId)) {
                return $id;
            } else {
                return false;
            }
        }
        $users = implode(",", $usersId);

        if (!Option::get("ithive.openlinesadditional", 'management_grup_vk')) {
            Option::set("ithive.openlinesadditional", 'management_grup_vk', $users);
        }

        return $users;
    }

    /**
     * Получение ссылки на плеер с видио
     * @param $videos
     * @return mixed
     * @throws \Exception
     */
    public static function videoGet($videos)
    {
        return self::_call('video.get', array(
            'videos' => $videos
        ), 1);
    }
}
