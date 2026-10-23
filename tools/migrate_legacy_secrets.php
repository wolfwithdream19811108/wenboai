<?php

/**
 * Recover the service credentials that were embedded in the legacy project.
 *
 * This script never prints credential values. The generated services.php is
 * ignored by Git and is written with mode 0600 where the platform supports it.
 *
 * Usage:
 *   php tools/migrate_legacy_secrets.php --source=/path/to/old/site
 *   php tools/migrate_legacy_secrets.php --source=/path/to/old/site --force
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This script may only be run from the command line.\n");
    exit(1);
}

$options = getopt('', ['source:', 'output::', 'force']);
$sourceOption = isset($options['source']) ? $options['source'] : '';
$sourceRoot = $sourceOption !== '' ? realpath($sourceOption) : false;

if ($sourceRoot === false || !is_dir($sourceRoot)) {
    fwrite(STDERR, "A valid extracted legacy site directory is required via --source.\n");
    exit(1);
}

$projectRoot = realpath(dirname(__DIR__));
$output = isset($options['output']) && $options['output'] !== ''
    ? $options['output']
    : $projectRoot . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'conf' . DIRECTORY_SEPARATOR . 'services.php';

if (file_exists($output) && !isset($options['force'])) {
    fwrite(STDERR, "The output file already exists. Use --force to replace it.\n");
    exit(1);
}

function legacy_file($root, $relative)
{
    $path = $root . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relative);
    if (!is_file($path)) {
        return '';
    }

    $contents = file_get_contents($path);
    return $contents === false ? '' : $contents;
}

function decode_legacy_string($quote, $value)
{
    if ($quote === "'") {
        return str_replace(["\\\\", "\\'"], ["\\", "'"], $value);
    }

    return stripcslashes($value);
}

function first_legacy_value($contents, array $patterns)
{
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $contents, $matches)) {
            return decode_legacy_string($matches[1], $matches[2]);
        }
    }

    return '';
}

function named_assignment($contents, $name)
{
    $quotedName = preg_quote($name, '~');
    return first_legacy_value($contents, [
        '~(?m)^\\s*private\\s+\\$' . $quotedName . '\\s*=\\s*([\'\"])(.*?)\\1\\s*;~',
        '~(?m)^\\s*\\$' . $quotedName . '\\s*=\\s*([\'\"])(.*?)\\1\\s*;~',
    ]);
}

function class_constant($contents, $name)
{
    return first_legacy_value($contents, [
        '~(?m)^\\s*(?:public\\s+|private\\s+|protected\\s+)?const\\s+' . preg_quote($name, '~') . '\\s*=\\s*([\'\"])(.*?)\\1\\s*;~',
    ]);
}

function jianyuan_keys($contents)
{
    if (!preg_match_all('~\\$validApiKeys\\s*=\\s*\\[(.*?)\\]\\s*;~s', $contents, $blocks)) {
        return '';
    }

    $best = [];
    foreach ($blocks[1] as $block) {
        preg_match_all('~([\'\"])(.*?)\\1~s', $block, $matches, PREG_SET_ORDER);
        $keys = [];
        foreach ($matches as $match) {
            $value = trim(decode_legacy_string($match[1], $match[2]));
            if (strlen($value) >= 20) {
                $keys[] = $value;
            }
        }
        $keys = array_values(array_unique($keys));
        if (count($keys) > count($best)) {
            $best = $keys;
        }
    }

    return implode(',', $best);
}

function neo4j_driver($contents, $driver)
{
    $pattern = '~->withDriver\\(\\s*([\'\"])' . preg_quote($driver, '~') . '\\1\\s*,\\s*([\'\"])(.*?)\\2\\s*,.*?Authenticate::basic\\(\\s*([\'\"])(.*?)\\4\\s*,\\s*([\'\"])(.*?)\\6\\s*\\)\\s*\\)~s';
    if (!preg_match($pattern, $contents, $matches)) {
        return ['', '', ''];
    }

    return [
        decode_legacy_string($matches[2], $matches[3]),
        decode_legacy_string($matches[4], $matches[5]),
        decode_legacy_string($matches[6], $matches[7]),
    ];
}

$apiChat = legacy_file($sourceRoot, 'api/ai/controller/ChatController.php');
$appChat = legacy_file($sourceRoot, 'app/portal/controller/ChatController.php');
$aiImage = legacy_file($sourceRoot, 'api/ai/controller/AiimageController.php');
$imageSearch = legacy_file($sourceRoot, 'api/portal/controller/ImagesearchController.php');
$userController = legacy_file($sourceRoot, 'api/portal/controller/UserController.php');
$aiDetection = legacy_file($sourceRoot, 'app/ai/controller/AidetectionController.php');
$cert = legacy_file($sourceRoot, 'app/cert/controller/CertController.php');
$visual = legacy_file($sourceRoot, 'app/portal/controller/VisualController.php');
$aiImageJs = legacy_file($sourceRoot, 'public/themes/Acme/public/assets/js/aiimage.js');
$common = legacy_file($sourceRoot, 'simplewind/cmf/common.php');

$captchaId = first_legacy_value($common, [
    '~\\$config->accessKeyId\\s*=\\s*([\'\"])(.*?)\\1\\s*;~',
]);
$captchaSecret = first_legacy_value($common, [
    '~\\$config->accessKeySecret\\s*=\\s*([\'\"])(.*?)\\1\\s*;~',
]);
if ($captchaId === '') {
    $captchaId = first_legacy_value($userController, [
        '~\\$config->accessKeyId\\s*=\\s*([\'\"])(.*?)\\1\\s*;~',
    ]);
}
if ($captchaSecret === '') {
    $captchaSecret = first_legacy_value($userController, [
        '~\\$config->accessKeySecret\\s*=\\s*([\'\"])(.*?)\\1\\s*;~',
    ]);
}

$aiOrNotToken = '';
if (preg_match('~Authorization:\\s*Bearer\\s+([^\'\"\\r\\n]+)~', $aiDetection, $tokenMatch)) {
    $aiOrNotToken = trim($tokenMatch[1]);
}

list($neo4jUri, $neo4jUsername, $neo4jPassword) = neo4j_driver($visual, 'bolt');
list($neo4jHttpUri) = neo4j_driver($visual, 'https');

$values = [
    'DASHSCOPE_API_KEY'                => named_assignment($apiChat, 'apiKey'),
    'MOONSHOT_API_KEY'                 => first_legacy_value($aiImageJs, ['~\\b(?:var|let|const)\\s+kimiapiKey\\s*=\\s*([\'\"])(.*?)\\1\\s*;~']),
    'AIORNOT_API_TOKEN'                => $aiOrNotToken,
    'TENCENT_COS_SECRET_ID'            => class_constant($cert, 'SECRET_ID'),
    'TENCENT_COS_SECRET_KEY'           => class_constant($cert, 'SECRET_KEY'),
    'QINIU_ACCESS_KEY'                 => named_assignment($imageSearch, 'accessKey'),
    'QINIU_SECRET_KEY'                 => named_assignment($imageSearch, 'secretKey'),
    'ALIYUN_CAPTCHA_ACCESS_KEY_ID'     => $captchaId,
    'ALIYUN_CAPTCHA_ACCESS_KEY_SECRET' => $captchaSecret,
    'YIDUN_SECRET_KEY'                 => named_assignment($appChat, 'yidunKey'),
    'YIDUN_SECRET_ID'                  => named_assignment($appChat, 'secretId'),
    'YIDUN_AUDIT_BUSINESS_ID'          => named_assignment($appChat, 'yidunAuditId'),
    'YIDUN_NON_AUDIT_BUSINESS_ID'      => named_assignment($appChat, 'yidunNonAuditId'),
    'YIDUN_BUSINESS_ID'                => named_assignment($apiChat, 'yidunBusinessid'),
    'JIANYUAN_CLIENT_KEYS'              => jianyuan_keys($apiChat),
    'NEO4J_URI'                        => $neo4jUri,
    'NEO4J_HTTP_URI'                   => $neo4jHttpUri,
    'NEO4J_USERNAME'                   => $neo4jUsername,
    'NEO4J_PASSWORD'                   => $neo4jPassword,
    'NEO4J_INFO_USERNAME'              => named_assignment($visual, 'neouser'),
    'NEO4J_INFO_PASSWORD'              => named_assignment($visual, 'neopassword'),
    'NEO4J_ENCRYPTION_KEY'             => named_assignment($visual, 'secretKey'),
];

// The image controller carried the same DashScope key in some backups.
if ($values['DASHSCOPE_API_KEY'] === '') {
    $values['DASHSCOPE_API_KEY'] = named_assignment($aiImage, 'aliApiKey');
}

$outputDir = dirname($output);
if (!is_dir($outputDir) && !mkdir($outputDir, 0700, true)) {
    fwrite(STDERR, "Unable to create the output directory.\n");
    exit(1);
}

$payload = "<?php\n\n// Generated from the legacy site. Keep this file private and out of Git.\nreturn "
    . var_export($values, true)
    . ";\n";
$temporary = tempnam($outputDir, 'services-');
if ($temporary === false || file_put_contents($temporary, $payload, LOCK_EX) === false) {
    fwrite(STDERR, "Unable to write the temporary configuration file.\n");
    exit(1);
}
@chmod($temporary, 0600);

if (file_exists($output) && !unlink($output)) {
    @unlink($temporary);
    fwrite(STDERR, "Unable to replace the existing output file.\n");
    exit(1);
}
if (!rename($temporary, $output)) {
    @unlink($temporary);
    fwrite(STDERR, "Unable to place the generated configuration file.\n");
    exit(1);
}
@chmod($output, 0600);

$found = 0;
foreach ($values as $name => $value) {
    $present = is_string($value) && $value !== '';
    if ($present) {
        $found++;
    }
    echo $name . ': ' . ($present ? 'FOUND' : 'MISSING') . PHP_EOL;
}
echo 'Generated private configuration with ' . $found . '/' . count($values) . " values.\n";
echo 'Output: ' . $output . PHP_EOL;

exit($found === count($values) ? 0 : 2);
