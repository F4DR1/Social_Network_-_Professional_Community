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
    async function sendGroupInfoData(action, value) {
        const url = '../actions/group/info_update.php';
        const data = {
            group_id: groupId,
            user_id: currentUserId,
            action: action,
            value: JSON.stringify(value)
        };

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams(data)
            });
            const result = await response.json();

            if (result.success) {
                updateGroupPath(value['linkname']);
                updateInfoMessage(action, 'success');
            } else {
                updateInfoMessage(action, 'error', result.message || 'Ошибка соединения');
                if (result.error) console.error(result.error);
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
        sendGroupInfoData('Base', baseInfo);
    });
});
