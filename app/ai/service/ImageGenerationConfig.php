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
            'examples' => self::defaultExamples(),
            'classifier' => [
                'enabled' => 1,
                'api_url' => 'https://dashscope.aliyuncs.com/compatible-mode/v1/chat/completions',
                'model' => '',
                'timeout' => 30,
                'system_prompt' => '',
                'fail_closed' => 1,
            ],
            'response' => [
                'refusal_message' => '我是一个文博领域的图片生成垂域大模型，你的问题不在我的能力范围，请更换问题或者提示词再试一次。',
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
        $stored = is_array($stored) ? $stored : [];
        $settings = self::mergeRecursive(self::defaults(), $stored);
        if (array_key_exists('examples', $stored) && is_array($stored['examples'])) {
            $settings['examples'] = array_values($stored['examples']);
        }
        return $settings;
    }

    public static function defaultExamples()
    {
        return [
            ['title' => '清代漆器 · 剔犀盘', 'prompt' => '请生成一件清代时期的漆器剔犀盘，采用螺钿镶嵌、磨漆工艺制作，器物线条流畅，装饰有莲瓣纹纹样，器物在墓葬场景中，置于春节庙会中，器物造型独特、器物细腻温润。'],
            ['title' => '清代珠宝 · 碧玺朝珠', 'prompt' => '请生成一件清代碧玺朝珠，采用雕琢、穿系工艺制作，器物饰佛头纹，局部施粉红碧玺，置于清代后妃礼佛展柜中，器物为馆藏珍品。'],
            ['title' => '商周青铜器 · 青铜人物立像', 'prompt' => '请生成一件商周时期的青铜器青铜人物立像，采用镶嵌、鎏金工艺制作，器物轻盈灵动，装饰有云雷纹纹样，完整器型展示，置于博物馆展架上，器物经修复处理。'],
            ['title' => '南北朝雕塑 · 石狮', 'prompt' => '请生成一件南北朝时期的雕塑石狮，采用刻划工艺制作，器物线条刚劲，装饰有花卉纹纹样，器物在自然环境中，置于古代婚嫁场合，器物置于木质展台上。'],
            ['title' => '明中期漆器 · 螺钿漆盒', 'prompt' => '请生成一件明中期时期的漆器螺钿漆盒，采用戗金工艺制作，器物镂空雕花、构图严谨，装饰有海水纹纹样，器物在庙宇中，置于古代客栈中，器物保存完好。'],
            ['title' => '清末陶瓷 · 越窑青瓷盘', 'prompt' => '请生成一件清末时期的陶瓷越窑青瓷盘，采用还原焰烧制工艺制作，器物栩栩如生，装饰有山水纹纹样，器物在水边，置于古代酒肆中，器物包浆醇厚、器物为成对出现。'],
            ['title' => '汉代陶瓷 · 磁州窑瓷罐', 'prompt' => '请生成一件汉代时期的陶瓷磁州窑瓷罐，采用斗彩工艺制作，器物光泽柔和，装饰有海水纹、云纹纹样，局部放大，置于博物馆浅色背景，器物沁色自然。'],
            ['title' => '西周漆器 · 漆屏风', 'prompt' => '请生成一件西周时期的漆器漆屏风，采用漆塑工艺制作，器物形态逼真，装饰有缠枝花纹、缠枝梅花纹纹样，器物在雨景中，置于古代宫殿中，器物工艺精湛。'],
            ['title' => '魏晋南北朝玉器 · 玉杯', 'prompt' => '请生成一件魏晋南北朝时期的玉器玉杯，采用雕刻、透雕工艺制作，器物鎏金装饰，装饰有海水纹纹样，器物在烛光下，置于古代书房中，器物底部有款识。'],
            ['title' => '明晚期雕塑 · 石雕佛像', 'prompt' => '请生成一件明晚期时期的雕塑石雕佛像，采用瓷塑工艺制作，器物轻盈灵动，装饰有花鸟纹纹样，整体展示，置于博物馆展柜中，器物纹饰清晰可见。'],
        ];
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
            'tencent_faceid_secret_id',
            'tencent_faceid_secret_key',
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
