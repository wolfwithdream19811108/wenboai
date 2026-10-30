<?php

namespace app\imageai\service;

class AgreementService
{
    const OPTION_NAME = 'imageai_agreements';

    public static function all()
    {
        $agreements = cmf_get_option(self::OPTION_NAME);
        $defaults = self::defaults();

        foreach ($defaults as $type => $default) {
            if (empty($agreements[$type]) || !is_array($agreements[$type])) {
                $agreements[$type] = $default;
                continue;
            }
            $agreements[$type] = array_merge($default, $agreements[$type]);
        }

        return $agreements;
    }

    public static function get($type)
    {
        $agreements = self::all();
        return isset($agreements[$type]) ? $agreements[$type] : [];
    }

    public static function publish($type, array $agreement)
    {
        if (!in_array($type, ['service', 'privacy'], true)) {
            return false;
        }

        $agreements = self::all();
        $agreements[$type] = [
            'title'        => trim((string)$agreement['title']),
            'version'      => trim((string)$agreement['version']),
            'content'      => trim((string)$agreement['content']),
            'published_at' => time(),
        ];

        return cmf_set_option(self::OPTION_NAME, $agreements, true);
    }

    private static function defaults()
    {
        return [
            'service' => [
                'title'        => '鉴渊 ImageAI 服务协议',
                'version'      => '2026-10-28',
                'published_at' => 1793116800,
                'content'      => '<h2>一、服务内容</h2><p>鉴渊 ImageAI 向注册用户提供图片生成 API、密钥管理、请求记录和作品管理服务。API 专用账号不得使用网页生图入口。</p><h2>二、账号与密钥</h2><p>用户应使用本人手机号码注册并妥善保管密码及 API Key。因主动泄露密钥造成的调用由账号持有人承担。</p><h2>三、使用规范</h2><p>不得利用服务生成或传播违法、侵权、虚假、危害国家安全或违反公序良俗的内容。平台可对输入进行内容类目判断并拒绝不符合服务范围的请求。</p><h2>四、计费与记录</h2><p>图片生成按后台公布的积分规则计费。系统会记录请求结果和生成作品；未成功生成的请求按规则退还预扣积分。</p><h2>五、服务变更</h2><p>平台可能因维护、安全或监管要求调整服务。重大变更将通过合理方式告知用户。</p>',
            ],
            'privacy' => [
                'title'        => '鉴渊 ImageAI 隐私政策',
                'version'      => '2026-10-30',
                'published_at' => 1793289600,
                'content'      => '<h2>一、收集的信息</h2><p>注册时收集本人手机号码、密码摘要、短信验证码验证结果、协议版本、同意时间和 IP 地址；调用 API 时记录密钥标识、请求 ID、调用时间、结果、消耗积分及生成作品。</p><h2>二、实名认证与年龄判断</h2><p>为确认用户身份并落实未成年人保护要求，用户主动提交的姓名和身份证号码会直接传输给腾讯云进行身份信息一致性核验。本系统不长期保存完整姓名或身份证号码，仅保存认证状态、身份证带密钥摘要、证件尾号、达到年龄要求的日期和腾讯云请求编号。</p><h2>三、使用目的</h2><p>上述信息用于账号登录、安全验证、实名认证、年龄判断、接口鉴权、计费、作品管理、故障排查及履行合规义务。</p><h2>四、内容数据</h2><p>为降低不必要的信息留存，请求日志仅保存提示词摘要；生成成功的提示词和图片会进入用户作品记录，用于向用户展示和后台管理。</p><h2>五、保护与期限</h2><p>API Key 仅保存不可逆哈希。我们采取访问控制和必要的技术措施保护数据，并在实现服务及合规目的所需期限内保存。</p><h2>六、用户权利</h2><p>用户可以在控制台撤销 API Key、查看调用和作品记录，并可联系平台申请处理账号和个人信息。</p>',
            ],
        ];
    }
}
