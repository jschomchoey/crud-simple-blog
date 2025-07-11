<?php
define('_FOLDER_', '/CRUD_NEWS');
define('_ROOT_', $_SERVER['DOCUMENT_ROOT'] . _FOLDER_);
define("_HOME_", _ROOT_ . '/');
define("_INC_", _ROOT_ . '/inc');
define("_TPL_", _ROOT_ . '/templates');
define("_HTTP_", 'http://');
define("_HOMEURL_", _HTTP_ . $_SERVER['SERVER_NAME'] . _FOLDER_);
define("_SERVICEURL_", _HOMEURL_ . '/services');

define("_API_", _HOME_ . '/api');
define("_QUERY_", _HOME_ . '/query');
define("_DB_", _HOME_ . '/includes');
