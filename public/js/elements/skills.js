// skills.js
import {
    userSkillsLevelsGet, userSkillsGet,
    userSkillsGetByUser, userSkillsAdd, userSkillsEdit, userSkillsDelete,
    userSkillEndorsementAdd, userSkillEndorsementDelete
} from '../api.js';

document.addEventListener('DOMContentLoaded', () => {
    if (!window.appData) {
        console.error('appData не определен');
        return;
    }

    const currentUserId = window.appData.currentUserId;
    const userId = window.appData.userId;
    const panel = window.appData.panel;





    let allSkills;
    let userSkills;
    let skillLevels;


    
    // Элементы
    const userSkillsPanel = document.querySelector('.user-skills-panel');
    const editSelfSkills = userSkillsPanel.querySelector('.user-skills-edit-btn');
    const skillsList = userSkillsPanel.querySelector('.user-skills-list');




    
    //=============== МОДАЛЬНЫЕ ОКНА ===============
    // Создание панели добавления новых навыков пользователя
    async function userSkillAddNewModal() {
        const modal = await createModalHTML('userSkillAddNewModal', `
            <div class="modal-title">
                <h2>Список навыков</h2>
            </div>
            <div class="modal-main add-skills-list"></div>
            <div class="modal-footer">
                <button class="modal-btn close-add-new-skill-modal">Отмена</button>
            </div>
        `);
        showModal(modal);

        
        // Закрыть панель редактирования навыков пользователя
        modal.querySelector('.close-add-new-skill-modal').addEventListener('click', (e) => {
            e.preventDefault();
            hideModal(modal, true);
        });


    
        // =============== РАЗМЕТКА ДОБАВЛЕНИЯ НАВЫКОВ ===============
        const addSkillsList = modal.querySelector('.add-skills-list');

        // Возвращает HTML навыка в панели добавления навыка из данных
        function createElement(skill) {
            const skillId = skill.id;
            const skillName = skill.name;
            const hasSkill = skill.has_skill;

            const hasSkillClass = hasSkill ? 'has-skill' : '';

            return `
                <button class="add-new-skill-btn ${hasSkillClass}" ${!!hasSkill ? 'disabled' : ''} data-skill-id="${skillId}" data-has-skill="${hasSkill}">
                    <h3>${skillName}</h3>
                </button>
            `;
        }

        // Обновить навыки в панели добавления навыка
        function updateList(skills) {
            addSkillsList.innerHTML = '';
            
            if (skills && skills.length > 0) {
                skills.forEach(skill => {
                    const skillHTML = createElement(skill);
                    addSkillsList.insertAdjacentHTML('beforeend', skillHTML);
                });
            }
            

            const hasSkills = addSkillsList.children.length > 0 && !addSkillsList.querySelector('.no-skills-message');
            if (!hasSkills) {
                addSkillsList.innerHTML = `<p class="no-skills-message">Не удалось получить навыки.</p>`;
            }


            
            if (!currentUserId || userId != currentUserId) return;

            // Добавить навык
            addSkillsList.querySelectorAll('.add-new-skill-btn')?.forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const hasSkill = btn.dataset.hasSkill;
                    const skillId = btn.dataset.skillId;
                    if (!!hasSkill && skillId) {
                        await addUserSkillAPI(skillId);
                        hideModal(modal, true);
                    }
                });
            });
        }



        // =============== ИНИЦИАЛИЗАЦИЯ ===============
        updateList(allSkills);
    }

    // Создание панели редактирования навыков пользователя
    async function createUserSkillEditModal() {
        const modal = await createModalHTML('userSkillEditModal', `
            <div class="modal-title">
                <h2>Ваши навыки</h2>
            </div>
            <div class="modal-main user-skills-list" id="userSkillsList"></div>
            <div class="modal-footer">
                <button class="modal-btn close-edit-skill-modal">Закрыть</button>
            </div>
        `);
        showModal(modal);

        
        // Закрыть панель
        modal.querySelector('.close-edit-skill-modal').addEventListener('click', (e) => {
            e.preventDefault();
            hideModal(modal, true);
        });


    
        // =============== РАЗМЕТКА РЕДАКТИРОВАНИЯ НАВЫКОВ ===============
        const userSkillsList = modal.querySelector('.user-skills-list');
        
        // Возвращает HTML навыка
        function createElement(skill) {
            let userSkillId = skill.user_skill_id;
            let skillName = skill.name;
            let skillLevelId = skill.level_id;
            
            const levelsDropdown = selectCustomDropdown(userSkillId, skillLevelId, skillLevels);

            return `
                <div class="user-skill" data-user-skill-id="${userSkillId}" data-skill-level-id="${skillLevelId}">
                    <h3>${skillName}</h3>
                    ${levelsDropdown}
                    <button class="button user-skill-delete-btn">Удалить</button>
                </div>
            `
        }

        // Обновить навыки в панели
        function updateList(skills) {
            userSkillsList.innerHTML = '';
            
            if (skills && skills.length > 0) {
                skills.forEach(skill => {
                    const skillHTML = createElement(skill);
                    userSkillsList.insertAdjacentHTML('beforeend', skillHTML);
                });
            }
            

            const hasSkills = userSkillsList.children.length > 0 && !userSkillsList.querySelector('.no-skills-message');
            if (!hasSkills) {
                userSkillsList.innerHTML = `<p class="no-skills-message">Вы не добавили ни одного навыка.</p>`;
            }
            
            



            if (!currentUserId || userId != currentUserId) return;

            // =============== ДОБАВЛЕНИЕ НАВЫКОВ ===============
            // Добавить навык
            const addSkillButton = `
                <button class="add-new-skill-btn">
                    <span>Добавить новый навык</span>
                </button>
            `;
            userSkillsList.insertAdjacentHTML('beforeend', addSkillButton);
            userSkillsList.querySelector('.add-new-skill-btn')?.addEventListener('click', async (e) => {
                e.stopPropagation();

                const userSkillsIds = Array.isArray(userSkills) ? userSkills.map(item => item.skill_id) : [];
                await getAllSkillsAPI(userSkillsIds);
                userSkillAddNewModal();
            });



            // =============== РЕДАКТИРОВАНИЕ НАВЫКОВ ===============
            // Удаление навыка
            userSkillsList.querySelectorAll('.user-skill-delete-btn')?.forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.stopPropagation();
                    const parentDiv = btn.closest('.user-skill');
                    const userSkillId = parentDiv.dataset.userSkillId;
                    if (userSkillId) {
                        const confirmed = await confirmationModal('Вы уверены, что хотите удалить этот навык?', 'Удаление навыка');
                        if (confirmed) deleteUserSkillAPI(userSkillId);
                    }
                });
            });

            // Выбор уровня навыка
            userSkillsList.querySelectorAll('.custom-dropdown').forEach(dropdown => {
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



        
    
        // Удалить навык пользователя
        async function deleteUserSkillAPI(userSkillId) {
            try {
                const data = {
                    userSkillId: userSkillId
                };

                const result = await userSkillsDelete(data);

                if (result.success) {
                    await getUserSkillsAPI();
                    updateList(userSkills);
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
            try {
                const data = {
                    userSkillId: userSkillId,
                    skillLevelId: levelId
                };

                const result = await userSkillsEdit(data);

                if (result.success) {
                    await getUserSkillsAPI();
                    updateList(userSkills);
                    updateUserSkills(userSkills);
                } else {
                    console.log(result.error || 'Ошибка обработки навыков');
                }

            } catch (err) {
                console.log('Ошибка сервера');
            }
        }



        // =============== ИНИЦИАЛИЗАЦИЯ ===============
        updateList(userSkills);
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
        const eSvg = isEndorsement ? `
                <path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/>
            ` : '';
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

    // Обновить навыки пользователя на его странице
    function updateUserSkills(skills) {
        skillsList.innerHTML = '';
        
        if (skills && skills.length > 0) {
            skills.forEach(skill => {
                const skillHTML = createElementUserSkills(skill);
                skillsList.insertAdjacentHTML('beforeend', skillHTML);
            });
        }
        

        const hasSkills = skillsList.children.length > 0 && !skillsList.querySelector('.no-skills-message');
        if (!hasSkills) {
            const text = userId == currentUserId ? 'Вы не добавили ни одного навыка.' : 'Пользователь не добавил ни одного навыка.'
            skillsList.innerHTML = `<p class="no-skills-message">${text}</p>`;
        }



        if (!currentUserId) return;

        // Обработчики кнопок
        if (userId == currentUserId) {
            // Открыть панель редактирования навыков пользователя
            editSelfSkills.addEventListener('click', (e) => {
                e.preventDefault();
                createUserSkillEditModal();
            });
        } else {
            // Добавить подтверждение
            skillsList.querySelectorAll('.btn-skill-endorsement-add')?.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const parentDiv = btn.closest('.user-skill');
                    const userSkillId = parentDiv.dataset.userSkillId;
                    addEndorsementAPI(userSkillId);
                });
            });

            // Удалить подтверждение
            skillsList.querySelectorAll('.btn-skill-endorsement-delete')?.forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const parentDiv = btn.closest('.user-skill');
                    const userSkillId = parentDiv.dataset.userSkillId;
                    deleteEndorsementAPI(userSkillId);
                });
            });
        }
    }




    
    //=============== API ===============
    // Получить навыки
    async function getAllSkillsAPI(selectedSkillsIds = null) {
        let skills = [];
        try {
            const data = {
                userSkillsIds: selectedSkillsIds
            };

            const result = await userSkillsGet(data);

            if (result.success) {
                skills = result.skills;
            } else {
                console.log(result.error || 'Ошибка обработки навыков');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
        allSkills = skills;
    }
    
    // Получить уровни навыков
    async function getSkillLevelsAPI() {
        let levels = [];
        try {
            const result = await userSkillsLevelsGet();

            if (result.success) {
                levels = result.levels;
            } else {
                console.log(result.error || 'Ошибка обработки уровней навыков');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
        skillLevels = levels;
    }
    
    // Добавить навык пользователю
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
    

    // Получить навыки пользователя
    async function getUserSkillsAPI() {
        let skills = [];
        try {
            const data = {};
            
            if (currentUserId) {
                // У неавторизованных пользователей не может быть голосов
                data.currentUserId = currentUserId;
            }

            const result = await userSkillsGetByUser(userId, data);

            if (result.success) {
                skills = result.skills;
            } else {
                console.log(result.error || 'Ошибка обработки отношений');
            }

        } catch (err) {
            console.log('Ошибка сервера');
        }
        userSkills = skills;
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




    
    (async () => {
        await getUserSkillsAPI();
        updateUserSkills(userSkills);

        if (userId == currentUserId) await getSkillLevelsAPI();
    })();

});
