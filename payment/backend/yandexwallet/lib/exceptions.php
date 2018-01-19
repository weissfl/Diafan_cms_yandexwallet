<?php 
namespace YandexMoney\Exceptions;

class APIException extends \Exception {

}
class FormatError extends APIException {
    public function __construct() {
        parent::__construct(
            'Формат HTTP-запроса не соответствует протоколу. Запрос невозможно разобрать, либо заголовок Authorization отсутствует, либо имеет некорректное значение.', 400
        );
    }
}

class ScopeError extends APIException {
    public function __construct() {
        parent::__construct(
            'Запрошена операция, на которую у токена нет прав.', 403
        );
    }
}

class TokenError extends APIException {
    public function __construct() {
        parent::__construct('Указан несуществующий, просроченный, или отозванный токен.', 401);
    }
}

class ServerError extends APIException {
    public function __construct($status_code) {
        parent::__construct('Ошибка сервера Яндекс.Денег', $status_code);
    }
}
