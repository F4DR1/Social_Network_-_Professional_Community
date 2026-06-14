// custom_dropdown.js
document.addEventListener('DOMContentLoaded', function() {

    // Функция создания кастомного выпадающего списка с выбором элемента
    window.selectCustomDropdown = function(parentId, currentElementId, elementsArray) {
        // elementsArray: массив объектов { id, name, title }
        const currentElement = elementsArray.find(el => el.id == currentElementId) || elementsArray[0];
        const dropdownId = `dropdown_${parentId}`;
        
        // HTML список значений
        let optionsHtml = '';
        elementsArray.forEach(el => {
            const isSelected = el.id == currentElementId;
            optionsHtml += `
                <li class="${isSelected ? 'selected' : ''}" data-element-id="${el.id}">
                    ${el.title}
                </li>
            `;
        });
        
        return `
            <div class="custom-dropdown" id="${dropdownId}" data-parent-id="${parentId}" data-current-element-id="${currentElementId}">
                <button class="dropdown-trigger" type="button">
                    <span class="selected-text">${currentElement.title}</span>
                    <svg class="dropdown-arrow" width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M7 10l5 5 5-5z"/>
                    </svg>
                </button>
                <ul class="dropdown-menu">
                    ${optionsHtml}
                </ul>
            </div>
        `;
    }

    

    // =============== ГЛОБАЛЬНОЕ УПРАВЛЕНИЕ ===============
    // Закрытие dropdown при клике вне их
    document.addEventListener('click', (e) => {
        // Если клик был внутри любого .custom-dropdown – ничего не делаем
        if (e.target.closest('.custom-dropdown')) return;
        document.querySelectorAll('.custom-dropdown.open').forEach(d => {
            d.classList.remove('open');
        });
    });


    // Открытие / закрытие дропдауна (делегирование на документ)
    document.addEventListener('click', (e) => {
        const trigger = e.target.closest('.dropdown-trigger');
        if (!trigger) return;

        const dropdown = trigger.closest('.custom-dropdown');
        if (!dropdown) return;

        // Останавливаем всплытие, чтобы не сработал верхний закрыватель
        e.stopPropagation();

        const isOpen = dropdown.classList.contains('open');

        // Закрываем все открытые дропдауны, которые не являются родителями текущего
        document.querySelectorAll('.custom-dropdown.open').forEach(d => {
            if (!d.contains(dropdown)) d.classList.remove('open');
        });

        // Переключаем состояние текущего
        dropdown.classList.toggle('open', !isOpen);
    });

    // Выбор элемента в списке (делегирование)
    document.addEventListener('click', (e) => {
        const item = e.target.closest('.custom-dropdown .dropdown-menu li[data-element-id]');
        if (!item) return;

        const dropdown = item.closest('.custom-dropdown');
        if (!dropdown) return;

        // Если внутри элемента есть вложенный дропдаун – не обрабатываем выбор
        if (item.querySelector('.custom-dropdown')) return;

        // Снимаем выделение со всех пунктов и ставим на выбранный
        const menu = item.closest('.dropdown-menu');
        menu.querySelectorAll('li[data-element-id]').forEach(li => li.classList.remove('selected'));
        item.classList.add('selected');

        // Обновляем текст на триггере
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const selectedText = trigger.querySelector('.selected-text');
        if (selectedText) selectedText.textContent = item.textContent.trim();

        // Обновляем data-current-element-id
        dropdown.dataset.currentElementId = item.dataset.elementId;

        // Закрываем дропдаун (можно оставить открытым, если нужно)
        dropdown.classList.remove('open');

        // Здесь можно вызвать callback или кастомное событие
        // Например: dropdown.dispatchEvent(new CustomEvent('change', { detail: { id: item.dataset.elementId } }));
    });

    
    // // Открытие dropdown (можно переназначить)
    // document.querySelectorAll('.custom-dropdown').forEach(dropdown => {
    //     const trigger = dropdown.querySelector('.dropdown-trigger');
    //     const menu = dropdown.querySelector('.dropdown-menu');
        
    //     // Удаляем старые обработчики, чтобы не дублировать
    //     trigger.replaceWith(trigger.cloneNode(true));
    //     const newTrigger = dropdown.querySelector('.dropdown-trigger');
        
    //     // Открытие/закрытие
    //     newTrigger.addEventListener('click', (e) => {
    //         e.stopPropagation();
    //         // Закрыть все другие открытые dropdown
    //         document.querySelectorAll('.custom-dropdown.open').forEach(d => {
    //             if (d !== dropdown) d.classList.remove('open');
    //         });
    //         dropdown.classList.toggle('open');
    //     });
        
    //     // Выбор пункта
    //     const items = dropdown.querySelectorAll('.dropdown-menu li');
    //     items.forEach(item => {
    //         item.addEventListener('click', async (e) => {
    //             e.stopPropagation();

    //             // Если есть подсписки - не скрываем при клике
    //             console.log(item);
    //             const childDropdownTrigger = item.querySelector('button.dropdown-trigger');
    //             if (childDropdownTrigger) return;
                
    //             // Закрыть dropdown
    //             dropdown.classList.remove('open');
    //         });
    //     });
    // });
});
