<?php

namespace app\ai\service;

/**
 * Centralized, typed access to the image-generation API configuration.
 * Sensitive values live in function_keys; behavior lives in a separate option.
 */
class ImageGenerationConfig
{
    const SETTINGS_OPTION = 'image_generation_settings';
    const KEYS_OPTION = 'function_keys';

    public static function defaults()
    {
        return [
            'classifier' => [
                'enabled' => 1,
                'api_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions',
                'model' => '',
                'timeout' => 30,
                'system_prompt' => '',
                'fail_closed' => 1,
            ],
            'watermark' => [
                'enabled' => 1,
                'text' => '鉴渊AI生成',
                'size_percent' => 6,
                'opacity_percent' => 90,
                'position' => 'bottom-right',
            ],
            'metadata' => [
                'enabled' => 1,
                'label' => '1',
                'content_producer' => '',
                'content_propagator' => '',
                'xmp_prefix' => 'TC260',
                'xmp_namespace' => 'http://www.tc260.org.cn/ns/AIGC/1.0/',
                'id_prefix' => 'wb',
            ],
            'storage' => [
                'sync_upyun' => 1,
                'upyun_domain' => '',
                'upyun_remote_dir' => '/aigc/',
                'local_save_rel_dir' => 'upload/aigc',
                'local_url_base' => '',
            ],
            'endpoint' => [
                'default_model' => 'jianyuan',
                'api_points' => 50,
                'rate_limit_per_minute' => 60,
                'poll_interval' => 3,
                'max_wait_time' => 90,
            ],
        ];
    }

    public static function settings()
    {
        $stored = cmf_get_option(self::SETTINGS_OPTION);
        return self::mergeRecursive(self::defaults(), is_array($stored) ? $stored : []);
    }

    public static function keys()
    {
        $keys = cmf_get_option(self::KEYS_OPTION);
        return is_array($keys) ? $keys : [];
    }

    public static function keyStatus(array $keys = null)
    {
        $keys = $keys === null ? self::keys() : $keys;
        $names = [
            'volc_access_key_id',
            'volc_secret_access_key',
            'image_classify_api_key',
            'image_test_api_keys',
            'upyun_service',
            'upyun_operator',
            'upyun_password',
            'wujie_appid',
            'wujie_signkey',
            'yidun_secret_id',
            'yidun_secret_key',
            'yidun_business_id',
        ];
        $status = [];
        foreach ($names as $name) {
            $value = isset($keys[$name]) ? $keys[$name] : null;
            $status[$name] = is_array($value) ? count(array_filter($value)) : ($value !== null && trim((string)$value) !== '');
        }
        $status['image_test_api_key_count'] = isset($keys['image_test_api_keys']) && is_array($keys['image_test_api_keys'])
            ? count(array_filter($keys['image_test_api_keys']))
            : 0;
        return $status;
    }

    public static function runtimeErrors(array $settings = null, array $keys = null)
    {
        $settings = $settings === null ? self::settings() : $settings;
        $keys = $keys === null ? self::keys() : $keys;
        $errors = [];

        if (empty($keys['image_test_api_keys']) || !is_array($keys['image_test_api_keys'])) {
            $errors[] = '测试图片接口 Bearer Key 未配置';
        }
        if (empty($keys['volc_access_key_id']) || empty($keys['volc_secret_access_key'])) {
            $errors[] = '火山引擎密钥未配置';
        }
        if (empty($settings['watermark']['enabled'])) {
            $errors[] = '显式 AI 水印必须开启';
        }
        if (empty($settings['metadata']['enabled'])) {
            $errors[] = 'AIGC metadata 必须开启';
        }
        if (!empty($settings['classifier']['enabled'])) {
            if (empty($keys['image_classify_api_key'])) {
                $errors[] = '类目判断 API Key 未配置';
            }
            if (empty($settings['classifier']['model']) || empty($settings['classifier']['system_prompt'])) {
                $errors[] = '类目判断模型或提示词未配置';
            }
        }
        if (!empty($settings['storage']['sync_upyun'])) {
            foreach (['upyun_service', 'upyun_operator', 'upyun_password'] as $name) {
                if (empty($keys[$name])) {
                    $errors[] = '又拍云凭据未配置完整';
                    break;
                }
            }
            if (empty($settings['storage']['upyun_domain'])) {
                $errors[] = '又拍云访问域名未配置';
            }
        }
        return array_values(array_unique($errors));
    }

    /**
     * Configuration required by create_for_test dry-run requests.
     *
     * Dry-run deliberately does not require image-generation or storage
     * credentials because it stops after the category decision.
     */
    public static function dryRunErrors(array $settings = null, array $keys = null)
    {
        $settings = $settings === null ? self::settings() : $settings;
        $keys = $keys === null ? self::keys() : $keys;
        $errors = [];

        if (!empty($settings['classifier']['enabled'])) {
            if (empty($keys['image_classify_api_key'])) {
                $errors[] = '类目判断 API Key 未配置';
            }
            if (empty($settings['classifier']['api_url'])
                || empty($settings['classifier']['model'])
                || empty($settings['classifier']['system_prompt'])) {
                $errors[] = '类目判断接口、模型或提示词未配置';
            }
        }

        return array_values(array_unique($errors));
    }

    private static function mergeRecursive(array $defaults, array $stored)
    {
        foreach ($stored as $key => $value) {
            if (isset($defaults[$key]) && is_array($defaults[$key]) && is_array($value)) {
                $defaults[$key] = self::mergeRecursive($defaults[$key], $value);
            } else {
                $defaults[$key] = $value;
            }
        }
        return $defaults;
    }
}
