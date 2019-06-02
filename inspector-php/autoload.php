<?php

// We used to have an autoloader, but it caused problems in some
// environments. So now we manually load the entire library upfront.
//
// The file is still called Autoload so that existing integration
// instructions continue to work.
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Contracts/TransportInterface.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Exceptions/InspectorException.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Context/AbstractContext.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Context/Db.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Context/Http.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Context/Socket.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Context/Url.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Context/User.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Context/Request.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Context/Response.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Context/ErrorContext.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Context/SpanContext.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Context/TransactionContext.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/AbstractModel.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Error.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Span.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Models/Transaction.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Transport/AbstractApiTransport.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Transport/AsyncTransport.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'/Transport/CurlTransport.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Configuration.php';
require_once dirname(__FILE__).DIRECTORY_SEPARATOR.'Inspector.php';
