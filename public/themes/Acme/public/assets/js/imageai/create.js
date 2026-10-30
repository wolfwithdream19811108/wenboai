(function () {
    'use strict';

    const promptInput = document.getElementById('input_textarea');
    const examplePrompt = document.getElementById('example_prompt');
    const promptLength = document.getElementById('now_text_num');
    const clearButton = document.getElementById('detail_clean');
    const submitButton = document.getElementById('submit_btn');
    const statusBox = document.getElementById('generation_status');
    const imageContainer = document.getElementById('img_container');
    const endpoint = window.location.origin + '/api/ai/imagegenerate/create_for_web';
    const metadataEndpoint = window.location.origin + '/api/ai/imagegenerate/metadata';
    const userMenu = document.getElementById('imageai_user_menu');
    const userMenuToggle = document.getElementById('imageai_user_toggle');
    const navToggle = document.getElementById('imageai_nav_toggle');
    const navCollapse = document.getElementById('imageai_nav_collapse');

    let generating = false;

    function setStatus(message, type) {
        statusBox.textContent = message || '';
        statusBox.className = 'imageai-generation-status' + (type ? ' is-' + type : '');
    }

    function setGenerating(active) {
        generating = active;
        submitButton.disabled = active;
        submitButton.textContent = active ? '正在生成…' : '立即生成';
        if (active) {
            setStatus('', '');
            renderLoading();
        }
    }

    function showMessage(message) {
        setStatus(message, 'error');
        if (window.layer && typeof window.layer.msg === 'function') {
            window.layer.msg(message, {icon: 2, time: 3500});
        }
    }

    function renderEmpty() {
        imageContainer.innerHTML = '';
        const empty = document.createElement('div');
        empty.className = 'imageai-result-empty';
        empty.innerHTML = '<i class="fa fa-picture-o"></i><strong>生成结果将在这里展示</strong><span>成品会自动写入显式水印和 AIGC metadata</span>';
        imageContainer.appendChild(empty);
    }

    function renderLoading() {
        imageContainer.innerHTML = '';
        const loading = document.createElement('div');
        loading.className = 'imageai-result-loading';
        loading.setAttribute('role', 'status');
        loading.setAttribute('aria-label', '图片生成中');
        const spinner = document.createElement('div');
        spinner.className = 'imageai-loading-spinner';
        loading.appendChild(spinner);
        imageContainer.appendChild(loading);
    }

    function renderImage(url, requestId) {
        imageContainer.innerHTML = '';

        const card = document.createElement('div');
        card.className = 'imageai-result-card';

        const image = document.createElement('img');
        image.src = url;
        image.alt = 'AI 生成作品';
        image.className = 'imageai-result-image';

        const footer = document.createElement('div');
        footer.className = 'imageai-result-footer';

        const openLink = document.createElement('a');
        openLink.href = url;
        openLink.target = '_blank';
        openLink.rel = 'noopener noreferrer';
        openLink.className = 'imageai-result-link';
        openLink.textContent = '打开原图';

        const actions = document.createElement('div');
        actions.className = 'imageai-result-actions';
        const metadataButton = document.createElement('button');
        metadataButton.type = 'button';
        metadataButton.className = 'imageai-metadata-button';
        metadataButton.textContent = '查看 Metadata';
        metadataButton.disabled = !requestId;
        metadataButton.addEventListener('click', function () {
            showMetadata(requestId, metadataButton);
        });
        actions.appendChild(metadataButton);
        actions.appendChild(openLink);

        footer.appendChild(actions);
        card.appendChild(image);
        card.appendChild(footer);
        imageContainer.appendChild(card);
    }

    function addMetadataRow(container, label, value, stateClass) {
        const row = document.createElement('div');
        row.className = 'imageai-metadata-row';
        const name = document.createElement('dt');
        name.textContent = label;
        const content = document.createElement('dd');
        content.textContent = value === undefined || value === null || value === '' ? '—' : String(value);
        if (stateClass) {
            content.className = stateClass;
        }
        row.appendChild(name);
        row.appendChild(content);
        container.appendChild(row);
    }

    function showMetadataModal(metadata) {
        const previous = document.getElementById('imageai_metadata_modal');
        if (previous) {
            previous.remove();
        }
        const overlay = document.createElement('div');
        overlay.id = 'imageai_metadata_modal';
        overlay.className = 'imageai-metadata-overlay';

        const panel = document.createElement('section');
        panel.className = 'imageai-metadata-panel';
        panel.setAttribute('role', 'dialog');
        panel.setAttribute('aria-modal', 'true');
        panel.setAttribute('aria-label', '图片 Metadata');

        const header = document.createElement('div');
        header.className = 'imageai-metadata-header';
        const title = document.createElement('h3');
        title.textContent = '图片 Metadata';
        const close = document.createElement('button');
        close.type = 'button';
        close.className = 'imageai-metadata-close';
        close.setAttribute('aria-label', '关闭');
        close.textContent = '×';
        close.addEventListener('click', function () { overlay.remove(); });
        header.appendChild(title);
        header.appendChild(close);

        const list = document.createElement('dl');
        list.className = 'imageai-metadata-list';
        const aigc = metadata.aigc || {};
        addMetadataRow(list, '文件格式', metadata.format);
        addMetadataRow(list, '图片尺寸', metadata.width + ' × ' + metadata.height);
        addMetadataRow(list, '文件大小', Math.round((metadata.file_size || 0) / 1024) + ' KB');
        addMetadataRow(list, 'XMP 命名空间', metadata.xmp_namespace);
        addMetadataRow(list, 'Label', aigc.Label);
        addMetadataRow(list, 'ContentProducer', aigc.ContentProducer);
        addMetadataRow(list, 'ContentPropagator', aigc.ContentPropagator);
        addMetadataRow(list, 'ProduceID', aigc.ProduceID);
        addMetadataRow(list, 'PropagateID', aigc.PropagateID);
        addMetadataRow(
            list,
            '制作编号与传播编号一致',
            metadata.produce_id_matches_propagate_id ? '是' : '否',
            metadata.produce_id_matches_propagate_id ? 'is-valid' : 'is-invalid'
        );
        addMetadataRow(
            list,
            '图片编号与作品记录一致',
            metadata.produce_id_matches_record ? '是' : '否',
            metadata.produce_id_matches_record ? 'is-valid' : 'is-invalid'
        );

        panel.appendChild(header);
        panel.appendChild(list);
        overlay.appendChild(panel);
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) {
                overlay.remove();
            }
        });
        document.body.appendChild(overlay);
        close.focus();
    }

    async function showMetadata(requestId, button) {
        if (!requestId || button.disabled) {
            return;
        }
        const originalText = button.textContent;
        button.disabled = true;
        button.textContent = '读取中…';
        try {
            const response = await fetch(metadataEndpoint + '?request_id=' + encodeURIComponent(requestId), {
                credentials: 'same-origin'
            });
            const result = await response.json();
            if (result.code !== 1) {
                throw new Error(result.msg || 'Metadata 读取失败');
            }
            showMetadataModal(result.data || {});
        } catch (error) {
            showMessage(error.message || 'Metadata 读取失败');
        } finally {
            button.disabled = false;
            button.textContent = originalText;
        }
    }

    async function generateImage() {
        if (generating) {
            return;
        }

        const prompt = promptInput.value.trim();
        if (!prompt) {
            showMessage('请输入图片描述');
            promptInput.focus();
            return;
        }

        setGenerating(true);
        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    model: 'jianyuan',
                    stream: false,
                    dialogue: [{role: 'user', content: prompt}]
                })
            });
            const raw = await response.text();
            let result;
            try {
                result = JSON.parse(raw);
            } catch (error) {
                throw new Error('服务器返回了无法解析的结果');
            }

            if (result.status !== 'success' || !result.content) {
                throw new Error(result.reason || '图片生成失败');
            }

            renderImage(result.content, result.request_id || '');
            setStatus('', '');
        } catch (error) {
            renderEmpty();
            showMessage(error.message || '图片生成失败，请稍后重试');
        } finally {
            setGenerating(false);
        }
    }

    promptInput.addEventListener('input', function () {
        promptLength.textContent = promptInput.value.length;
        if (!promptInput.value) {
            examplePrompt.selectedIndex = 0;
            return;
        }
        if (examplePrompt.value !== promptInput.value) {
            examplePrompt.value = '__custom__';
        }
    });

    function applySelectedExample() {
        if (!examplePrompt.value || examplePrompt.value === '__custom__') {
            return;
        }
        promptInput.value = examplePrompt.value;
        promptLength.textContent = promptInput.value.length;
        promptInput.focus();
    }

    examplePrompt.addEventListener('change', applySelectedExample);

    promptInput.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            generateImage();
        }
    });

    clearButton.addEventListener('click', function () {
        promptInput.value = '';
        examplePrompt.selectedIndex = 0;
        promptLength.textContent = '0';
        promptInput.focus();
    });

    submitButton.addEventListener('click', generateImage);

    if (navToggle && navCollapse) {
        navToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            const open = navCollapse.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            navToggle.setAttribute('aria-label', open ? '收起导航' : '展开导航');
        });
        navCollapse.addEventListener('click', function (event) {
            if (event.target.closest('a') && !event.target.closest('.imageai-user-menu')) {
                navCollapse.classList.remove('is-open');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.setAttribute('aria-label', '展开导航');
            }
        });
        document.addEventListener('click', function (event) {
            if (!navCollapse.contains(event.target) && !navToggle.contains(event.target)) {
                navCollapse.classList.remove('is-open');
                navToggle.setAttribute('aria-expanded', 'false');
                navToggle.setAttribute('aria-label', '展开导航');
            }
        });
    }

    if (userMenu && userMenuToggle) {
        userMenuToggle.addEventListener('click', function (event) {
            event.preventDefault();
            const open = userMenu.classList.toggle('is-open');
            userMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        document.addEventListener('click', function (event) {
            if (!userMenu.contains(event.target)) {
                userMenu.classList.remove('is-open');
                userMenuToggle.setAttribute('aria-expanded', 'false');
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                userMenu.classList.remove('is-open');
                userMenuToggle.setAttribute('aria-expanded', 'false');
                if (navCollapse && navToggle) {
                    navCollapse.classList.remove('is-open');
                    navToggle.setAttribute('aria-expanded', 'false');
                    navToggle.setAttribute('aria-label', '展开导航');
                }
            }
        });
    }
    applySelectedExample();
    renderEmpty();
})();
