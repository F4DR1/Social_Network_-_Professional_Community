import { groupsListGet, groupsCreate } from '../api.js';

document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, есть ли данные
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    // Базовые данные
    const currentUserId = window.appData.currentUserId;


    // Категории контактов
    const myGroupsCategory = document.getElementById('myGroups');
    const allGroupsCategory = document.getElementById('allGroups');



    

    // Показать/скрыть сообщение
    function toggleMessage(message, text = '') {
        message.textContent = text;
        message.classList.toggle('active', text !== '')
    }
    
    // Включить/отключить кнопку
    function updateButtonActive(btn, isActive) {
        btn.classList.toggle('active', isActive);
        btn.disabled = !isActive;
    }





    // =============== ВЫВОД СПИСКОВ ===============
    // Обновить группы в категории на странице
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




    
    // =============== API ===============
    // Получить список групп
    async function getGroupsListAPI() {
        let myGroups = [];
        let allGroups = [];
        try {
            const result = await groupsListGet(currentUserId);

            if (result.success) {
                myGroups = result.groups['admin'];
                allGroups = result.groups['all'];
            } else {
                console.error(result.error || 'Ошибка получения списка групп');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }
        updateCategoryGroups(myGroups, 'my');
        updateCategoryGroups(allGroups, 'all');
    }

    // Создать группу
    async function createGroupAPI(message, data) {
        try {
            const result = await groupsCreate(data);

            if (result.success) {
                toggleMessage(message);
                setTimeout(() => (window.location.href = `group${result.groupId}`), 2000);

            } else {
                toggleMessage(message, result.error || 'Ошибка создания группы');
            }

        } catch (err) {
            toggleMessage('Ошибка сервера');
        }
    }





    // =============== МОДАЛЬНОЕ ОКНО ===============
    // Модальное окно создания группы
    async function createCreateGroupPanel(id) {
        const modal = await createModalHTML(id, `
            <div class="modal-title">
                <h2>Создание группы</h2>
            </div>
            <div class="modal-main create-group">
            
                <!-- Имя группы -->
                <div class="input-field">
                    <span class="required">Название:</span>
                    <div class="field">
                        <input class="group-name" type="text" name="name" required autocomplete="name">
                    </div>
                </div>
                <p class="message create-group-message" id="createGroupErrorMessage"></p>

            </div>
            <div class="modal-footer">
                <button class="create-group-cancel-btn">Отмена</button>
                <button class="create-group-accept-btn">Создать группу</button>
            </div>
        `);

        

        // Элементы модального окна
        const message = modal.querySelector('.create-group-message');
        const groupName = modal.querySelector('.group-name');



        // ===== КНОПКИ И ВВОД =====
        const acceptBtn = modal.querySelector('.create-group-accept-btn');
        const cancelBtn = modal.querySelector('.create-group-cancel-btn');
        

        // Изменяем состояние кнопки в зависимости от заполненности поля
        await updateButtonActive(acceptBtn, false);
        groupName.addEventListener('input', () => {
            const hasText = groupName.value.trim().length > 0;
            updateButtonActive(acceptBtn, hasText);
        });

        // Закрыть панель создания группы
        cancelBtn.addEventListener('click', (e) => {
            e.preventDefault();
            hideModal(id, true);
        });
        
        // Создание группы
        acceptBtn.addEventListener('click', (e) => {
            e.preventDefault();
            
            const data = {
                name: groupName.value.trim()
            };
            createGroupAPI(message, data);
        });
    }





    // =============== СТАРТОВЫЕ ДЕЙСТВИЯ ===============
    // Получить список групп пользователя
    getGroupsListAPI();





    // =============== ОБРАБОТКА КНОПОК ===============
    // Открыть панель создания группы
    document.getElementById('openCreateGroupPanel').addEventListener('click', (e) => {
        e.preventDefault();
        if (!isNoModals()) return;
        
        const createGroupModalId = 'createGroupModal';

        // Создать панель создания группы
        createCreateGroupPanel(createGroupModalId);
        showModal(createGroupModalId)
    });
});
