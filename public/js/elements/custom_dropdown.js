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

    

    // Закрытие dropdown при клике вне их
    document.addEventListener('click', () => {
        document.querySelectorAll('.custom-dropdown.open').forEach(d => {
            d.classList.remove('open');
        });
    });
    
    // Открытие dropdown (можно переназначить)
    document.querySelectorAll('.custom-dropdown').forEach(dropdown => {
        const trigger = dropdown.querySelector('.dropdown-trigger');
        const menu = dropdown.querySelector('.dropdown-menu');
        
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
                
                // Закрыть dropdown
                dropdown.classList.remove('open');
            });
        });
    });
});
