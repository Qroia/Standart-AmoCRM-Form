<?php
class AmoAPI
{
    private $client_id, $client_secret, $refresh_token, $subdomain, $access_token, $expires_in, $datetime;

    function __construct(string $client_id, string $client_secret, string $refresh_token, string $subdomain, string $access_token, string $expires_in, string $datetime)
    {
        $this->client_id     = $client_id;
        $this->client_secret = $client_secret;
        $this->refresh_token = $refresh_token;
        $this->subdomain     = $subdomain;
        $this->access_token  = $access_token;
        $this->expires_in    = $expires_in;
        $this->datetime      = $datetime;
    }

    function checkToken(): bool
    {
        $nowDate  = new DateTime();
        $interval = $this->datetime->diff($nowDate);
        if ( ((($interval->days * 24) + $interval->h) * 60) + $interval->i > $this->expires_in )
        {
            return true;
        } else {
            return false;
        }
    }

    function getAccessToken()
    {
        $link = "https://$this->subdomain.amocrm.ru/oauth2/access_token"; //Формируем URL для запроса

        /** Соберем данные для запроса */
        $data = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'authorization_code',
            'code' => $this->access_token,
            'redirect_uri' => 'https://google.ru/',
        );

        /**
         * Нам необходимо инициировать запрос к серверу.
         * Воспользуемся библиотекой cURL (поставляется в составе PHP).
         * Вы также можете использовать и кроссплатформенную программу cURL, если вы не программируете на PHP.
        */
        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER, array('content-Type: application/json'));
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int)$code;
        $errors = [
            400 => 'Bad request/1',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found/1',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try
        {
            /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error/1' . json_decode($out, true), $code);
            }
        }
        catch(Exception $e)
        {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        /**
         * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
         * нам придётся перевести ответ в формат, понятный PHP
         */
        $response = json_decode($out, true);

        print_r($response);

        $isDate = new DateTime();

        // Сохраняем локально
        $this->access_token  = $response['access_token']; //Access токен
        $this->refresh_token = $response['refresh_token']; //Refresh токен
        $this->expires_in    = $response['expires_in']; //Через сколько действие токена истекает
        $this->datetime      = $isDate;
    }

    function refreshToken()
    {
        // Формирование ссылки для авторизации
        $link = "https://$this->subdomain.amocrm.ru/oauth2/access_token";

        // Создание Data для передачи в запрос
        $data = array(
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'refresh_token',
            'refresh_token' => $this->refresh_token,
            'redirect_uri' => 'https://google.ru/'
        );

        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json']);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try
        {
            if ($code < 200 || $code > 204)
            {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch (Exception $err)
        {
            die("Error: $e->getMessage(), код ошибки: $e->getCode()");
        }

        $response = json_decode($out, true);

        $isDate = new DateTime();

        $this->access_token  = $response['access_token'];
        $this->refresh_token = $response['refresh_token'];
        $this->expires_in    = $response['expires_in'];
        $this->datetime      = $isDate;
    }

    function checkContact(string $phone): array
    {
        // Обновляем Токен в случае его истечения
        if ($this->checkToken())
        {
            $this->refreshToken();
        }

        $link = "https://$this->subdomain.amocrm.ru/api/v4/contacts";

        $headers = array(
            'Authorization: Bearer ' . $this->access_token
        );

        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized /1.3',
            403 => 'Forbidden',
            404 => 'Not found/2',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try
        {
            /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        } catch(Exception $e)
        {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        $response = json_decode($out, true);

        foreach ($response['_embedded']['contacts'] as $key => $value) {
            if ( $response['_embedded']['contacts'][$key]['custom_fields_values'][0]['field_code'] == 'PHONE')
            {
                if ($response['_embedded']['contacts'][$key]['custom_fields_values'][0]['values'][0]['value'] == $phone )
                {
                    return array('status' => true, 'id' => $response['_embedded']['contacts'][$key]['id']);
                }
            }
        }
        return array('status' => false);
    }

    function getAccount(): int
    {
        $link = "https://$this->subdomain.amocrm.ru/api/v4/users";

        $headers = array(
            'Authorization: Bearer ' . $this->access_token
        );

        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int) $code;

        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found/2',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try
        {
            /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        } catch(Exception $e)
        {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        $response = json_decode($out, true);
        print_r($response);
        foreach ($response['_embedded']['users'] as $key => $value) {
            if ( $response['_embedded']['users'][$key]['rights']['is_admin'] == true )
            {
                return (int) $response['_embedded']['users'][$key]['id'];
            }
        }

    }

    function getFields(string $entity_type): array
    {
        if ($this->checkToken())
        {
            $this->refreshToken();
        }

        switch ($entity_type) {
            case 'contacts':
                $link = "https://$this->subdomain.amocrm.ru/api/v4/contacts/custom_fields";
                break;

            case 'leads':
                $link = "https://$this->subdomain.amocrm.ru/api/v4/leads/custom_fields";
                break;
        }

        $headers = array(
            'Authorization: Bearer ' . $this->access_token
        );

        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int) $code;

        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found/2',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try
        {
            /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        } catch(Exception $e)
        {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        $response = json_decode($out, true);

        return (array) $response['_embedded']['custom_fields'];
    }

    function createContact(string $name, string $phone, string $email, string $city, int $adminId): int
    {
        $link = "https://$this->subdomain.amocrm.ru/api/v4/contacts";

        $customFields = $this->getFields('contacts');

        // Получение ID поля по его code
        function getFieldId(string $nameField, array $customField): int
        {
            switch ($nameField) {
                case 'PHONE':
                    foreach ($customField as $key => $value) {
                        if ($customField[$key]['name'] == 'Телефон')
                        {
                            return (int) $customField[$key]['id'];
                        }
                    }
                    break;
                case 'EMAIL':
                    foreach ($customField as $key => $value) {
                        if ($customField[$key]['name'] == 'Email')
                        {
                            return (int) $customField[$key]['id'];
                        }
                    }
                    break;
                case 'CITY':
                    foreach ($customField as $key => $value) {
                        if ($customField[$key]['name'] == 'Город')
                        {
                            return (int) $customField[$key]['id'];
                        }
                    }
                    break;
            }}

        // Генерируем JSON строку для отправки
        $queryArray = [array(
            'first_name' => $name,
            'responsible_user_id' => $adminId,
            'custom_fields_values' => [
                array(
                    'field_id' => getFieldId('PHONE', $customFields),
                    'values' => [
                        array(
                            'value' => $phone
                        )
                ]),
                array(
                    'field_id' => getFieldId('EMAIL', $customFields),
                    'values' => [
                        array(
                            'value' => $email
                        )
                ]),
                array(
                    'field_id' => getFieldId('CITY', $customFields),
                    'values' => [
                        array(
                            'value' => $city
                        )
                    ])
            ])
        ];

        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json', "Authorization: Bearer $this->access_token"]);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($queryArray));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int) $code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try {
            /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        /**
         * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
         * нам придётся перевести ответ в формат, понятный PHP
        */
        $response = json_decode($out, true);

        return (int) $response['_embedded']['contacts'][0]['id'];
    }

    // Создание примечания
    function createNotes(int $id, string $msg)
    {
        $link = "https://$this->subdomain.amocrm.ru/api/v4/leads/$id/notes";

        $data = [array(
            'note_type' => 'common',
            'params' => array(
                'text' => $msg
            )
        )];

        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json', "Authorization: Bearer $this->access_token"]);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try {
            /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        /**
         * Данные получаем в формате JSON, поэтому, для получения читаемых данных,
         * нам придётся перевести ответ в формат, понятный PHP
        */
        $response = json_decode($out, true);
    }

    function createDeal(string $service, int $price, string $msg, int $id, int $adminId)
    {
        $link = "https://$this->subdomain.amocrm.ru/api/v4/leads";

        $customFields = $this->getFields('leads');

        function getFieldsId(string $nameField, array $customField)
        {
            switch ($nameField) {
                case 'Услуга':
                    foreach ($customField as $key => $value) {
                        if ( $customField[$key]['name'] == 'Услуга' )
                        {
                            return (int) $customField[$key]['id'];
                        }
                    }
                    break;
                default:
                    break;
            }
        }


        $data = [array(
            'name' => 'Сделка',
            'price' => $price,
            'responsible_user_id' => $adminId,
            'custom_fields_values' => [array(
                'field_id'=> getFieldsId('Услуга', $customFields),
                'values' => [array(
                    'value' => $service
                )]
            )
            ],
            '_embedded' =>  array(
                'contacts' => [array(
                    'id' => $id
                )]
            )
        )];

        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json', "Authorization: Bearer $this->access_token"]);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        try {
            /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }

        $response = json_decode($out, true);

        $this->createNotes($response['_embedded']['leads'][0]['id'], $msg);

    }

    function createTask(bool $repeat, int $adminId, int $contactId)
    {
        $link = "https://$this->subdomain.amocrm.ru/api/v4/tasks";

        if ( $repeat )
        {
            $nowDate = new DateTime('+15 minutes');

            $data = [array(
                'text' => 'Повторная заявка',
                'entity_id' => $contactId,
                'entity_type' => 'contacts',
                'responsible_user_id' => $adminId,
                'complete_till' => $nowDate->getTimestamp()
            )];
        } else {
            $nowDate = new DateTime('+5 minutes');
            $data = [array(
                'text' => 'Обработать заявку',
                'entity_id' => $contactId,
                'entity_type' => 'contacts',
                'responsible_user_id' => $adminId,
                'complete_till' => $nowDate->getTimestamp()
            )];
        }

        $curl = curl_init(); //Сохраняем дескриптор сеанса cURL
        /** Устанавливаем необходимые опции для сеанса cURL  */
        curl_setopt($curl,CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl,CURLOPT_USERAGENT,'amoCRM-oAuth-client/1.0');
        curl_setopt($curl,CURLOPT_URL, $link);
        curl_setopt($curl,CURLOPT_HTTPHEADER,['Content-Type:application/json', "Authorization: Bearer $this->access_token"]);
        curl_setopt($curl,CURLOPT_HEADER, false);
        curl_setopt($curl,CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl,CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($curl,CURLOPT_SSL_VERIFYPEER, 1);
        curl_setopt($curl,CURLOPT_SSL_VERIFYHOST, 2);
        $out = curl_exec($curl); //Инициируем запрос к API и сохраняем ответ в переменную
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        /** Теперь мы можем обработать ответ, полученный от сервера. Это пример. Вы можете обработать данные своим способом. */
        $code = (int)$code;
        $errors = [
            400 => 'Bad request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not found',
            500 => 'Internal server error',
            502 => 'Bad gateway',
            503 => 'Service unavailable',
        ];

        print_r($out);

        try {
            /** Если код ответа не успешный - возвращаем сообщение об ошибке  */
            if ($code < 200 || $code > 204) {
                throw new Exception(isset($errors[$code]) ? $errors[$code] : 'Undefined error', $code);
            }
        }
        catch(Exception $e)
        {
            die('Ошибка: ' . $e->getMessage() . PHP_EOL . 'Код ошибки: ' . $e->getCode());
        }
    }
}
?>
