(function (window) {
    function post(url, data) {
        return fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: new URLSearchParams(data)
        }).then(function (response) {
            return response.json();
        });
    }

    function formValues(form) {
        var values = {};
        new FormData(form).forEach(function (value, key) {
            values[key] = value;
        });
        return values;
    }

    function handleResult(result) {
        if (result.code === 1) {
            location.href = result.url || '/imageai/account';
            return;
        }
        alert(result.msg || '操作失败');
    }

    function verifyCaptcha(captchaVerifyParam) {
        return fetch('/imageai/captcha/verify', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({captchaVerifyParam: captchaVerifyParam})
        }).then(function (response) {
            return response.json();
        });
    }

    function initCaptcha(button, element, businessAction) {
        initAliyunCaptcha({
            SceneId: '1doig5bf',
            prefix: '9pvdp9',
            mode: 'popup',
            element: element,
            button: button,
            captchaVerifyCallback: function (captchaVerifyParam, callback) {
                verifyCaptcha(captchaVerifyParam).then(function (result) {
                    callback({
                        captchaResult: result.code === 1
                            && result.data
                            && result.data.verifyResult === true
                    });
                }).catch(function () {
                    callback({captchaResult: false});
                });
            },
            onBizResultCallback: function (bizResult) {
                if (bizResult !== true) {
                    alert('滑块验证失败，请重新完成验证');
                    return;
                }
                businessAction().catch(function () {
                    alert('请求失败，请稍后重试');
                });
            },
            getInstance: function () {},
            slideStyle: {width: 360, height: 40},
            language: 'cn',
            region: 'cn'
        });
    }

    window.ImageAIAuth = {
        initSubmitCaptcha: function (button, element, form, url) {
            initCaptcha(button, element, function () {
                return post(url, formValues(document.querySelector(form)))
                    .then(function (result) {
                        handleResult(result);
                        return result;
                    });
            });
        },

        initSendCaptcha: function (button, element, mobile, type) {
            initCaptcha(button, element, function () {
                return post('/imageai/send-code', {
                    mobile: document.querySelector(mobile).value,
                    type: type
                }).then(function (result) {
                    if (result.code !== 1) {
                        alert(result.msg || '验证码发送失败');
                        return result;
                    }

                    var buttonElement = document.querySelector(button);
                    var seconds = 60;
                    buttonElement.disabled = true;
                    var timer = setInterval(function () {
                        buttonElement.textContent = seconds-- + '秒后重试';
                        if (seconds < 0) {
                            clearInterval(timer);
                            buttonElement.disabled = false;
                            buttonElement.textContent = '获取验证码';
                        }
                    }, 1000);
                    return result;
                });
            });
        },

        bindPlainForm: function (form, url) {
            document.querySelector(form).addEventListener('submit', function (event) {
                event.preventDefault();
                post(url, formValues(event.currentTarget))
                    .then(handleResult)
                    .catch(function () {
                        alert('请求失败，请稍后重试');
                    });
            });
        }
    };
})(window);
