// user_profile.js
// LEGACY
import {
    relationshipsSubscribe, relationshipsUnsubscribe,
    skillLevelsGet, skillsGet,
    userSkillsGet, userSkillsAdd, userSkillsEdit, userSkillsDelete,
    userSkillEndorsementAdd, userSkillEndorsementDelete
} from "../api.js";

document.addEventListener('DOMContentLoaded', function() {
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }
    
    const profilePath = window.appData.path;

    const currentUserId = window.appData.currentUserId;
    const userId = window.appData.userId;
    const panel = window.appData.panel;
    
    
    // ID элементов модального окна редактирования навыков
    const editUserSkillsModalId = 'editUserSkillsModal';
    const closeEditUserSkillsBtnId = 'closeUserSkillsEditButton'

    // ID элементов модального окна добавления навыка
    const addSkillModalId = 'addSkillModal';
    const closeAddSkillBtnId = 'cancelAddSkillButton';


    // Полученные по API массивы
    let skillsLevels;
    let userSkills;
    let allSkills;



    

    //=============== МОДАЛЬНЫЕ ОКНА ===============
    // Создание модального окна добавления навыка пользователя
    function createAddSkillPanel(id, closeBtnId) {
        createModalHTML(id, `
            <div class="modal-title">
                <h2>Список навыков</h2>
            </div>
            <div class="modal-main user-skills" id="addSkillList">
                
            </div>
            <div class="modal-footer">
                <button class="modal-btn" id="${closeBtnId}">Отмена</button>
            </div>
        `);
    }

    // Создание модального окна редактирования навыков пользователя
    function createEditUserSkillsPanel(id, closeBtnId) {
        createModalHTML(id, `
            <div class="modal-title">
                <h2>Ваши навыки</h2>
            </div>
            <div class="modal-main user-skills" id="userSkillsList">
            
            </div>
            <div class="modal-footer">
                <button class="modal-btn" id="${closeBtnId}">Закрыть</button>
            </div>
        `);
    }




    
    //=============== РАЗМЕТКА ДОБАВЛЕНИЯ НАВЫКОВ ===============
    // Возвращает HTML навыка в панели добавления навыка из данных
    function createElementAddSkillPanel(skill) {
        const skillId = skill.id;
        const skillName = skill.name;
        const hasSkill = skill.has_skill;

        const skillClass = hasSkill ? 'has-skill' : '';

        return `
            <button class="add-skill-btn ${skillClass}" data-skill-id="${skillId}" data-has-skill="${hasSkill}">
                <h3>${skillName}</h3>
            </button>
        `
        
    }

    // Обновить навыки в панели добавления навыка
    function updateAddSkillPanel(skills) {
        const addSkillList = document.getElementById('addSkillList');
        addSkillList.innerHTML = '';
        
        if (skills && skills.length > 0) {
            skills.forEach(skill => {
                const skillHTML = createElementAddSkillPanel(skill);
                addSkillList.insertAdjacentHTML('beforeend', skillHTML);
            });
            initAddSkillPanelActions();
        } else {
            const emptyText = `<p>Нет доступных навыков</p>`;
            addSkillList.insertAdjacentHTML('beforeend', emptyText);
        }
    }

    // Инициализация действий с навыками в панели добавления навыка
    function initAddSkillPanelActions() {
        if (!currentUserId || userId != currentUserId) return;

        // Добавить навык пользователю
        document.querySelectorAll('.add-skill-btn')?.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const hasSkill = btn.dataset.hasSkill;
                const skillId = btn.dataset.skillId;
                if (!!hasSkill && skillId) {
                    await addUserSkillAPI(skillId);
                    hideModal(addSkillModalId);
                }
            });
        });
    }



    //=============== РАЗМЕТКА РЕДАКТИРОВАНИЯ НАВЫКОВ ===============
    // Возвращает HTML навыка в панели редактирования навыков пользователя из данных
    function createElementEditUserSkillPanel(skill) {
        let userSkillId = skill.user_skill_id;
        let skillName = skill.name;
        let skillLevelId = skill.level_id;
        
        const levelsDropdown = selectCustomDropdown(userSkillId, skillLevelId, skillsLevels);

        return `
            <div class="user-skill" data-user-skill-id="${userSkillId}" data-skill-level-id="${skillLevelId}">
                <h3>${skillName}</h3>
                ${levelsDropdown}
                <button class="button user-skill-delete-btn">Удалить</button>
            </div>
        `
    }

    // Обновить навыки в панели редактирования навыков пользователя
    function updateEditUserSkillsPanel(skills) {
        const userSkillsList = document.getElementById('userSkillsList');
        userSkillsList.innerHTML = '';
        
        if (skills && skills.length > 0) {
            skills.forEach(skill => {
                const skillHTML = createElementEditUserSkillPanel(skill);
                userSkillsList.insertAdjacentHTML('beforeend', skillHTML);
            });
        } else {
            const emptyText = `<p>Вы не добавили ни одного навыка</p>`;
            userSkillsList.insertAdjacentHTML('beforeend', emptyText);
        }

        // Добавить навык
        const addSkillButton = `
            <button class="add-field-btn" id="addNewUserSkillButton">
                Добавить новый навык
            </button>
        `
        userSkillsList.insertAdjacentHTML('beforeend', addSkillButton)
        initEditUserSkillsPanelActions();
    }

    // Инициализация действий с навыками в панели редактирования навыков пользователя
    function initEditUserSkillsPanelActions() {
        if (!currentUserId || userId != currentUserId) return;

        const modal = document.getElementById(editUserSkillsModalId);
        
        // Добавить навык
        document.getElementById('addNewUserSkillButton')?.addEventListener('click', async (e) => {
            e.stopPropagation();
            const userSkillsIds = Array.isArray(userSkills) ? userSkills.map(item => item.id) : [];
            await getSkillsAPI(userSkillsIds);

            await updateAddSkillPanel(allSkills);
            showModal(addSkillModalId);
        });

        // Удаление навыка
        modal.querySelectorAll('.user-skill-delete-btn')?.forEach(btn => {
            btn.addEventListener('click', async (e) => {
                e.stopPropagation();
                const parentDiv = btn.closest('.user-skill');
                const userSkillId = parentDiv.dataset.userSkillId;
                if (userSkillId) {
                    const confirmed = await confirmationModal('Вы уверены, что хотите удалить этот навык?', 'Удаление навыка');
                    if (confirmed)
                        deleteUserSkillAPI(userSkillId);
                }
            });
        });



        // Выбор уровня навыка
        modal.querySelectorAll('.custom-dropdown').forEach(dropdown => {
            const trigger = dropdown.querySelector('.dropdown-trigger');
            const menu = dropdown.querySelector('.dropdown-menu');
            const userSkillId = dropdown.dataset.parentId;
            
            // Удаляем старые обработчики, чтобы не дублировать
            trigger.replaceWith(trigger.cloneNode(true));
            const newTrigger = dropdown.querySelector('.dropdown-trigger');
            
            // Открытие/закрытие
            newTrigger.addEventListener('click', (e) => {
                e.stopPropagation();
                // Закрыть все другие открытые dropdown
                document.querySelectorAll('.custom-dropdown.open').forEach(d => {
                    if (d !== dropdown) d.classList.remove('open');
                });
                dropdown.classList.toggle('open');
            });
            
            // Выбор пункта
            const items = dropdown.querySelectorAll('.dropdown-menu li');
            items.forEach(item => {
                item.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const levelId = item.dataset.elementId;
                    const levelTitle = item.textContent;
                    
                    // Обновить текст на триггере
                    const selectedText = dropdown.querySelector('.selected-text');
                    if (selectedText) selectedText.textContent = levelTitle;
                    
                    // Обновить data-current-element-id
                    dropdown.dataset.currentElementId = levelId;
                    
                    // Обновить класс selected у пунктов
                    items.forEach(i => i.classList.remove('selected'));
                    item.classList.add('selected');
                    
                    // Закрыть dropdown
                    dropdown.classList.remove('open');
                    
                    // Вызвать API для сохранения уровня
                    await editUserSkillAPI(userSkillId, levelId)
                });
            });
        });
    }



    //=============== РАЗМЕТКА НАВЫКОВ ПОЛЬЗОВАТЕЛЯ ===============
    // Возвращает HTML навыка в панели навыков на странице пользователя из данных
    function createElementUserSkills(skill) {
        let userSkillId = skill.user_skill_id;
        let skillName = skill.name;
        let levelName = skill.level_name;
        let levelTitle = skill.level_title;
        let eCount = skill.endorsements_count;

        let isEndorsement = skill.is_endorsement;

        let skillLevel = 0;
        switch (levelName) {
            case 'beginner':
                skillLevel = 1;
                break;
                
            case 'intermediate':
                skillLevel = 2;
                break;
                
            case 'expert':
                skillLevel = 3;
                break;
        
            default:
                skillLevel = 0;
                break;
        }

        const eBtnTitle = isEndorsement ? 'Удалить своё подтверждение' : 'Подтвердить навык';
        const eBtnAction = isEndorsement ? 'delete' : 'add';
        const eSvg = isEndorsement ?
            'M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z' :
            ''
        const eBtn = (!currentUserId || userId == currentUserId) ? '' : `
            <button class="button btn-skill-endorsement-${eBtnAction}" type="button">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor">
                    <path d="${eSvg}"/>
                </svg>
                <span>${eBtnTitle}</span>
            </button>
        `;
        return `
            <div class="user-skill" data-user-skill-id="${userSkillId}">
                <div class="skill-head">
                    <h3 class="skill-title">
                        <b>${skillName}</b>
                    </h3>
                    <p class="skill-endorsements-count">
                        Подтверждений: <b>${eCount}</b>
                    </p>
                </div>
                
                <div class="skill-level">
                    <div class="progress-scale fullness-${skillLevel} max-fullness-3"></div>
                    <p class="skill-level-title">${levelTitle}</p>
                </div>

                ${eBtn}
            </div>
        `;
    }

    // Обновить навыки пользователя в панели навыков на его странице
    function updateUserSkills(skills) {
        const userSkillsList = document.getElementById('skillsList');
        userSkillsList.innerHTML = '';
        
        if (skills && skills.length > 0) {
            skills.forEach(skill => {
                const skillHTML = createElementUserSkills(skill);
                userSkillsList.insertAdjacentHTML('beforeend', skillHTML);
            });
            initUserSkillsActions();
        }
        

        const hasSkills = userSkillsList.children.length > 0 && !userSkillsList.querySelector('.no-skills-message');
        if (!hasSkills) {
            const text = userId == currentUserId ? "Вы не добавили ни одного навыка." : "Пользователь не добавил ни одного навыка."
            userSkillsList.innerHTML = `<p class="no-skills-message">${text}</p>`;
        }
    }

    // Инициализация действий с навыками в панели навыков на странице пользователя
    function initUserSkillsActions() {
        if (!currentUserId || userId == currentUserId) return;
        
        // Добавить подтверждение
        document.querySelectorAll('.btn-skill-endorsement-add')?.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const parentDiv = btn.closest('.user-skill');
                const userSkillId = parentDiv.dataset.userSkillId;
                addEndorsementAPI(userSkillId);
            });
        });

        // Удалить подтверждение
        document.querySelectorAll('.btn-skill-endorsement-delete')?.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                const parentDiv = btn.closest('.user-skill');
                const userSkillId = parentDiv.dataset.userSkillId;
                deleteEndorsementAPI(userSkillId);
            });
        });
    }

    


    
    //=============== API ===============
    // Получить навыки пользователя
    async function getUserSkillsAPI() {
        const data = {};
        
        if (currentUserId) {
            data.currentUserId = currentUserId;
        }

        try {
            const result = await userSkillsGet(userId, data);

            if (result.success) {
                userSkills = result.skills
            } else {
                console.log(result.error || 'Ошибка обработки отношений');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
        return [];
    }

    // Получить навыки
    async function getSkillsAPI(userSkillsIds) {
        const data = {
            userSkillsIds: userSkillsIds
        };

        try {
            const result = await skillsGet(data);

            if (result.success) {
                allSkills = result.skills;
            } else {
                console.log(result.error || 'Ошибка обработки навыков');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
    }

    // Получить уровни навыков
    async function getSkillLevelsAPI() {
        try {
            const result = await skillLevelsGet();

            if (result.success) {
                skillsLevels = result.levels;
            } else {
                console.log(result.error || 'Ошибка обработки уровней навыков');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
        return [];
    }
    
    
    // Добавление подтверждения
    async function addEndorsementAPI(userSkillId) {
        const data = {
            userId: userId,
            userSkillId: userSkillId
        };
        
        try {
            const result = await userSkillEndorsementAdd(data);

            if (result.success) {
                await getUserSkillsAPI();
                updateUserSkills(userSkills);
            } else {
                console.log(result.error || 'Ошибка обработки навыков');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }
    }
    
    // Удаление подтверждения
    async function deleteEndorsementAPI(userSkillId) {
        const data = {
            userId: userId,
            userSkillId: userSkillId
        };
        
        try {
            const result = await userSkillEndorsementDelete(data);

            if (result.success) {
                await getUserSkillsAPI();
                updateUserSkills(userSkills);
            } else {
                console.log(result.error || 'Ошибка обработки навыков');
            }

        } catch (err) {
            console.error('Ошибка сервера');
        }
    }


    // Добавить навык пользователя
    async function addUserSkillAPI(skillId) {
        const data = {
            skillId: skillId
        };

        try {
            const result = await userSkillsAdd(data);

            if (result.success) {
                await getUserSkillsAPI();
                updateEditUserSkillsPanel(userSkills);
                updateUserSkills(userSkills);
            } else {
                console.log(result.error || 'Ошибка обработки навыков');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
    }

    // Удалить навык пользователя
    async function deleteUserSkillAPI(userSkillId) {
        const data = {
            userSkillId: userSkillId
        };

        try {
            const result = await userSkillsDelete(data);

            if (result.success) {
                await getUserSkillsAPI();
                updateEditUserSkillsPanel(userSkills);
                updateUserSkills(userSkills);
            } else {
                console.log(result.error || 'Ошибка обработки навыков');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
    }

    // Редактировать навык пользователя
    async function editUserSkillAPI(userSkillId, levelId) {
        const data = {
            userSkillId: userSkillId,
            skillLevelId: levelId
        };

        try {
            const result = await userSkillsEdit(data);

            if (result.success) {
                await getUserSkillsAPI();
                updateEditUserSkillsPanel(userSkills);
                updateUserSkills(userSkills);
            } else {
                console.log(result.error || 'Ошибка обработки навыков');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
    }
    

    // Изменить подписку
    async function subscribeAPI(isSubscribe) {
        const data = {
            related_user_id: userId
        };

        try {
            const result = await (isSubscribe ? relationshipsSubscribe(data) : relationshipsUnsubscribe(data));

            if (result.success) {
                location.reload();
            } else {
                console.log(result.error || 'Ошибка обработки отношений');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
    }



    

    (async () => {
        if (panel === 'skills') {
            await getUserSkillsAPI();
            updateUserSkills(userSkills);
            
            if (userId == currentUserId) {
                await getSkillLevelsAPI();

                // Модальные окна
                await createAddSkillPanel(addSkillModalId, closeAddSkillBtnId);
                await createEditUserSkillsPanel(editUserSkillsModalId, closeEditUserSkillsBtnId);


                // Кнопки
                document.getElementById('openEditUserSkillsButton').addEventListener('click', (e) => {
                    // Открыть панель редактирования навыков пользователя
                    e.preventDefault();
                    updateEditUserSkillsPanel(userSkills);  // Обновляем список навыков пользователя в панели редактирования навыков
                    showModal(editUserSkillsModalId)
                });
                document.getElementById(closeEditUserSkillsBtnId).addEventListener('click', (e) => {
                    // Закрыть панель редактирования навыков пользователя
                    e.preventDefault();
                    hideModal(editUserSkillsModalId);
                });
                document.getElementById(closeAddSkillBtnId).addEventListener('click', (e) => {
                    // Закрыть панель добавления навыка
                    e.preventDefault();
                    hideModal(addSkillModalId);
                })
            }
        }
    })();





    if (userId != currentUserId) {
        // =============== КНОПКИ В ЧУЖОМ ПРОФИЛЕ ===============
        document.getElementById('mainMessageUserButton')?.addEventListener('click', (e) => {
            // Написать пользователю
            e.preventDefault();
            window.location.href = `${window.APP_CONFIG.BASE_URL}/msg?type=user&id=${userId}`;
        });
        document.getElementById('baseMessageUserButton')?.addEventListener('click', (e) => {
            // Написать пользователю
            e.preventDefault();
            window.location.href = `${window.APP_CONFIG.BASE_URL}/msg?type=user&id=${userId}`;
        });
        document.getElementById('followButton')?.addEventListener('click', (e) => {
            // Отправить заявку
            e.preventDefault();
            subscribeAPI(true);
        });
        document.getElementById('unfollowButton')?.addEventListener('click', (e) => {
            // Отменить заявку
            e.preventDefault();
            subscribeAPI(false);
        });
        document.getElementById('acceptButton')?.addEventListener('click', (e) => {
            // Принять заявку
            e.preventDefault();
            subscribeAPI(true);
        });
        document.getElementById('deleteButton')?.addEventListener('click', (e) => {
            // Удалить из контактов
            e.preventDefault();
            subscribeAPI(false);
        });

    }
    

    // =============== КНОПКИ В ЛЮБОМ ПРОФИЛЕ ===============
    document.getElementById('postsNavigationButton')?.addEventListener('click', (e) => {
        // Написать пользователю
        e.preventDefault();
        window.location.href = `${profilePath}`;
    });
    document.getElementById('skillsNavigationButton')?.addEventListener('click', (e) => {
        // Написать пользователю
        e.preventDefault();
        window.location.href = `${profilePath}?p=skills`;
    });
});
