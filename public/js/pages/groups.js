import { groupsListGet, groupsCreate } from '../api.js';

document.addEventListener('DOMContentLoaded', function() {
    const currentUserId = window.appData.currentUserId;

    const myGroupsCategory = document.getElementById('myGroups');
    const allGroupsCategory = document.getElementById('allGroups');

    const message = document.getElementById('createGroupErrorMessage');


    // ID элементов модального окна создания группы
    const createGroupModalId = 'createGroupModal';
    const createGroupBtnId = 'createGroupButton';
    const cancelCreateGroupBtnId = 'cancelCreateGroupButton';





    async function createCreateGroupPanel(id, cancelBtnId, createBtnId) {
        const nameInputId = 'inputNameCreateGroup';
        await createModalHTML(id, `
            <div class="modal-title">
                <h2>Создание группы</h2>
            </div>
            <div class="modal-main create-group">
                <div class="input-field">
                    <input id="${nameInputId}" type="text" name="name" required autocomplete="name">
                    <label class="required">Название группы</label>
                </div>
                <p class="message" id="createGroupErrorMessage"></p>
            </div>
            <div class="modal-footer">
                <button class="create-group-cancel-btn" id="${cancelBtnId}">Отмена</button>
                <button class="create-group-accept-btn" id="${createBtnId}">Создать группу</button>
            </div>
        `);

        // Изменяем состояние кнопки в зависимости от заполненности поля
        const createBtn = document.getElementById(createGroupBtn)
        document.getElementById(nameInputId).addEventListener('input', function() {
            const hasText = this.value.trim().length > 0;
            createBtn.classList.toggle('active', hasText);
            createBtn.disabled = !hasText;
        });
    }





    // Обновить посты в категории на странице
    function updateCategoryGroups(groups, categoryType) {
        if (!groups || !categoryType || !myGroupsCategory || !allGroupsCategory) return;

        let category;
        let emptyText;
        switch (categoryType) {
            case 'my':
                category = myGroupsCategory;
                emptyText = 'У вас нет ни одной группы.';
                break;
                
            case 'all':
                category = allGroupsCategory;
                emptyText = 'Вы не состоите ни в одной группе.';
                break;
        
            default:
                return;
        }
        
        category.getElementsByClassName('title')[0].dataset.count = groups.length > 0 ? groups.length : 0;

        const list = category.getElementsByClassName('list')[0];
        list.innerHTML = '';
        if (groups.length > 0) {
            groups.forEach(group => {
                const groupHTML = createGroupHTML(group);
                list.insertAdjacentHTML('beforeend', groupHTML);
            });
        } else {
            list.innerHTML = `<p>${emptyText}</p>`;
        }
    }



    // Показать сообщение
    function setMessage(text) {
        message.textContent = text;
        message.classList.add('active');
    }

    // Скрыть сообщение
    function clearMessage() {
        message.textContent = '';
        message.classList.remove('active');
    }


    
    // Получить список групп
    async function getGroupsList() {
        try {
            const result = await groupsListGet(currentUserId);

            if (result.success) {
                updateCategoryGroups(result.groups['admin'], 'my');
                updateCategoryGroups(result.groups['all'], 'all');
                return;
            } else {
                console.error(result.error || "Ошибка получения списка групп");
            }

        } catch (err) {
            console.error("Ошибка сервера");
        }
        updateCategoryGroups([], 'my');
        updateCategoryGroups([], 'all');
    }

    // Создать группу
    async function createGroupAPI() {
        const data = {
            name: document.getElementById('groupName').value,
        };

        try {
            const result = await groupsCreate(data);

            if (result.success) {
                clearMessage();
                setTimeout(() => (window.location.href = `group${result.groupId}`), 2000);

            } else {
                setMessage(result.error || 'Ошибка создания группы');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }
    }



    // Создать панель создания группы
    createCreateGroupPanel(createGroupModalId, cancelCreateGroupBtnId, createGroupBtnId);

    // Получить список групп пользователя
    getGroupsList();


    



    // Открыть панель создания группы
    document.getElementById('openCreateGroupPanel').addEventListener('click', (e) => {
        e.preventDefault();
        showModal(createGroupModalId)
    });

    // Закрыть панель создания группы
    document.getElementById(cancelCreateGroupBtnId).addEventListener('click', (e) => {
        e.preventDefault();
        hideModal(createGroupModalId);
    });
    
    // Создание группы
    document.getElementById(createGroupBtnId).addEventListener('click', createGroupAPI);
});
