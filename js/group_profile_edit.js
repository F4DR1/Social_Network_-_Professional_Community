import { groupsEdit } from './api.js';

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли данные
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const groupPath = window.appData.groupPath;
    const groupId = window.appData.groupId;
    const currentUserId = window.appData.currentUserId;

    
    const baseInfoSuccess = document.getElementById('baseInfoSuccess');
    const baseInfoError = document.getElementById('baseInfoError');
    const errorMessage = document.getElementById('errorMessage');
    
    const groupLinkname = document.getElementById('groupLinkname');



    function updateGroupPath(path) {
        document.getElementById('groupPath').href = path;
    }

    function updateInfoMessage(action, message_status = null, message = null) {
        switch (action) {
            case 'Base':
                [baseInfoSuccess, baseInfoError]
                    .forEach(el => el?.classList.remove('active'));
                
                switch (message_status) {
                    case 'error':
                        errorMessage.textContent = message;
                        baseInfoError.classList.add('active');
                        break;
                    
                    case 'success':
                        baseInfoSuccess.classList.add('active');
                        break;
                
                    default:
                        break;
                }
                break;
        
            default:
                break;
        }
        
    }


    // Ставим ссылку назад при загрузке
    updateGroupPath(groupPath);

    // Скрываем панели сообщений
    updateInfoMessage('Base');



    // Отправить данные на скрипт
    async function editGroupData(action, value) {
        const data = {
            action: action,
            value: JSON.stringify(value)
        };

        try {
            const result = await groupsEdit(groupId, data);

            if (result.success) {
                updateGroupPath(result.linkname);
                updateInfoMessage(action, 'success');
            } else {
                updateInfoMessage(action, 'error', result.error || 'Ошибка соединения');
            }
            
        } catch (err) {
            updateInfoMessage(action, 'error', 'Ошибка сервера');
            console.error("Ошибка сервера: " . result.error || '');
        }
    }



    // Сохранение базовой информации группы
    document.getElementById("saveBaseInfo").addEventListener("click", (e) => {
        e.preventDefault();
        if (groupLinkname.value == '') groupLinkname.value = 'group' + groupId;
        const baseInfo = {
            name: document.getElementById('groupName').value,
            linkname: groupLinkname.value
        };
        editGroupData('base', baseInfo);
    });
});
