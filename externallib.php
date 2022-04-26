<?php

use Firebase\JWT\JWT;

header('Access-Control-Allow-Origin: *');

defined('MOODLE_INTERNAL') || die();

require_once('../../config.php');
require_once($CFG->libdir . "/externallib.php");
require_once($CFG->dirroot . "/webservice/lib.php");
require_once($CFG->libdir . "/filelib.php");
require_once($CFG->dirroot . '/mod/rki/vendor/autoload.php');


class mod_rki_external extends external_api
{

    const HOST = 'https://api.ros-edu.ru';
    const X_API_KEY = 'QVAckUqIT49LRQQb';


    public static function search_books_parameters()
    {
        return new external_function_parameters(
            array(
                'searchParam' => new external_single_structure(
                    array('searchString' => new external_value(PARAM_TEXT, 'Поисковая строка'),)
                ),
                'page' => new external_value(PARAM_INT, 'смещение выдачи'),
                'limit' => new external_value(PARAM_INT, 'количетво элементов на страницу (10 по дефолту)'),
                'catId' => new external_value(PARAM_RAW, 'ИД категории поиска'),
                'levelId' => new external_value(PARAM_RAW, 'ИД уровня'),
            )
        );
    }

    public static function search_books($searchParam, $page, $limit, $catId, $levelId)
    {
        $params = array(
            "title" => $searchParam['searchString'],
            "level_id" => $levelId,
            "category_id" => $catId,
            "limit" => $limit,
            "offset" => ($page - 1) * $limit
        );
        $response = self::exec("/books/list", $params, true);
        return array('body' => $response);
    }

    public static function search_books_returns()
    {
        return new external_single_structure(
            array(
                'body' => new external_value(PARAM_RAW, 'Поисковая строка'),
            )
        );
    }

    public static function book_read_url($bookId)
    {
        global $USER;
        $params = array(
            "email" => $USER->email,
            "fullname" => "",
            "user_type" => 1,
            "publication_id" => $bookId,
            "open_method" => "iframe"
        );
        $data = mod_rki_external::exec("/security/generateAutoAuthUrl", $params);
        return array('body' => $data);
    }

    public static function book_read_url_parameters()
    {
        return new external_function_parameters(
            array(
                'bookId' => new external_value(PARAM_RAW, 'ИД книги')
            )
        );
    }

    public static function book_read_url_returns()
    {
        return new external_single_structure(
            array(
                'body' => new external_value(PARAM_RAW, 'ссылка'),
            )
        );
    }

    public static function category_tree_parameters()
    {
        return new external_function_parameters(
            array(
                'categoryId' => new external_single_structure(array(new external_value(PARAM_TEXT, 'ИД книги'))
                )
            )
        );
    }

    public static function category_tree($categoryId)
    {
        $params = array();
        $response = self::exec("/books/categories", $params);
        if ($response['success'] == false) {
            return array('body' => json_encode($response));
        }
        $response = json_decode($response, true);

        $allCategory = array("id" => 0,
            "pagetitle" => "Выбрать все",
            "parent_title" => "Выбрать все"
        );
        array_unshift($response['data'], $allCategory);
        return array('body' => json_encode($response));
    }

    public static function category_tree_returns()
    {
        return new external_single_structure(
            array(
                'body' => new external_value(PARAM_RAW, 'верхние категории'),
            )
        );
    }


    public static function level_tree_parameters()
    {
        return new external_function_parameters(
            array(
                'categoryId' => new external_single_structure(array(new external_value(PARAM_TEXT, ''))
                )
            )
        );
    }

    public static function level_tree($id)
    {
        $params = array();
        $response = self::exec("/books/levels", $params);
        $response = json_decode($response, true);

        $allLevels = array("id" => 0,
            "level" => "Выбрать все",
        );
        array_unshift($response['data'], $allLevels);

        return array('body' => json_encode($response));
    }

    public static function level_tree_returns()
    {
        return new external_single_structure(
            array(
                'body' => new external_value(PARAM_RAW, ''),
            )
        );
    }

    public static function exec($apiMethod, array $params, $isPostMethod = false)
    {
        $clientId = get_config('rki', 'user_id');
        $secretKey = get_config('rki', 'token');

        $json = array(
            "client_id" => $clientId,
            "time" => time(),
            "ip" => $_SERVER['SERVER_ADDR']
        );

        $token = JWT::encode($json, $secretKey);
        $params = array_merge(array("client_id" => $clientId), $params);

        if (!empty($params)) {
            $apiMethod = sprintf("%s?%s", $apiMethod, http_build_query($params, '', '&'));
        }

        $headers = array(
            'Authorization: Bearer ' . $token,
            'X-APIKey: ' . self::X_API_KEY,
            'Content-Type: application/x-www-form-urlencoded; charset=utf-8',
            'Accept: application/json'
        );

        $curl = curl_init();
        if ($isPostMethod) {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        } else {
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        }
        curl_setopt($curl, CURLOPT_URL, self::HOST . $apiMethod);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

        $curlResult = curl_exec($curl);

        if (curl_errno($curl)) {
            return [
//                'meta' => [
                    'success' => false,
                    'message' => 'Curl error ' . curl_errno($curl) . ': ' . curl_error($curl)
//                ]
            ];
        }

        return $curlResult;
    }
}
