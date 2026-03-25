import { groupsEdit } from './api.js';

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли данные
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const groupPath = window.appData.groupPath;
    const groupId = window.appData.groupId;

    
    const baseInfo = document.getElementById('base-info');
    
    const groupLinkname = document.getElementById('group-linkname');



    function updateGroupPath(path) {
        document.getElementById('group-path').href = path;
    }

    function updateInfoMessage(category = null, message_status = null, message = null) {
        [baseInfo]
            .forEach(el => el?.classList.remove('active', 'success', 'error'));
        
        let infoPanel;
        switch (category) {
            case 'base':
                infoPanel = baseInfo;
                break;
                
            default:
                return;
        }

        const infoSVG = infoPanel.getElementsByClassName('info-icon-svg')[0];
        const infoTitle = infoPanel.getElementsByClassName('info-title')[0];
        const infoMessage = infoPanel.getElementsByClassName('info-message')[0];
        switch (message_status) {
            case 'success':
                infoPanel.classList.add('success');
                infoTitle.textContent = 'Изменения сохранены';
                infoMessage.textContent = 'Основная информация группы сохранена.';
                break;
        
            case 'error':
                infoPanel.classList.add('error');
                infoTitle.textContent = 'Ошибка при сохранении';
                infoMessage.textContent = message;
                break;
            
            default:
                return;
        }

        infoPanel.classList.add('active');
    }


    // Ставим ссылку назад при загрузке
    updateGroupPath(groupPath);

    // Скрываем панели сообщений
    updateInfoMessage();



    // Отправить данные на скрипт
    async function editGroupData(category, value) {
        const data = {
            groupId: groupId,
            category: category,
            value: JSON.stringify(value)
        };

        try {
            const result = await groupsEdit(data);

            if (result.success) {
                updateGroupPath(result.linkname);
                updateInfoMessage(category, 'success');
            } else {
                updateInfoMessage(category, 'error', result.error || 'Ошибка соединения');
            }
            
        } catch (err) {
            updateInfoMessage(category, 'error', 'Ошибка сервера');
            console.log("Ошибка сервера: " . result.error || '');
        }
    }



    // Сохранение базовой информации группы
    document.getElementById("save-base-info").addEventListener("click", (e) => {
        e.preventDefault();
        if (groupLinkname.value == '') groupLinkname.value = 'group' + groupId;
        const baseInfo = {
            name: document.getElementById('group-name').value,
            linkname: groupLinkname.value
        };
        editGroupData('base', baseInfo);
    });
});
