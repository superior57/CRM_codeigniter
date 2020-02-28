#!/usr/local/bin/php
<?php
define('TICKETS_PIPE', true);
error_reporting(1);

$environment = 'development';

$system_path = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'system';

$application_folder = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'application';

if (realpath($system_path) !== false) {
    $system_path = realpath($system_path) . '/';
}

$system_path = rtrim($system_path, '/') . '/';

define('BASEPATH', str_replace('\\', '/', $system_path));
define('APPPATH', $application_folder . '/');
define('EXT', '.php');
define('ENVIRONMENT', $environment ? $environment : 'development');
define('FCPATH', dirname(__FILE__) . '/');

if (file_exists(APPPATH . 'config/' . ENVIRONMENT . '/constants.php')) {
    require(APPPATH . 'config/' . ENVIRONMENT . '/constants.php');
} else {
    require(APPPATH . 'config/constants.php');
}

require(BASEPATH . 'core/Common.php');

if ($composer_autoload = config_item('composer_autoload')) {
    if ($composer_autoload === true) {
        file_exists(APPPATH . 'vendor/autoload.php')
        ? require_once(APPPATH . 'vendor/autoload.php')
        : log_message('error', '$config[\'composer_autoload\'] is set to TRUE but ' . APPPATH . 'vendor/autoload.php was not found.');
    } elseif (file_exists($composer_autoload)) {
        require_once($composer_autoload);
    } else {
        log_message('error', 'Could not find the specified $config[\'composer_autoload\'] path: ' . $composer_autoload);
    }
} else {
    // Fix for user who don't replace all the files during update
    if (file_exists(APPPATH . 'vendor/autoload.php')) {
        require_once(APPPATH . 'vendor/autoload.php');
    }
}

// Only uses the hooks() function.
require_once(APPPATH . 'config/hooks.php');
// Load the classes autoloader
require_once(APPPATH . 'hooks/App_Autoloader.php');
(new App_Autoloader)->register();

if (extension_loaded('mbstring')) {
    define('MB_ENABLED', true);
    // mbstring.internal_encoding is deprecated starting with PHP 5.6
    // and it's usage triggers E_DEPRECATED messages.
    @ini_set('mbstring.internal_encoding', $charset);
    // This is required for mb_convert_encoding() to strip invalid characters.
    // That's utilized by CI_Utf8, but it's also done for consistency with iconv.
    mb_substitute_character('none');
} else {
    define('MB_ENABLED', false);
}

    // There's an ICONV_IMPL constant, but the PHP manual says that using
    // iconv's predefined constants is "strongly discouraged".
    if (extension_loaded('iconv')) {
        define('ICONV_ENABLED', true);
        // iconv.internal_encoding is deprecated starting with PHP 5.6
        // and it's usage triggers E_DEPRECATED messages.
        @ini_set('iconv.internal_encoding', $charset);
    } else {
        define('ICONV_ENABLED', false);
    }

$GLOBALS['CFG'] = & load_class('Config', 'core');
$GLOBALS['UNI'] = & load_class('Utf8', 'core');

if (file_exists(BASEPATH . 'core/Security.php')) {
    $GLOBALS['SEC'] = & load_class('Security', 'core');
}

load_class('Router', 'core');
load_class('Input', 'core');
load_class('Lang', 'core');

require(BASEPATH . 'core/Controller.php');

function &get_instance()
{
    return CI_Controller::get_instance();
}

$class    = 'CI_Controller';
$instance = new $class();

$fd = fopen('php://stdin', 'r');

$input = '';
while (!feof($fd)) {
    $input .= fread($fd, 1024);
}
fclose($fd);
require_once(APPPATH . 'hooks/InitHook.php');
_app_init_load();

$instance->load->model('tickets_model');
$instance->load->helper('files');
$instance->load->helper('func');
$instance->load->helper('misc');
$instance->load->helper('database');

$mailParser = new \ZBateson\MailMimeParser\MailMimeParser();
$message    = $mailParser->parse($input);

$body = $message->getTextContent();

if (!$body) {
    $body = $message->getHtmlContent();
}

$body = trim($body);

if (!$body) {
    $body = 'No message found.';
}

$attachments     = [];
$mailAttachments = $message->getAllAttachmentParts();

foreach ($mailAttachments as $attachment) {
    $filename = $attachment->getHeaderParameter('Content-Disposition', 'filename');

    if (empty($filename)) {
        $filename = $attachment->getHeaderParameter('Content-Disposition', 'name');
    }

    if (!$filename) {
        continue;
    }

    $attachments[] = [
        'data'     => $attachment->getContent(),
        'filename' => sanitize_file_name($filename),
  ];
}

$subject   = $message->getHeaderValue('subject');
$fromemail = $message->getHeaderValue('from');
$fromname  = '';

if ($fromHeader = $message->getHeader('from')) {
    $fromname = $fromHeader->getPersonName();
}

if (empty($fromname)) {
    $fromname = $fromemail;
}

if ($reply_to = $message->getHeaderValue('reply-to')) {
    $fromemail = $reply_to;
}

$toemails = [];
foreach (['to', 'cc', 'bcc'] as $checkHeader) {
    $addreses = $message->getHeader($checkHeader);
    if ($addreses) {
        foreach ($addreses->getAddresses() as $addr) {
            $toemails[] = $addr->getEmail();
        }
    }
}

$to = implode(',', $toemails);

$body = handle_google_drive_links_in_text($body);

if (class_exists('EmailReplyParser\EmailReplyParser')
    && get_option('ticket_import_reply_only') === '1'
    && (mb_substr_count($subject, 'FWD:') == 0 && mb_substr_count($subject, 'FW:') == 0)) {
    $parsedBody = \EmailReplyParser\EmailReplyParser::parseReply($body);

    $parsedBody = trim($parsedBody);
    // For some emails this is causing an issue and not returning the email, instead is returning empty string
    // In this case, only use parsed email reply if not empty
    if (!empty($parsedBody)) {
        $body = $parsedBody;
    }
}

// Trim message
$body = trim($body);
$body = str_replace('&nbsp;', ' ', $body);
// Remove html tags - strips inline styles also
$body = trim(strip_html_tags($body, '<br/>, <br>, <a>'));
// Once again do security
$body = $instance->security->xss_clean($body);
// Remove duplicate new lines
$body = preg_replace("/[\r\n]+/", "\n", $body);
// new lines with <br />
$body = preg_replace('/\n(\s*\n)+/', '<br />', $body);
$body = preg_replace('/\n/', '<br />', $body);

$instance->tickets_model->insert_piped_ticket([
    'to'          => $to,
    'fromname'    => $fromname,
    'email'       => $fromemail,
    'subject'     => $subject,
    'body'        => $body,
    'attachments' => $attachments,
]);
